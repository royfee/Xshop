<?php

namespace Royfee\XShop\Cache;

use Psr\SimpleCache\CacheInterface;

/**
 * 文件缓存 - 默认缓存实现
 * 简单的文件缓存，支持 PSR-16
 */
class FileCache implements CacheInterface
{
    /** @var string 缓存目录 */
    protected $cacheDir;

    public function __construct(?string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?? sys_get_temp_dir() . '/xshop_cache';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    protected function getFilePath(string $key): string
    {
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        return $this->cacheDir . '/' . $safeKey . '.cache';
    }

    public function get($key, $default = null)
    {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return $default;
        }

        $data = unserialize(file_get_contents($file));
        if ($data === false || !is_array($data) || !isset($data['expire'], $data['value'])) {
            return $default;
        }

        if ($data['expire'] !== null && $data['expire'] < time()) {
            unlink($file);
            return $default;
        }

        return $data['value'];
    }

    public function set($key, $value, $ttl = null)
    {
        $file = $this->getFilePath($key);
        $expire = $ttl ? time() + $ttl : null;
        $data = ['expire' => $expire, 'value' => $value];

        return file_put_contents($file, serialize($data), LOCK_EX) !== false;
    }

    public function delete($key)
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    public function clear()
    {
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }

    public function getMultiple($keys, $default = null)
    {
        $result = [];
        foreach ((array) $keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple($values, $ttl = null)
    {
        foreach ((array) $values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple($keys)
    {
        foreach ((array) $keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function has($key)
    {
        return $this->get($key, $this) !== $this;
    }
}
