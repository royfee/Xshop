<?php
namespace royfee\xshop;

use royfee\xshop\Config\ConfigManager;
use royfee\xshop\Traits\PlatformTrait;

/**
 * 统一入口类 - 支持多平台电商SDK
 * 
 * 使用方式：
 * $shop = new Xshop('/path/to/config/shop.php');
 * $shop->taobao->goods->getList(['keyword' => '手机']);
 * $shop->jd->order->getDetail('order_id');
 * $shop->pdd->goods->getDetail('goods_id');
 */
class Shop {
    use PlatformTrait;
    
    /**
     * @var ConfigManager 配置管理器
     */
    protected $config;
    
    /**
     * @var array 平台实例缓存
     */
    protected $instances = [];
    
    /**
     * 构造函数
     * @param string|array $config 配置文件路径或配置数组
     */
    public function __construct($config = null)
    {
        $this->config = new ConfigManager($config ?: $this->getDefaultConfig());
        $this->initializePlatforms();
    }
    
    /**
     * 初始化所有平台
     */
    protected function initializePlatforms()
    {
        $platforms = $this->config->get('platforms', []);
        foreach ($platforms as $name => $platformConfig) {
            $this->registerPlatform($name, $platformConfig);
        }
    }
    
    /**
     * 注册单个平台
     * @param string $name 平台名称
     * @param array $config 平台配置
     */
    protected function registerPlatform($name, $config)
    {
        if (!isset($config['class']) || !class_exists($config['class'])) {
            throw new \InvalidArgumentException("平台 {$name} 的类 {$config['class']} 不存在");
        }

        // 合并全局配置
        $globalConfig = $this->config->get('global', []);

        $fullConfig = array_merge($globalConfig, $config['config'] ?? []);

        // 实例化平台
        $this->instances[$name] = new $config['class']($fullConfig, $config['config']['access_token'] ?? null);
    }
    
    /**
     * 魔法方法 - 获取平台实例
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }
        
        throw new \InvalidArgumentException("平台 {$name} 未注册");
    }
    
    /**
     * 获取默认配置（可自定义）
     */
    protected function getDefaultConfig()
    {
        return [
            'platforms' => [],
            'global' => []
        ];
    }
    
    /**
     * 动态添加平台（运行时）
     * @param string $name
     * @param string $className
     * @param array $config
     * @return $this
     */
    public function addPlatform($name, $className, array $config)
    {
        $this->registerPlatform($name, [
            'class' => $className,
            'config' => $config
        ]);
        
        return $this;
    }
}