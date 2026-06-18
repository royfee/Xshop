<?php
namespace royfee\xshop\Tests;

use PHPUnit\Framework\TestCase;
use royfee\xshop\Shop;
use royfee\xshop\Platforms\Taobao\Taobao;
use royfee\xshop\Platforms\Yueyan\Yueyan;

/**
 * 基础契约测试, 确保 Shop 入口、平台注册、命名空间解析 OK.
 */
class ShopTest extends TestCase
{
    public function testDefaultShopHasNoPlatforms(): void
    {
        $shop = new Shop();
        $this->assertSame([], $shop->getPlatforms());
    }

    public function testRegisterPlatformsFromArrayConfig(): void
    {
        $shop = new Shop([
            'platforms' => [
                'taobao' => [
                    'class'  => Taobao::class,
                    'config' => [
                        'app_key'    => 'fake_key',
                        'app_secret' => 'fake_secret',
                    ],
                ],
            ],
        ]);

        $this->assertTrue($shop->hasPlatform('taobao'));
        $this->assertFalse($shop->hasPlatform('youzan'));
        $this->assertInstanceOf(Taobao::class, $shop->taobao);
    }

    public function testAccessUnknownPlatformThrows(): void
    {
        $shop = new Shop();
        $this->expectException(\InvalidArgumentException::class);
        $shop->notExist;
    }

    public function testAddPlatformAtRuntime(): void
    {
        $shop = new Shop();
        $shop->addPlatform('yueyan', Yueyan::class, [
            'app_id'     => 'fake',
            'app_secret' => 'fake',
            'auth_code'  => 'fake',
        ]);

        $this->assertTrue($shop->hasPlatform('yueyan'));
        $this->assertInstanceOf(Yueyan::class, $shop->yueyan);
    }

    public function testPlatformMapperDefaultsToNull(): void
    {
        $shop = new Shop([
            'platforms' => [
                'taobao' => [
                    'class'  => Taobao::class,
                    'config' => ['app_key' => 'k', 'app_secret' => 's'],
                ],
            ],
        ]);

        $this->assertNull($shop->taobao->getMapper());
    }
}
