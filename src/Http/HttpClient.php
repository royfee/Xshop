<?php

namespace Royfee\XShop\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Royfee\XShop\Contracts\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * HTTP 客户端基类
 * 封装 GuzzleHttp，提供统一的请求接口
 * 
 * 注意: 请求头由各个平台类自行设置，不在此处统一配置
 */
class HttpClient implements HttpClientInterface
{
    /** @var Client Guzzle客户端 */
    protected $client;

    /** @var array 当前请求头 */
    protected $headers = [];

    /** @var int 超时时间 */
    protected $timeout = 30;

    /** @var int 连接超时 */
    protected $connectTimeout = 10;

    /** @var int 重试次数 */
    protected $retries = 3;

    /** @var LoggerInterface|null 日志 */
    protected $logger;

    /** @var bool 是否调试模式 */
    protected $debug = false;

    public function __construct(array $config = [], ?LoggerInterface $logger = null, bool $debug = false)
    {
        $this->timeout = $config['timeout'] ?? 30;
        $this->connectTimeout = $config['connect_timeout'] ?? 10;
        $this->retries = $config['retries'] ?? 3;
        $this->logger = $logger;
        $this->debug = $debug;

        $this->client = new Client([
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
            'http_errors' => false,
        ]);
    }

    /**
     * GET 请求
     */
    public function get(string $url, array $params = [], array $headers = []): array
    {
        return $this->request('GET', $url, ['query' => $params], $headers);
    }

    /**
     * POST 请求
     * 
     * 根据 Content-Type 自动选择发送方式:
     * - application/json => json 格式
     * - 其他 => form_params 格式
     */
    public function post(string $url, array $params = [], array $headers = []): array
    {
        $contentType = $headers['Content-Type'] ?? $headers['content-type'] ?? 'application/x-www-form-urlencoded';

        $options = ['headers' => $headers];

        if (strpos($contentType, 'application/json') !== false) {
            $options['json'] = $params;
        } else {
            $options['form_params'] = $params;
        }

        return $this->request('POST', $url, $options, $headers);
    }

    /**
     * 发送请求
     */
    protected function request(string $method, string $url, array $options, array $headers = []): array
    {
        if (!empty($headers)) {
            $options['headers'] = $headers;
        }

        $lastException = null;

        for ($attempt = 1; $attempt <= $this->retries; $attempt++) {
            try {
                $this->logRequest($method, $url, $options);

                $response = $this->client->request($method, $url, $options);
                $body = (string) $response->getBody();
                $statusCode = $response->getStatusCode();

                $this->logResponse($url, $statusCode, $body);

                $data = json_decode($body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $data = ['raw_body' => $body];
                }

                $data['http_status'] = $statusCode;

                return $data;

            } catch (RequestException $e) {
                $lastException = $e;
                $this->logError($url, $e->getMessage(), $attempt);

                if ($attempt < $this->retries) {
                    usleep(500000 * $attempt);
                }
            }
        }

        throw new \RuntimeException(
            "HTTP request failed after {$this->retries} attempts: " . $lastException->getMessage(),
            0,
            $lastException
        );
    }

    /**
     * 记录请求日志
     */
    protected function logRequest(string $method, string $url, array $options): void
    {
        if (!$this->debug || !$this->logger) {
            return;
        }

        $logData = [
            'method' => $method,
            'url' => $url,
            'options' => $this->maskSensitiveData($options),
        ];

        $this->logger->debug('HTTP Request', $logData);
    }

    /**
     * 记录响应日志
     */
    protected function logResponse(string $url, int $statusCode, string $body): void
    {
        if (!$this->debug || !$this->logger) {
            return;
        }

        $this->logger->debug('HTTP Response', [
            'url' => $url,
            'status_code' => $statusCode,
            'body' => $body,
        ]);
    }

    /**
     * 记录错误日志
     */
    protected function logError(string $url, string $error, int $attempt): void
    {
        if (!$this->logger) {
            return;
        }

        $this->logger->warning('HTTP Request Retry', [
            'url' => $url,
            'error' => $error,
            'attempt' => $attempt,
        ]);
    }

    /**
     * 脱敏敏感数据
     */
    protected function maskSensitiveData(array $data): array
    {
        $sensitiveKeys = ['client_secret', 'access_token', 'refresh_token', 'password', 'sign'];

        array_walk_recursive($data, function (&$value, $key) use ($sensitiveKeys) {
            if (in_array(strtolower($key), $sensitiveKeys, true)) {
                $value = '***';
            }
        });

        return $data;
    }

    public function setTimeout(int $seconds): HttpClientInterface
    {
        $this->timeout = $seconds;
        return $this;
    }

    public function setHeaders(array $headers): HttpClientInterface
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function removeHeader(string $name): HttpClientInterface
    {
        unset($this->headers[$name]);
        return $this;
    }
}
