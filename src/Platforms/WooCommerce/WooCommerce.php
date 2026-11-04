<?php

namespace Royfee\XShop\Platforms\WooCommerce;

use Royfee\XShop\Contracts\AuthInterface;
use Royfee\XShop\Contracts\OrderInterface;
use Royfee\XShop\Contracts\GoodsInterface;
use Royfee\XShop\Platforms\AbstractPlatform;
use Royfee\XShop\Platforms\WooCommerce\Auth\WooCommerceAuth;
use Royfee\XShop\Platforms\WooCommerce\Api\WooCommerceOrder;

class WooCommerce extends AbstractPlatform
{
    protected $authInstance = null;
    protected $orderInstance = null;

    /**
     * 参数映射：[wc参数 => xshop参数]
     */
    protected $paramMap = [
        'page' => 'page',
        'page_size' => 'per_page',
        'status' => 'status',
        'start_time' => 'after',
        'end_time' => 'before',
    ];

    /**
     * 状态映射：[xshop状态 => wc状态]
     */
    protected $statusMap = [
        'pending' => 'pending',
        'paid' => 'processing',
        'shipped' => 'processing',
        'completed' => 'completed',
        'cancelled' => 'cancelled',
        'refunded' => 'refunded',
    ];

    public function getName(): string
    {
        return 'WooCommerce';
    }

    public function getCode(): string
    {
        return 'woocommerce';
    }

    public function auth(): AuthInterface
    {
        if ($this->authInstance === null) {
            $this->authInstance = new WooCommerceAuth(
                $this->config,
                $this->container->get('logger'),
                $this
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
                $this->container->get('logger'),
                $this
            );
        }
        return $this->orderInstance;
    }

    public function goods(): GoodsInterface
    {
        throw new \RuntimeException('WooCommerce goods module is not implemented yet');
    }

    /**
     * 获取 WooCommerce 平台配置参数 Schema
     *
     * @return array 配置参数定义
     */
    public static function getConfigSchema(): array
    {
        return [
            'site_url' => [
                'type' => 'string',
                'required' => true,
                'description' => 'WooCommerce 站点地址',
                'example' => 'https://your-site.com',
            ],
            'consumer_key' => [
                'type' => 'string',
                'required' => true,
                'description' => 'REST API Consumer Key',
                'example' => 'ck_xxxxxxxx',
            ],
            'consumer_secret' => [
                'type' => 'string',
                'required' => true,
                'description' => 'REST API Consumer Secret',
                'example' => 'cs_xxxxxxxx',
            ]
        ];
    }
}
