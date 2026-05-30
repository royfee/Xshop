<?php
namespace royfee\xshop\Platforms;

use Hanson\Foundation\Foundation;
use royfee\xshop\Traits\ApiTrait;

/**
 * 平台基类 - 所有平台必须继承此类
 */
abstract class BasePlatform extends Foundation
{
    use ApiTrait;
    
    protected $accessToken;
    protected $config;
    
    public function __construct($config, $accessToken = null)
    {
        $this->config = $config;
        $this->accessToken = $accessToken;
        parent::__construct($config);
        $this->registerModules($this);
    }
    
    /**
     * 注册业务模块
     * @param $pimple
     */
    abstract public function registerModules($pimple);
    
    /**
     * 获取平台名称
     */
    abstract public function getPlatformName();
    
    /**
     * 发送请求
     * @param string $method API方法名
     * @param array $params 请求参数
     * @return array
     */
    abstract protected function request($method, array $params = []);
    
    /**
     * 生成签名
     */
    abstract protected function generateSign(array $params);
    
    /**
     * 魔法方法 - 获取模块实例
     */
    public function __get($property)
    {
        return $this->offsetGet($property);
    }

    /**
     * 是否开发模式
     */
    public function getDev(){
        return $this->config['is_dev'] ?? false;
    }
}