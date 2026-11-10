<?php

namespace Royfee\XShop\Platforms\Pdd\Api;

use Psr\Log\LoggerInterface;
use Royfee\XShop\Contracts\HttpClientInterface;
use Royfee\XShop\Contracts\PlatformInterface;
use Royfee\XShop\Platforms\AbstractApiResource;
use Royfee\XShop\Platforms\Pdd\Auth\PddAuth;
use Royfee\XShop\Platforms\Pdd\PddErrorTrait;

/**
 * 拼多多 API 模块抽象基类
 *
 * 统一处理拼多多网关调用: 系统参数拼装 -> 签名 -> POST JSON -> 错误解析。
 * 请求格式: Content-Type: application/json
 */
abstract class AbstractPddApi extends AbstractApiResource
{
    use PddErrorTrait;

    public function __construct(
        array $config,
        HttpClientInterface $http,
        PddAuth $auth,
        ?LoggerInterface $logger = null,
        ?PlatformInterface $platform = null
    ) {
        parent::__construct($config, $http, $auth, $logger, $platform);
    }

    /**
     * 调用拼多多 API
     *
     * POST https://gw-api.pinduoduo.com/api/router
     */
    protected function request(string $type, array $params = []): array
    {
        $token = $this->auth->getCachedToken();
        if (!$token) {
            throw new \RuntimeException('No valid token found, please authorize first');
        }

        $baseParams = [
            'type' => $type,
            'client_id' => $this->config['client_id'],
            'access_token' => $token['access_token'] ?? '',
            'timestamp' => time() * 1000,
            'data_type' => 'JSON',
            'version' => 'V1',
        ];

        $params = array_merge($baseParams, $params);
        $params['sign'] = $this->generateSign($params);

        $this->log('debug', 'PDD API Request', ['type' => $type, 'params' => $params]);

        $response = $this->http->post(
            $this->config['api_url'],
            $params,
            ['Content-Type' => 'application/json']
        );

        $this->log('debug', 'PDD API Response', ['type' => $type, 'response' => $response]);

        $this->checkResponseError($response, $type);

        return $response;
    }

    /**
     * 生成签名
     */
    protected function generateSign(array $params): string
    {
        ksort($params);
        $string = $this->config['client_secret'];
        foreach ($params as $key => $value) {
            if ($key !== 'sign' && $value !== null && $value !== '') {
                $string .= $key . $value;
            }
        }
        $string .= $this->config['client_secret'];
        return strtoupper(md5($string));
    }
}
