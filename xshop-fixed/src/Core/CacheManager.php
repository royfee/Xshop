<?php
namespace Royfee\XShop\Core;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class CacheManager implements CacheInterface
{
    /**
     * 缓存根目录
     */
    protected string $cacheDir;

    /**
     * 缓存文件后缀
     */
    protected string $fileExt = '.cache';

    public function __construct()
    {
        // 定义缓存目录：项目根目录/runtime/cache
        $this->cacheDir = dirname(__DIR__, 2) . '/runtime/cache/';
        $this->initDir();
    }

    /**
     * 初始化目录，不存在则创建
     */
    protected function initDir(): void
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * 获取缓存文件完整路径
     */
    protected function getFilepath(string $key): string
    {
        // 文件名做安全处理，避免特殊字符
        $safeKey = md5($key);
        return $this->cacheDir . $safeKey . $this->fileExt;
    }

    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        $file = $this->getFilepath($key);

        // 文件不存在直接返回默认值
        if (!file_exists($file)) {
            return $default;
        }

        $content = file_get_contents($file);
        $data = unserialize($content);

        // 格式校验
        if (!is_array($data) || !isset($data['value'], $data['expire'])) {
            $this->delete($key);
            return $default;
        }

        // 判断是否过期
        if ($data['expire'] > 0 && time() > $data['expire']) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null): bool
    {
        $file = $this->getFilepath($key);
        $expire = 0;

        // 计算过期时间，ttl 单位：秒
        if ($ttl !== null && $ttl > 0) {
            $expire = time() + (int)$ttl;
        }

        $data = [
            'value'  => $value,
            'expire' => $expire
        ];

        return file_put_contents($file, serialize($data)) !== false;
    }

    /**
     * @inheritDoc
     */
    public function delete($key): bool
    {
        $file = $this->getFilepath($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        $files = glob($this->cacheDir . '*' . $this->fileExt);
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function has($key): bool
    {
        return $this->get($key) !== null;
    }
}