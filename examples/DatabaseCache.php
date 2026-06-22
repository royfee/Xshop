<?php
/**
 * 数据库缓存适配器 - 支持多店铺Token隔离存储
 * 
 * 单应用多店铺场景:
 * - 同一个 client_id 授权多个店铺
 * - 每个店铺有独立的 mall_id 和 access_token
 * - cache_key 格式: xshop_token_pdd_{client_id}_{mall_id}
 */

namespace App\Cache;

use Psr\SimpleCache\CacheInterface;
use PDO;

class DatabaseCache implements CacheInterface
{
    protected $pdo;
    protected $table = 'xshop_tokens';
    protected $prefix = 'xshop_';

    public function __construct(PDO $pdo, string $table = 'xshop_tokens', string $prefix = 'xshop_')
    {
        $this->pdo = $pdo;
        $this->table = $table;
        $this->prefix = $prefix;
    }

    protected function buildKey($key): string
    {
        return $this->prefix . $key;
    }

    /**
     * 解析 cache_key 提取 mall_id
     * 格式: xshop_token_pdd_client_id_mall_id
     */
    protected function parseMallId(string $cacheKey): ?string
    {
        $parts = explode('_', $cacheKey);
        // token_pdd_client_id_mall_id -> mall_id 是最后一部分
        return $parts[count($parts) - 1] ?? null;
    }

    public function get($key, $default = null)
    {
        $stmt = $this->pdo->prepare(
            "SELECT cache_value, expire_at FROM {$this->table} WHERE cache_key = ?"
        );
        $stmt->execute([$this->buildKey($key)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $default;
        }

        if ($row['expire_at'] < time()) {
            $this->delete($key);
            return $default;
        }

        $value = unserialize($row['cache_value']);
        return $value !== false ? $value : $default;
    }

    public function set($key, $value, $ttl = null)
    {
        $cacheKey = $this->buildKey($key);
        $expireAt = $ttl ? time() + $ttl : time() + 3600;
        $cacheValue = serialize($value);

        // 从 cache_key 解析 mall_id
        $mallId = $this->parseMallId($cacheKey) ?? '';
        $parts = explode('_', $key);
        $platform = $parts[1] ?? '';
        $clientId = $parts[2] ?? '';

        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} 
             (cache_key, platform, client_id, mall_id, cache_value, expire_at, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, NOW()) 
             ON DUPLICATE KEY UPDATE 
             cache_value = VALUES(cache_value), 
             expire_at = VALUES(expire_at),
             updated_at = NOW()"
        );

        return $stmt->execute([$cacheKey, $platform, $clientId, $mallId, $cacheValue, $expireAt]);
    }

    public function delete($key)
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->table} WHERE cache_key = ?"
        );
        return $stmt->execute([$this->buildKey($key)]);
    }

    public function clear()
    {
        return $this->pdo->exec("DELETE FROM {$this->table}") !== false;
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

    /**
     * 获取某个店铺的所有token (业务扩展方法)
     */
    public function getTokensByMallId(string $mallId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT cache_key, cache_value FROM {$this->table} WHERE mall_id = ? AND expire_at > ?"
        );
        $stmt->execute([$mallId, time()]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
