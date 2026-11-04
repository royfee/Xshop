<?php

namespace Royfee\XShop\Platforms\WooCommerce;

use Royfee\XShop\Contracts\PlatformInterface;
use Royfee\XShop\Contracts\AuthInterface;
use Royfee\XShop\Contracts\OrderInterface;
use Royfee\XShop\Contracts\GoodsInterface;
use Royfee\XShop\Contracts\HttpClientInterface;
use Royfee\XShop\Container;
use Royfee\XShop\Platforms\WooCommerce\Auth\WooCommerceAuth;
use Royfee\XShop\Platforms\WooCommerce\Api\WooCommerceOrder;

class WooCommerce implements PlatformInterface
{
    protected $config;
    protected $container;
    protected $authInstance = null;
    protected $orderInstance = null;
    protected $httpInstance = null;

    public function __construct(array $config, Container $container)
    {
        $this->config = $config;
        $this->container = $container;
    }

    public function getName(): string
    {
        return 'WooCommerce';
    }

    public function getIdentifier(): string
    {
        return 'woocommerce';
    }

    public function auth(): AuthInterface
    {
        if ($this->authInstance === null) {
            $this->authInstance = new WooCommerceAuth(
                $this->config,
                $this->container->get('logger')
            );
        }
        return $this->authInstance;
    }

    public function order(): OrderInterface
    {
        if ($this->orderInstance === null) {
            $this->orderInstance = new WooCommerceOrder(
                $this->config,
                $this->http(),
                $this->auth(),
                $this->container->get('logger')
            );
        }
        return $this->orderInstance;
    }

    public function goods(): GoodsInterface
    {
        throw new \RuntimeException('WooCommerce goods module is not implemented yet');
    }

    public function http(): HttpClientInterface
    {
        if ($this->httpInstance === null) {
            $this->httpInstance = $this->container->get('http');
        }
        return $this->httpInstance;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }
}
