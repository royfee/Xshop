<?php
namespace royfee\xshop\Platforms;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Hanson\Foundation\Foundation;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use royfee\xshop\Traits\ApiTrait;

/**
 * 平台基类
 *
 * 提供公共的 HTTP 调用、日志和签名钩子.
 * 子平台只需要实现: gateway(), buildSystemParams(), buildSign().
 */
abstract class BasePlatform extends Foundation
{
    use ApiTrait;

    /** @var string|array access_token */
    protected $accessToken;

    /** @var array 平台配置 */
    protected $config;

    /** @var Client|null Guzzle 客户端 (按平台配置共享) */
    protected $httpClient;

    public function __construct($config, $accessToken = null)
    {
        $this->config      = $config;
        $this->accessToken = $accessToken;
        parent::__construct($config);
        $this->registerModules($this);
    }

    /**
     * 注册业务模块
     */
    abstract public function registerModules($pimple);

    /**
     * 平台名 (小写英文, e.g. 'taobao' / 'yueyan')
     */
    abstract public function getPlatformName(): string;

    /**
     * 获取平台默认的订单 Mapper, 默认返回 null.
     * 有需要的平台 (如 yueyan) 可重写此方法返回具体实例.
     */
    public function getMapper()
    {
        return null;
    }

    /**
     * 平台 API 网关
     */
    abstract protected function getGateway(): string;

    /**
     * 构造平台系统级参数 (在业务参数之前合并)
     */
    abstract protected function buildSystemParams(string $method): array;

    /**
     * 平台签名规则
     */
    abstract protected function buildSign(array $params): string;

    /**
     * 统一 HTTP 调度
     *
     * @return array
     * @throws PlatformException
     */
    public function request(string $method, array $params = []): array
    {
exit('eee');
        $allParams          = array_merge($this->buildSystemParams($method), $params);
        $allParams['sign']  = $this->buildSign($allParams);

        $this->log('debug', sprintf('[%s] request %s params=%s', $this->getPlatformName(), $method, $this->maskParams($allParams)));

        try {
            $client   = $this->getHttpClient();
            $response = $client->post($this->getGateway(), [
                'form_params' => $allParams,
                'timeout'     => $this->config['timeout'] ?? 30,
            ]);
        } catch (GuzzleException $e) {
            $this->log('error', sprintf('[%s] http error: %s', $this->getPlatformName(), $e->getMessage()));
            throw new PlatformException(sprintf('[%s] HTTP 调用失败: %s', $this->getPlatformName(), $e->getMessage()), 0, [], $e);
        }

        $result = json_decode($response->getBody(), true);
        if (!is_array($result)) {
            throw new PlatformException(sprintf('[%s] 返回非 JSON 数据', $this->getPlatformName()));
        }

        $this->log('debug', sprintf('[%s] response %s', $this->getPlatformName(), json_encode($result, JSON_UNESCAPED_UNICODE)));

        $error = $this->detectError($result);
        if ($error !== null) {
            throw new PlatformException(
                sprintf('[%s] %s', $this->getPlatformName(), $error['message'] ?? '平台返回错误'),
                $error['code'] ?? 0,
                $result
            );
        }

        return $result;
    }

    /**
     * 各平台差异化的错误识别. 默认仅识别 error_response 节点, 子类可覆盖.
     *
     * @return array|null 返回 null 表示无错误, 否则返回 [code, message]
     */
    protected function detectError(array $result): ?array
    {
        if (isset($result['error_response'])) {
            return [
                'code'    => (int)($result['error_response']['code']    ?? 0),
                'message' => (string)($result['error_response']['msg'] ?? '平台错误'),
            ];
        }
        return null;
    }

    /**
     * 业务模块访问
     */
    public function __get($property)
    {
        return $this->offsetGet($property);
    }

    public function getDev(): bool
    {
        return (bool)($this->config['is_dev'] ?? false);
    }

    public function success($data, string $message = ''): array
    {
        return ['code' => 0, 'message' => $message, 'data' => $data];
    }

    public function error(string $message = '', $data = null): array
    {
        return ['code' => 1, 'message' => $message, 'data' => $data];
    }

    /**
     * 共享 Guzzle 客户端
     */
    protected function getHttpClient(): Client
    {
        if ($this->httpClient === null) {
            $this->httpClient = new Client(['timeout' => $this->config['timeout'] ?? 30]);
        }
        return $this->httpClient;
    }

    /**
     * 平台级日志
     */
    protected function log(string $level, string $message): void
    {
        /** @var LoggerInterface $logger */
        $logger = $this->logger ?? new NullLogger();
        $logger->log($level, $message);
    }

    /**
     * 简易敏感字段脱敏, 用于日志
     */
    protected function maskParams(array $params): array
    {
        $sensitiveKeys = ['app_secret', 'client_secret', 'access_token', 'auth_code', 'sign', 'password'];
        foreach ($sensitiveKeys as $k) {
            if (isset($params[$k])) {
                $params[$k] = '***';
            }
        }
        return $params;
    }
}
