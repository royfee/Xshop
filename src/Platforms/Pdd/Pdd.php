<?php

namespace Royfee\XShop\Platforms\Pdd;

use Royfee\XShop\Contracts\PlatformInterface;
use Royfee\XShop\Contracts\AuthInterface;
use Royfee\XShop\Contracts\OrderInterface;
use Royfee\XShop\Contracts\GoodsInterface;
use Royfee\XShop\Contracts\HttpClientInterface;
use Royfee\XShop\Container;
use Royfee\XShop\Platforms\Pdd\Auth\PddAuth;
use Royfee\XShop\Platforms\Pdd\Api\PddOrder;
use Royfee\XShop\Platforms\Pdd\Api\PddGoods;

/**
 * 拼多多平台主类
 * 
 * 统一注入 HttpClientInterface 到 Auth 和 API 模块
 */
class Pdd implements PlatformInterface
{
    /** @var array 平台配置 */
    protected $config;

    /** @var Container 服务容器 */
    protected $container;

    /** @var PddAuth|null 认证模块 */
    protected $authInstance = null;

    /** @var PddOrder|null 订单模块 */
    protected $orderInstance = null;

    /** @var PddGoods|null 商品模块 */
    protected $goodsInstance = null;

    /** @var HttpClientInterface|null HTTP客户端 */
    protected $httpInstance = null;

    public function __construct(array $config, Container $container)
    {
        $this->config = $config;
        $this->container = $container;
    }

    public function getName(): string
    {
        return '拼多多';
    }

    public function getIdentifier(): string
    {
        return 'pdd';
    }

    /**
     * 获取认证模块 (惰性加载)
     * 
     * 注入统一的 HTTP 客户端，与 API 模块保持一致
     */
    public function auth(): AuthInterface
    {
        if ($this->authInstance === null) {
            $this->authInstance = new PddAuth(
                $this->config,
                $this->container->get('cache'),
                $this->http(),  // 注入统一 HTTP 客户端
                $this->container->get('logger')
            );
        }
        return $this->authInstance;
    }

    /**
     * 获取订单模块 (惰性加载)
     */
    public function order(): OrderInterface
    {
        if ($this->orderInstance === null) {
            $this->orderInstance = new PddOrder(
                $this->config,
                $this->http(),
                $this->auth(),
                $this->container->get('logger')
            );
        }
        return $this->orderInstance;
    }

    /**
     * 获取商品模块 (惰性加载)
     */
    public function goods(): GoodsInterface
    {
        if ($this->goodsInstance === null) {
            $this->goodsInstance = new PddGoods(
                $this->config,
                $this->http(),
                $this->auth(),
                $this->container->get('logger')
            );
        }
        return $this->goodsInstance;
    }

    /**
     * 获取HTTP客户端 (惰性加载)
     */
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
