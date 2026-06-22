<?php
namespace Royfee\XShop\Cache;

use Psr\SimpleCache\CacheInterface as PsrCacheInterface;
use Royfee\XShop\Contracts\CacheInterface;

/**
 * 缓存管理器
 * 
 * 支持自定义缓存适配器:
 * 1. 传入类名字符串 (自动实例化)
 * 2. 传入实例对象 (直接使用)
 * 
 * 自定义适配器必须实现 Psr\SimpleCache\CacheInterface (PSR-16)
 */
class Cache implements CacheInterface
{
    /** @var PsrCacheInterface 缓存实例 */
    protected $handler;

    /** @var string 缓存key前缀 */
    protected $prefix = 'xshop_';

    /** @var string|null 缓存目录 */
    protected $cacheDir;

    /**
     * @param PsrCacheInterface|string|null $handler 缓存处理器 (实例/类名/null使用默认)
     * @param string $prefix key前缀
     * @param string|null $cacheDir 文件缓存目录
     */
    public function __construct($handler = null, string $prefix = 'xshop_', ?string $cacheDir = null)
    {
        $this->prefix = $prefix;
        $this->cacheDir = $cacheDir;

        if ($handler instanceof PsrCacheInterface) {
            // 传入实例对象
            $this->handler = $handler;
        } elseif (is_string($handler) && class_exists($handler)) {
            // 传入类名字符串，自动实例化
            $instance = new $handler();
            if ($instance instanceof PsrCacheInterface) {
                $this->handler = $instance;
            } else {
                throw new \InvalidArgumentException(
                    "Cache handler must implement Psr\\SimpleCache\\CacheInterface"
                );
            }
        } else {
            // 使用默认文件缓存
            $this->handler = new FileCache($cacheDir);
        }
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    protected function buildKey(string $key): string
    {
        return $this->prefix . $key;
    }

    public function get($key, $default = null)
    {
        return $this->handler->get($this->buildKey($key), $default);
    }

    public function set($key, $value, $ttl = null)
    {
        return $this->handler->set($this->buildKey($key), $value, $ttl);
    }

    public function delete($key)
    {
        return $this->handler->delete($this->buildKey($key));
    }

    public function clear()
    {
        return $this->handler->clear();
    }

    public function getMultiple($keys, $default = null)
    {
        $prefixedKeys = array_map([$this, 'buildKey'], (array) $keys);
        return $this->handler->getMultiple($prefixedKeys, $default);
    }

    public function setMultiple($values, $ttl = null)
    {
        $prefixedValues = [];
        foreach ((array) $values as $key => $value) {
            $prefixedValues[$this->buildKey($key)] = $value;
        }
        return $this->handler->setMultiple($prefixedValues, $ttl);
    }

    public function deleteMultiple($keys)
    {
        $prefixedKeys = array_map([$this, 'buildKey'], (array) $keys);
        return $this->handler->deleteMultiple($prefixedKeys);
    }

    public function has($key)
    {
        return $this->handler->has($this->buildKey($key));
    }
}
