<?php

namespace Royfee\XShop;

use Royfee\XShop\Contracts\PlatformInterface;
use Royfee\XShop\Platforms\Pdd\Pdd;

/**
 * XShop 核心工厂类
 * 
 * 多店铺支持 (mall_id 授权后才知):
 * 1. 授权前: $xshop->pdd() 创建临时实例，用 code 换 token
 * 2. 授权后: 从响应中提取 mall_id，用 $xshop->pdd(['mall_id' => 'xxx']) 创建正式实例
 * 3. 后续调用: 始终使用带 mall_id 的正式实例
 */
class XShop
{
    /** @var Container 服务容器 */
    protected $container;

    /** @var array 已注册的平台 */
    protected static $platforms = [
        'pdd' => Pdd::class,
    ];

    /** @var array 平台实例缓存 [instanceKey => instance] */
    protected $platformInstances = [];

    public function __construct($config = [])
    {
        if (is_string($config) && file_exists($config)) {
            $config = require $config;
        }

        $this->container = new Container($config);
    }

    public function container(): Container
    {
        return $this->container;
    }

    /**
     * 获取平台实例
     * 
     * 单店铺/授权前:
     *   $pdd = $xshop->platform('pdd');
     *   $token = $pdd->auth()->getToken('code'); // 从响应中拿到 mall_id
     * 
     * 多店铺/授权后 (指定 mall_id):
     *   $shopA = $xshop->platform('pdd', ['mall_id' => '4567890']);
     *   $orders = $shopA->order()->getList(['page' => 1]);
     * 
     * @param string $name 平台标识
     * @param array $overrideConfig 覆盖配置 (如 mall_id)
     * @return PlatformInterface
     */
    public function platform(string $name, array $overrideConfig = []): PlatformInterface
    {
        $platformConfig = $this->container->getPlatformConfig($name);

        if (empty($platformConfig) || !($platformConfig['enabled'] ?? false)) {
            throw new \RuntimeException("Platform not configured or disabled: {$name}");
        }

        if (!empty($overrideConfig)) {
            $platformConfig = array_merge($platformConfig, $overrideConfig);
        }

        // 实例缓存key: pdd 或 pdd_123456
        $mallId = $platformConfig['mall_id'] ?? 'pending';
        $instanceKey = "{$name}_{$mallId}";

        if (isset($this->platformInstances[$instanceKey])) {
            return $this->platformInstances[$instanceKey];
        }

        if (!isset(self::$platforms[$name])) {
            throw new \RuntimeException("Platform not registered: {$name}");
        }

        $class = self::$platforms[$name];
        $instance = new $class($platformConfig, $this->container);
        $this->platformInstances[$instanceKey] = $instance;

        return $instance;
    }

    /**
     * 获取拼多多平台实例
     * 
     * 授权前 (临时):
     *   $pdd = $xshop->pdd();
     *   $token = $pdd->auth()->getToken('code');
     *   $mallId = $token['owner_id']; // 从响应中提取
     * 
     * 授权后 (正式):
     *   $shop = $xshop->pdd(['mall_id' => $mallId]);
     *   $orders = $shop->order()->getList(['page' => 1]);
     * 
     * @param array $config 覆盖配置
     * @return Pdd
     */
    public function pdd(array $config = []): Pdd
    {
        return $this->platform('pdd', $config);
    }

    /**
     * 注册新平台
     */
    public static function registerPlatform(string $name, string $class): void
    {
        if (!is_subclass_of($class, PlatformInterface::class)) {
            throw new \InvalidArgumentException(
                "Platform class must implement " . PlatformInterface::class
            );
        }
        self::$platforms[$name] = $class;
    }

    public static function getRegisteredPlatforms(): array
    {
        return array_keys(self::$platforms);
    }

    public function getInstances(): array
    {
        return array_keys($this->platformInstances);
    }
}
