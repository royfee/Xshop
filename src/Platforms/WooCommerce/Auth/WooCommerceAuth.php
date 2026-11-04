<?php

namespace Royfee\XShop\Platforms\WooCommerce\Auth;

use Royfee\XShop\Contracts\AuthInterface;
use Psr\Log\LoggerInterface;

/**
 * WooCommerce HTTP Basic Authentication
 *
 * WooCommerce REST API v3 uses consumer key / consumer secret
 * as HTTP Basic Auth credentials. No OAuth flow or token refresh.
 */
class WooCommerceAuth implements AuthInterface
{
    protected $config;
    protected $logger;

    public function __construct(array $config, ?LoggerInterface $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function getToken(?string $code = null): array
    {
        return [
            'consumer_key' => $this->config['consumer_key'] ?? '',
            'consumer_secret' => $this->config['consumer_secret'] ?? '',
        ];
    }

    public function refreshToken(string $refreshToken): array
    {
        return $this->getToken();
    }

    public function getAuthorizeUrl(?string $redirectUri = null, ?string $state = null): string
    {
        return '';
    }

    public function isTokenValid(): bool
    {
        return !empty($this->config['consumer_key']) && !empty($this->config['consumer_secret']);
    }

    public function getBasicAuthHeader(): string
    {
        $credentials = $this->config['consumer_key'] . ':' . $this->config['consumer_secret'];
        return 'Basic ' . base64_encode($credentials);
    }

    public function getConsumerKey(): string
    {
        return $this->config['consumer_key'] ?? '';
    }

    public function getConsumerSecret(): string
    {
        return $this->config['consumer_secret'] ?? '';
    }
}
