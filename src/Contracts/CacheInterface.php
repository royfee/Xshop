<?php

namespace Royfee\XShop\Contracts;

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;

/**
 * 缓存接口 - 继承PSR-16标准
 */
interface CacheInterface extends PsrCacheInterface
{
    /**
     * 获取缓存key前缀
     * @return string
     */
    public function getPrefix(): string;
}
