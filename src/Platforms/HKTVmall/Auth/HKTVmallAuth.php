<?php

namespace Royfee\XShop\Platforms\HKTVmall\Auth;

use Royfee\XShop\Contracts\AuthInterface;
use Royfee\XShop\Cache\Cache;
use Psr\Log\LoggerInterface;

/**
 * HKTVmall RS256 JWT Authentication
 *
 * Generates JWT tokens signed with RSA private key.
 * Token must be refreshed every 30 minutes (iat timestamp).
 * Cache key: token_hktvmall_{api_key_prefix}
 */
class HKTVmallAuth implements AuthInterface
{
    protected $config;
    protected $cache;
    protected $logger;
    protected $platform = 'hktvmall';
    protected $tokenTtl = 1500;

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
        $prefix = substr($apiKey, 0, 8);
        return "token_{$this->platform}_{$prefix}";
    }

    public function getToken(?string $code = null): array
    {
        $cached = $this->cache->get($this->buildCacheKey());
        if ($cached && is_array($cached) && isset($cached['jwt'])) {
            return $cached;
        }

        return $this->generateAndCacheToken();
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
        $cached = $this->cache->get($this->buildCacheKey());
        return $cached && is_array($cached) && isset($cached['jwt']);
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

        if (file_exists($key)) {
            $content = file_get_contents($key);
            if ($content === false) {
                throw new \RuntimeException("Failed to read private key file: {$key}");
            }
            return $content;
        }

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
