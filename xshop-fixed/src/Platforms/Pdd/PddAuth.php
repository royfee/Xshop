<?php
namespace Royfee\XShop\Platforms\Pdd;

use Royfee\XShop\Contracts\AuthInterface;
use Royfee\XShop\Core\CacheManager;
use Royfee\XShop\Core\HttpClient;

class PddAuth implements AuthInterface
{
    protected $config;
    protected $http;
    protected $cache;
    protected $cacheKey = 'pdd_access_token';
    protected $refreshCacheKey = 'pdd_refresh_token';

    // 拼多多官方 OAuth 地址
    protected const AUTH_URL = 'https://fuwu.pinduoduo.com/service-market/auth';
    protected const TOKEN_URL = 'https://open-api.pinduoduo.com/oauth/token';

    public function __construct(array $config, HttpClient $http)
    {
        $this->config = $config;
        $this->http   = $http;
        // 实例化文件缓存
        $this->cache  = new CacheManager();
    }

    /**
     * 生成官方授权跳转URL
     */
    public function getAuthUrl(string $state = ''): string
    {
        $clientId    = $this->config['client_id'] ?? '';
        $redirectUri = $this->config['redirect_uri'] ?? '';

        if (empty($clientId) || empty($redirectUri)) {
            throw new \Exception('配置缺失：client_id 或 redirect_uri');
        }

        $params = [
            'response_type' => 'code',
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'state'         => $state ?: md5(time())
        ];

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * 根据授权Code换取Token
     */
    public function getTokenByCode(string $code): string
    {
        $clientId     = $this->config['client_id'] ?? '';
        $clientSecret = $this->config['client_secret'] ?? '';
        $redirectUri  = $this->config['redirect_uri'] ?? '';

        if (empty($clientId) || empty($clientSecret)) {
            throw new \Exception('配置缺失：client_id / client_secret');
        }

        $params = [
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $redirectUri
        ];

        $result = $this->http->post(self::TOKEN_URL, $params);
        if (empty($result['access_token'])) {
            throw new \Exception('换取Token失败：' . json_encode($result, JSON_UNESCAPED_UNICODE));
        }

        // 缓存 access_token 到磁盘
        $accessToken = $result['access_token'];
        $expiresIn   = $result['expires_in'] ?? 86400;
        $this->cache->set($this->cacheKey, $accessToken, $expiresIn);

        // 缓存 refresh_token
        if (!empty($result['refresh_token'])) {
            $refreshExpire = $result['refresh_token_expires_in'] ?? 31536000;
            $this->cache->set($this->refreshCacheKey, $result['refresh_token'], $refreshExpire);
        }

        return $accessToken;
    }

    /**
     * 从磁盘缓存获取Token
     */
    public function getToken(): string
    {
        $token = $this->cache->get($this->cacheKey);
        if ($token) {
            return $token;
        }
        throw new \Exception('Token不存在，请先完成授权');
    }

    /**
     * 刷新Token
     */
    public function refreshToken(): string
    {
        $refreshToken = $this->cache->get($this->refreshCacheKey);
        if (empty($refreshToken)) {
            throw new \Exception('无可用刷新令牌，请重新授权');
        }

        $params = [
            'client_id'     => $this->config['client_id'] ?? '',
            'client_secret' => $this->config['client_secret'] ?? '',
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken
        ];

        $result = $this->http->post(self::TOKEN_URL, $params);
        if (empty($result['access_token'])) {
            throw new \Exception('刷新Token失败：' . json_encode($result, JSON_UNESCAPED_UNICODE));
        }

        // 更新磁盘缓存
        $newToken    = $result['access_token'];
        $expiresIn   = $result['expires_in'] ?? 86400;

        $this->cache->set($this->cacheKey, $newToken, $expiresIn);

        if (!empty($result['refresh_token'])) {
            $refreshExpire = $result['refresh_token_expires_in'] ?? 31536000;
            $this->cache->set($this->refreshCacheKey, $result['refresh_token'], $refreshExpire);
        }

        return $newToken;
    }
}