<?php

namespace Royfee\XShop\Platforms\Pdd\Auth;

use Royfee\XShop\Contracts\AuthInterface;
use Royfee\XShop\Contracts\HttpClientInterface;
use Royfee\XShop\Cache\Cache;
use Psr\Log\LoggerInterface;
use Royfee\XShop\Platforms\Pdd\PddErrorTrait;
use Royfee\XShop\Support\LoggerAwareTrait;

/**
 * 拼多多 OAuth 认证模块
 *
 * 使用平台统一的 HTTP 客户端发送请求
 *
 * Cache Key: token_pdd_{client_id}_{mall_id}
 */
class PddAuth implements AuthInterface
{
    use LoggerAwareTrait;
    use PddErrorTrait;

    /** @var array 配置 */
    protected $config;

    /** @var Cache 缓存 */
    protected $cache;

    /** @var HttpClientInterface HTTP客户端 */
    protected $http;

    /** @var LoggerInterface|null 日志 */
    protected $logger;

    /** @var string|null 当前 mall_id */
    protected $mallId;

    /** @var string 平台标识 */
    protected $platform = 'pdd';

    /** @var string 拼多多店铺WEB端授权页 */
    protected $authorizeUrl = 'https://fuwu.pinduoduo.com/service-market/auth';

    public function __construct(
        array $config,
        Cache $cache,
        HttpClientInterface $http,
        ?LoggerInterface $logger = null
    ) {
        $this->config = $config;
        $this->cache = $cache;
        $this->http = $http;
        $this->logger = $logger;
        $this->mallId = $config['mall_id'] ?? null;
    }

    /**
     * 构建缓存Key
     * 格式: token_pdd_{client_id}_{mall_id}
     */
    protected function buildCacheKey(?string $mallId = null): string
    {
        $clientId = $this->config['client_id'] ?? 'default';
        $id = $mallId ?? $this->mallId ?? 'pending';
        return "token_{$this->platform}_{$clientId}_{$id}";
    }

    /**
     * 获取访问令牌
     * 
     * 流程:
     * 1. 优先从缓存获取
     * 2. 缓存没有则通过 code 换取
     * 3. 如果已过期且存在 refresh_token，自动刷新
     */
    public function getToken(?string $code = null): array
    {
        // 1. 如果有 mall_id，先查正式 key
        if ($this->mallId) {
            $cachedToken = $this->cache->get($this->buildCacheKey());
            if ($cachedToken && is_array($cachedToken)) {
                // 检查是否即将过期，提前刷新
                if ($this->isTokenExpiringSoon($cachedToken)) {
                    $this->log('info', 'Token expiring soon, auto refreshing...', [
                        'mall_id' => $this->mallId,
                    ]);
                    if (!empty($cachedToken['refresh_token'])) {
                        return $this->refreshToken($cachedToken['refresh_token']);
                    }
                }
                $this->log('debug', 'Token retrieved from cache', [
                    'cache_key' => $this->buildCacheKey(),
                    'mall_id' => $this->mallId,
                ]);
                return $cachedToken;
            }
        }

        // 2. 用 code 换 token
        if ($code === null) {
            throw new \RuntimeException(
                "No cached token found" .
                ($this->mallId ? " for mall_id [{$this->mallId}]" : "") .
                " and no authorization code provided"
            );
        }

        return $this->requestToken($code);
    }

    /**
     * 请求新 Token
     * 
     * POST https://open-api.pinduoduo.com/oauth/token
     */
    protected function requestToken(string $code): array
    {
        $params = [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'grant_type' => $this->config['grant_type'] ?? 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->config['redirect_uri'] ?? '',
        ];

        $this->log('info', 'Requesting new token', [
            'client_id' => $this->config['client_id'],
        ]);

        $response = $this->http->post(
            $this->config['auth_url'],
            $params,
            ['Content-Type' => 'application/json']
        );

        // 检查响应是否包含错误
        $this->checkResponseError($response, 'request token');

        if (!isset($response['access_token'])) {
            throw new \RuntimeException('Failed to get token: ' . json_encode($response));
        }

        // 从响应中提取 owner_id (即 mall_id)
        $ownerId = isset($response['owner_id']) ? (string) $response['owner_id'] : null;

        if ($ownerId) {
            $this->mallId = $ownerId;
            $this->log('info', 'Got mall_id from auth response', ['mall_id' => $ownerId]);
        }

        $this->saveToken($response);

        return $response;
    }

