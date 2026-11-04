<?php

namespace Royfee\XShop\Platforms\Pdd;

use Royfee\XShop\Contracts\AuthInterface;
use Royfee\XShop\Contracts\OrderInterface;
use Royfee\XShop\Contracts\GoodsInterface;
use Royfee\XShop\Platforms\AbstractPlatform;
use Royfee\XShop\Platforms\Pdd\Auth\PddAuth;
use Royfee\XShop\Platforms\Pdd\Api\PddOrder;
use Royfee\XShop\Platforms\Pdd\Api\PddGoods;

/**
 * 拼多多平台主类
 * 
 * 统一注入 HttpClientInterface 到 Auth 和 API 模块
 */
class Pdd extends AbstractPlatform
{
    /** @var PddAuth|null 认证模块 */
    protected $authInstance = null;

    /** @var PddOrder|null 订单模块 */
    protected $orderInstance = null;

    /** @var PddGoods|null 商品模块 */
    protected $goodsInstance = null;

    /**
     * 参数映射：[pdd参数 => xshop参数]
     */
    protected $paramMap = [
        'page' => 'page',
        'page_size' => 'page_size',
        'status' => 'order_status',
        'start_time' => 'start_confirm_time',
        'end_time' => 'end_confirm_time',
    ];

    /**
     * 状态映射：[xshop状态 => pdd状态]
     */
    protected $statusMap = [
        'pending' => 1,
        'paid' => 1,
        'shipped' => 2,
        'completed' => 5,
        'cancelled' => 6,
        'refunded' => 4,
    ];

    public function getName(): string
    {
        return '拼多多';
    }

    public function getCode(): string
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
                $this->http(),
                $this->container->get('logger'),
                $this
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
                $this->container->get('logger'),
                $this
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
                $this->container->get('logger'),
                $this
            );
        }
        return $this->goodsInstance;
    }

    /**
     * 获取拼多多平台配置参数 Schema
     *
     * @return array 配置参数定义
     */
    public static function getConfigSchema(): array
    {
        return [
            'client_id' => [
                'type' => 'string',
                'required' => true,
                'description' => '拼多多开放平台应用 ID',
                'example' => '12345678',
            ],
            'client_secret' => [
                'type' => 'string',
                'required' => true,
                'description' => '拼多多开放平台应用密钥',
                'example' => 'your_client_secret',
            ],
            'redirect_uri' => [
                'type' => 'string',
                'required' => false,
                'description' => 'OAuth 授权回调地址',
                'example' => 'https://your-site.com/callback',
            ],
            'api_url' => [
                'type' => 'string',
                'required' => false,
                'default' => 'https://gw-api.pinduoduo.com/api/router',
                'description' => '拼多多 API 接口地址',
            ],
            'auth_url' => [
                'type' => 'string',
                'required' => false,
                'default' => 'https://open-api.pinduoduo.com/oauth/token',
                'description' => '拼多多 OAuth 授权地址',
            ],
            'mall_id' => [
                'type' => 'string',
                'required' => false,
                'description' => '店铺 ID (授权后自动获取)',
                'example' => '4567890',
            ],
        ];
    }
}
