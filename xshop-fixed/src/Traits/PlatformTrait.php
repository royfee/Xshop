<?php
namespace royfee\xshop\Traits;

/**
 * 平台管理能力 (注册/查询)
 */
trait PlatformTrait
{
    /**
     * 平台是否存在
     */
    public function hasPlatform(string $name): bool
    {
        return isset($this->instances[$name]);
    }

    /**
     * 获取所有已注册的平台名
     * @return string[]
     */
    public function getPlatforms(): array
    {
        return array_keys($this->instances);
    }
}