    /**
     * 刷新访问令牌
     * 
     * POST https://open-api.pinduoduo.com/oauth/token
     */
    public function refreshToken(string $refreshToken): array
    {
        $params = [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];

        $this->log('info', 'Refreshing token', [
            'mall_id' => $this->mallId,
        ]);

        $response = $this->http->post(
            $this->config['auth_url'],
            $params,
            ['Content-Type' => 'application/x-www-form-urlencoded']
        );

        // 检查响应是否包含错误
        $this->checkResponseError($response, 'refresh token');

        if (!isset($response['access_token'])) {
            $this->log('error', 'Token refresh failed', [
                'response' => $response,
                'mall_id' => $this->mallId,
            ]);
            throw new \RuntimeException('Failed to refresh token: ' . json_encode($response));
        }

        // 刷新时也可能返回 owner_id
        if (isset($response['owner_id'])) {
            $this->mallId = (string) $response['owner_id'];
        }

        $this->saveToken($response);

        return $response;
    }

    /**
     * 错误日志附加当前店铺标识
     */
    protected function errorContext(): array
    {
        return ['mall_id' => $this->mallId];
    }

    /**
     * 保存 Token 到缓存
     */
    protected function saveToken(array $token): void
    {
        $token['__mall_id'] = $this->mallId ?? '';
        $token['__client_id'] = $this->config['client_id'] ?? '';
        $token['__saved_at'] = date('Y-m-d H:i:s');

        $expiresIn = ($token['expires_in'] ?? 3600) - 300;
        $ttl = max($expiresIn, 300);

        // 保存到正式 key (有 mall_id 时)
        if ($this->mallId) {
            $formalKey = $this->buildCacheKey();
            $this->cache->set($formalKey, $token, $ttl);

            // 清理临时 key
            $pendingKey = $this->buildCacheKey('pending');
            $this->cache->delete($pendingKey);

            $this->log('info', 'Token saved to formal cache key', [
                'cache_key' => $formalKey,
                'mall_id' => $this->mallId,
                'ttl' => $ttl,
            ]);
        } else {
            // 没有 mall_id，先存到临时 key
            $pendingKey = $this->buildCacheKey('pending');
            $this->cache->set($pendingKey, $token, $ttl);

            $this->log('warning', 'Token saved to pending cache key (no mall_id)', [
                'cache_key' => $pendingKey,
                'ttl' => $ttl,
            ]);
        }
    }

    /**
     * 检查 Token 是否即将过期 (提前5分钟)
     */
    protected function isTokenExpiringSoon(array $token): bool
    {
        $expiresIn = $token['expires_in'] ?? 3600;
        $savedAt = strtotime($token['__saved_at'] ?? 'now');
        $expireAt = $savedAt + $expiresIn;

        // 如果距离过期时间小于5分钟，认为即将过期
        return ($expireAt - time()) < 300;
    }

    /**
     * 获取授权URL
     */
    public function getAuthorizeUrl(?string $redirectUri = null, ?string $state = null): string
    {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $redirectUri ?? $this->config['redirect_uri'] ?? '',
        ];

        if ($state !== null) {
            $params['state'] = $state;
        }

        return $this->authorizeUrl . '?' . http_build_query($params);
    }

    /**
     * 检查 Token 是否有效
     */
    public function isTokenValid(): bool
    {
        $token = $this->cache->get($this->buildCacheKey());
        return $token && is_array($token);
    }

    /**
     * 获取缓存中的 token
     */
    public function getCachedToken(): ?array
    {
        return $this->cache->get($this->buildCacheKey());
    }

    public function clearToken(): void
    {
        $this->cache->delete($this->buildCacheKey());
        $this->log('info', 'Token cleared', ['mall_id' => $this->mallId]);
    }

    public function getCacheKey(): string
    {
        return $this->buildCacheKey();
    }

    public function getMallId(): ?string
    {
        return $this->mallId;
    }

    public function setMallId(string $mallId): void
    {
        $this->mallId = $mallId;
    }
}
