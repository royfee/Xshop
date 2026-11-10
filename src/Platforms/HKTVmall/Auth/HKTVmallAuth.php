<?php

namespace Royfee\XShop\Platforms\HKTVmall\Auth;

use Royfee\XShop\Contracts\AuthInterface;
use Royfee\XShop\Cache\Cache;
use Psr\Log\LoggerInterface;

/**
 * HKTVmall RS256 JWT Authentication
 *
 * Generates JWT tokens signed with RSA private key.
 * 网关只接受 iat 在过去 30 分钟内的 token（实测 25 分钟可过、30 分钟 401），
 * 故 TTL 留出安全边际，读取时再校验一次 expires_at。
 * Cache key: token_hktvmall_{sha1(api_key|private_key)前16位}
 */
class HKTVmallAuth implements AuthInterface
{
    /** 距过期不足该秒数时视为需要重新签发 */
    const REFRESH_MARGIN = 120;

    protected $config;
    protected $cache;
    protected $logger;
    protected $platform = 'hktvmall';
    protected $tokenTtl = 1200;

    public function __construct(
        array $config,
        Cache $cache,
        ?LoggerInterface $logger = null
    ) {
        $this->config = $config;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    protected function buildCacheKey(): string
    {
        $apiKey = $this->config['api_key'] ?? 'default';
        // 网关按 iat 校验签名，凭证或私钥任一变化都必须重新签发，不能复用缓存
        $fingerprint = substr(sha1($apiKey . '|' . ($this->config['private_key'] ?? '')), 0, 16);
        return "token_{$this->platform}_{$fingerprint}";
    }

    protected function readCachedToken(): ?array
    {
        $cached = $this->cache->get($this->buildCacheKey());
        if (is_array($cached) && isset($cached['jwt'], $cached['expires_at'])
            && $cached['expires_at'] > time() + self::REFRESH_MARGIN) {
            return $cached;
        }

        return null;
    }

    public function getToken(?string $code = null): array
    {
        return $this->readCachedToken() ?? $this->generateAndCacheToken();
    }

    public function refreshToken(string $refreshToken): array
    {
        return $this->generateAndCacheToken();
    }

    public function getAuthorizeUrl(?string $redirectUri = null, ?string $state = null): string
    {
        return '';
    }

    public function isTokenValid(): bool
    {
        return $this->readCachedToken() !== null;
    }

    public function getJwtToken(): string
    {
        $token = $this->getToken();
        return $token['jwt'];
    }

    protected function generateAndCacheToken(): array
    {
        $jwt = $this->generateJwt();

        $tokenData = [
            'jwt' => $jwt,
            'iat' => time(),
            'expires_at' => time() + $this->tokenTtl,
            'api_key' => $this->config['api_key'] ?? '',
        ];

        $this->cache->set($this->buildCacheKey(), $tokenData, $this->tokenTtl);

        $this->log('info', 'JWT token generated and cached', [
            'ttl' => $this->tokenTtl,
        ]);

        return $tokenData;
    }

    public function generateJwt(): string
    {
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'sub' => $this->config['sub'] ?? 'shoalter',
            'name' => $this->config['name'] ?? 'shoalter',
            'iat' => time(),
            'x-api-key' => $this->config['api_key'] ?? '',
        ];

        $base64Header = $this->base64UrlEncode(json_encode($header));
        $base64Payload = $this->base64UrlEncode(json_encode($payload));

        $signingInput = $base64Header . '.' . $base64Payload;

        $privateKey = $this->loadPrivateKey();
        $signature = '';

        $keyResource = openssl_pkey_get_private(
            $privateKey,
            $this->config['private_key_passphrase'] ?? ''
        );

        if ($keyResource === false) {
            throw new \RuntimeException(
                'Failed to load RSA private key: ' . openssl_error_string()
            );
        }

        $success = openssl_sign($signingInput, $signature, $keyResource, OPENSSL_ALGO_SHA256);

        if (!$success) {
            throw new \RuntimeException(
                'Failed to sign JWT: ' . openssl_error_string()
            );
        }

        $base64Signature = $this->base64UrlEncode($signature);

        return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
    }

    protected function loadPrivateKey(): string
    {
        $key = $this->config['private_key'] ?? '';

        if (empty($key)) {
            throw new \RuntimeException('Private key is not configured for HKTVmall');
        }

        // 如果是文件路径，读取文件内容
        if (file_exists($key)) {
            $content = file_get_contents($key);
            if ($content === false) {
                throw new \RuntimeException("Failed to read private key file: {$key}");
            }
            $key = $content;
        }

        // 处理没有换行的私钥
        $key = $this->normalizePrivateKey($key);

        return $key;
    }

    /**
     * 标准化私钥格式，确保符合 PEM 要求
     * 处理情况：
     * 1. 纯 base64 内容（无 BEGIN/END 标记）
     * 2. 有标记但没有换行
     * 3. 字面量 \n 需要转换为真实换行
     */
    protected function normalizePrivateKey(string $key): string
    {
        // 先将字面量 \n 转换为真实换行
        $key = str_replace('\\n', "\n", $key);

        // 去除首尾空白
        $key = trim($key);

        // 检查是否包含 BEGIN 标记
        $hasBegin = strpos($key, '-----BEGIN') !== false;
        $hasEnd = strpos($key, '-----END') !== false;

        if (!$hasBegin && !$hasEnd) {
            // 纯 base64 内容，需要添加标记并格式化
            $base64Content = preg_replace('/\s+/', '', $key);
            $wrapped = wordwrap($base64Content, 64, "\n", true);
            return "-----BEGIN RSA PRIVATE KEY-----\n{$wrapped}\n-----END RSA PRIVATE KEY-----";
        }

        if ($hasBegin && $hasEnd) {
            // 有标记但可能格式不对，提取中间内容重新格式化
            if (preg_match('/-----BEGIN[^-]+-----(.+)-----END[^-]+-----/s', $key, $matches)) {
                $base64Content = preg_replace('/\s+/', '', $matches[1]);
                $wrapped = wordwrap($base64Content, 64, "\n", true);
                // 保留原始的 BEGIN 标记类型
                preg_match('/-----BEGIN ([^-]+)-----/', $key, $typeMatch);
                $type = $typeMatch[1] ?? 'RSA PRIVATE KEY';
                return "-----BEGIN {$type}-----\n{$wrapped}\n-----END {$type}-----";
            }
        }

        // 其他情况，直接返回
        return $key;
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->$level($message, $context);
        }
    }

    public function clearToken(): void
    {
        $this->cache->delete($this->buildCacheKey());
        $this->log('info', 'JWT token cleared');
    }
}
