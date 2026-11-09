<?php

namespace Royfee\XShop\Platforms\HKTVmall;

use Royfee\XShop\Contracts\AuthInterface;
use Royfee\XShop\Contracts\OrderInterface;
use Royfee\XShop\Contracts\GoodsInterface;
use Royfee\XShop\Platforms\AbstractPlatform;
use Royfee\XShop\Platforms\HKTVmall\Auth\HKTVmallAuth;
use Royfee\XShop\Platforms\HKTVmall\Api\HKTVmallOrder;

class HKTVmall extends AbstractPlatform
{
    protected $authInstance = null;
    protected $orderInstance = null;

    protected $paramMap = [
        'page' => 'page',
        'page_size' => 'pageSize',
        'start_time' => 'orderDateStart',
        'end_time' => 'orderDateEnd',
        'status' => 'status',
    ];

    protected $statusMap = [
        'pending' => 'PENDING',
        'processing' => 'PROCESSING',
        'picked' => 'PICKED',
        'shipped' => 'SHIPPED',
        'delivered' => 'DELIVERED',
        'completed' => 'COMPLETED',
        'cancelled' => 'CANCELLED',
        'refunded' => 'REFUNDED',
    ];

    public function getName(): string
    {
        return 'HKTVmall';
    }

    public function getCode(): string
    {
        return 'hktvmall';
    }

    public function auth(): AuthInterface
    {
        if ($this->authInstance === null) {
            $this->authInstance = new HKTVmallAuth(
                $this->config,
                $this->container->get('cache'),
                $this->container->get('logger')
            );
        }
        return $this->authInstance;
    }

    public function order(): OrderInterface
    {
        if ($this->orderInstance === null) {
            $this->orderInstance = new HKTVmallOrder(
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
        throw new \RuntimeException('HKTVmall goods module is not implemented yet');
    }

    public static function getConfigSchema(): array
    {
        return [
            'base_url' => [
                'type' => 'string',
                'required' => false,
                'description' => 'API 基础地址',
                'example' => 'https://merchant-oapi.shoalter.com',
                'default' => 'https://merchant-oapi.shoalter.com',
            ],
            'store_code' => [
                'type' => 'string',
                'required' => true,
                'description' => '店铺代码 (Storefront Store Code)',
                'example' => 'H888888',
            ],
            'api_key' => [
                'type' => 'string',
                'required' => true,
                'description' => 'API Key (UUID格式，由平台分配)',
                'example' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
            ],
            'private_key' => [
                'type' => 'string',
                'required' => true,
                'description' => 'RSA 私钥 (文件路径或PEM内容)',
                'example' => '/path/to/private_key.pem',
            ]
        ];
    }
}
