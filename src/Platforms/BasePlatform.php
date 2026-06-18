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
     * 获取平台Mapper
     */
	public function getMapper(){
		$mapper = __NAMESPACE__.'\\'.ucfirst($this->getPlatformName()).'\\Mapper\\OrderMapper';
		return new $mapper;
	}
    
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

    public function getConfig(){
        return $this->config;
    }

    /**
     * 是否开发模式
     */
    public function success($data,$message = ''){
        return [
            'code'      =>  0,
            'message'   =>  $message,
            'data'      =>  $data,
        ];
    }

    public function error($message = ''){
        return [
            'code'      =>  1,
            'message'   =>  $message,
            'data'      =>  null,
        ];
    }
}