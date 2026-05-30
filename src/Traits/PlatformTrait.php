<?php
namespace royfee\xshop\Traits;

trait PlatformTrait
{
    /**
     * 获取所有已注册的平台
     */
    public function getPlatforms()
    {
        return array_keys($this->instances);
    }
    
    /**
     * 切换默认平台
     */
    public function setDefaultPlatform($platform)
    {
        if (!isset($this->instances[$platform])) {
            throw new \InvalidArgumentException("平台 {$platform} 未注册");
        }
        $this->defaultPlatform = $platform;
        return $this;
    }
}