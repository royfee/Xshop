<?php

namespace Royfee\XShop\Tests;

use PHPUnit\Framework\TestCase;
use Royfee\XShop\XShop;

class XShopTest extends TestCase
{
    public function testContainer()
    {
        $config = [
            'debug' => true,
            'logger' => ['enabled' => false],
            'cache' => ['ttl' => 3600],
            'http' => ['timeout' => 10],
            'platforms' => [
                'pdd' => [
                    'enabled' => true,
                    'client_id' => 'test_app_id',
                    'client_secret' => 'test_secret',
                    'mall_id' => '123',
                ],
            ],
        ];

        $xshop = new XShop($config);

        $container = $xshop->container();
        $this->assertInstanceOf(\Royfee\XShop\Container::class, $container);

        $pdd = $xshop->platform('pdd');
        $this->assertInstanceOf(\Royfee\XShop\Platforms\Pdd\Pdd::class, $pdd);

        $this->assertSame($pdd, $xshop->pdd());
    }

    /**
     * 测试多店铺: 同一应用不同 mall_id 产生不同实例
     */
    public function testMultiShopInstances()
    {
        $config = [
            'debug' => true,
            'logger' => ['enabled' => false],
            'cache' => ['ttl' => 3600],
            'http' => ['timeout' => 10],
            'platforms' => [
                'pdd' => [
                    'enabled' => true,
                    'client_id' => 'same_app_id',
                    'client_secret' => 'same_secret',
                ],
            ],
        ];

        $xshop = new XShop($config);

        // 店铺A
        $shopA = $xshop->pdd(['mall_id' => '123456']);
        $this->assertInstanceOf(\Royfee\XShop\Platforms\Pdd\Pdd::class, $shopA);

        // 店铺B
        $shopB = $xshop->pdd(['mall_id' => '789012']);
        $this->assertInstanceOf(\Royfee\XShop\Platforms\Pdd\Pdd::class, $shopB);

        // 两个实例不同
        $this->assertNotSame($shopA, $shopB);

        // cache key 不同
        $this->assertNotEquals(
            $shopA->auth()->getCacheKey(),
            $shopB->auth()->getCacheKey()
        );

        // cache key 包含 mall_id
        $this->assertStringContainsString('123456', $shopA->auth()->getCacheKey());
        $this->assertStringContainsString('789012', $shopB->auth()->getCacheKey());

        // 同一 mall_id 返回缓存实例
        $shopA2 = $xshop->pdd(['mall_id' => '123456']);
        $this->assertSame($shopA, $shopA2);
    }

    public function testDataMapping()
    {
        $mapper = new \Royfee\XShop\Platforms\Pdd\Mapper\PddOrderMapper();

        $rawOrder = [
            'order_sn' => '123456789',
            'mall_id' => '123',
            'buyer_id' => '456',
            'buyer_nickname' => 'test_user',
            'order_status' => 1,
            'pay_amount' => 10000,
            'goods_amount' => 9000,
            'postage' => 1000,
            'discount_amount' => 0,
            'pay_time' => time(),
            'created_time' => time(),
            'receiver_name' => '张三',
            'receiver_phone' => '13800138000',
            'province' => '广东省',
            'city' => '深圳市',
            'district' => '南山区',
            'address' => '科技园',
            'remark' => '请尽快发货',
            'item_list' => [
                [
                    'goods_id' => '10001',
                    'sku_id' => '20001',
                    'goods_name' => '测试商品',
                    'goods_count' => 2,
                    'goods_price' => 5000,
                    'goods_amount' => 10000,
                ],
            ],
        ];

        $order = $mapper->map($rawOrder);

        $this->assertEquals('123456789', $order->orderId);
        $this->assertEquals('pdd', $order->platform);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals('待发货', $order->statusText);
        $this->assertEquals(100.0, $order->totalAmount);
        $this->assertEquals(90.0, $order->goodsAmount);
        $this->assertEquals(1, count($order->items));
        $this->assertEquals('测试商品', $order->items[0]->goodsName);
    }
}
