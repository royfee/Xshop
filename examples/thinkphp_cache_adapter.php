<?php
/**
 * ThinkPHP 模型实现的 XShop 缓存适配器
 * 
 * 使用 ThinkPHP 的模型操作数据库，实现 PSR-16 CacheInterface
 * 自动维护 token，与店铺表关联
 */

namespace app\common\cache;

use Psr\SimpleCache\CacheInterface;
use app\common\model\XshopToken;    // Token 模型
use app\common\model\PddShop;        // 店铺模型

/**
 * ThinkPHP 数据库缓存适配器
 * 
 * 表结构:
 * - pdd_shops: 店铺信息表 (mall_id, mall_name, client_id, status, token_status, ...)
 * - xshop_tokens: token缓存表 (cache_key, mall_id, access_token, refresh_token, expire_at, ...)
 */
class ThinkPhpDbCache implements CacheInterface
{
    /** @var string key前缀 */
    protected $prefix = 'xshop_';

    /** @var XshopToken Token模型 */
    protected $tokenModel;

    /** @var PddShop 店铺模型 */
    protected $shopModel;

    public function __construct()
    {
        $this->tokenModel = new XshopToken();
        $this->shopModel = new PddShop();
    }

    /**
     * 获取缓存值
     * 
     * @param string $key 缓存key (如: token_pdd_clientId_mallId)
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $cacheKey = $this->prefix . $key;

        $row = $this->tokenModel
            ->where('cache_key', $cacheKey)
            ->where('expire_at', '>', time())
            ->find();

        if (!$row) {
            return $default;
        }

        $value = unserialize($row->cache_value);
        return $value !== false ? $value : $default;
    }

    /**
     * 设置缓存值 (保存token)
     * 
     * 自动:
     * 1. 保存token到 xshop_tokens 表
     * 2. 同步更新 pdd_shops 表的token状态
     * 
     * @param string $key 缓存key
     * @param mixed $value token数据数组
     * @param int|null $ttl 过期时间(秒)
     * @return bool
     */
    public function set($key, $value, $ttl = null)
    {
        $cacheKey = $this->prefix . $key;
        $expireAt = time() + ($ttl ?? 7000);
        $cacheValue = serialize($value);

        // 解析 mall_id 从 key
        // key 格式: token_pdd_{client_id}_{mall_id}
        $parts = explode('_', $key);
        $mallId = $parts[3] ?? '';
        $clientId = $parts[2] ?? '';

        // 提取token信息
        $accessToken = is_array($value) ? ($value['access_token'] ?? '') : '';
        $refreshToken = is_array($value) ? ($value['refresh_token'] ?? '') : '';
        $expiresIn = is_array($value) ? ($value['expires_in'] ?? 3600) : 3600;

        try {
            // 1. 保存/更新 token
            $this->tokenModel->saveOrUpdate([
                'cache_key' => $cacheKey,
                'mall_id' => $mallId,
                'client_id' => $clientId,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_in' => $expiresIn,
                'expire_at' => $expireAt,
                'cache_value' => $cacheValue,
            ], ['cache_key']);

            // 2. 同步更新店铺表 (如果mall_id有效)
            if ($mallId && $mallId !== 'pending' && $mallId !== 'default') {
                $this->syncShop($mallId, $clientId, $expireAt);
            }

            return true;

        } catch (\Exception $e) {
            trace("Cache set error: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * 同步更新店铺表
     */
    protected function syncShop(string $mallId, string $clientId, int $expireAt): void
    {
        $shop = $this->shopModel->where('mall_id', $mallId)->find();

        if ($shop) {
            // 更新现有店铺
            $shop->client_id = $clientId;
            $shop->token_status = 'valid';
            $shop->token_expire_at = date('Y-m-d H:i:s', $expireAt);
            $shop->save();
        } else {
            // 创建新店铺记录
            $this->shopModel->save([
                'mall_id' => $mallId,
                'client_id' => $clientId,
                'status' => 1,
                'token_status' => 'valid',
                'token_expire_at' => date('Y-m-d H:i:s', $expireAt),
            ]);
        }
    }

    /**
     * 删除缓存 (删除token)
     * 
     * 同时更新店铺表的token状态为 invalid
     */
    public function delete($key)
    {
        $cacheKey = $this->prefix . $key;

        // 解析 mall_id
        $parts = explode('_', $key);
        $mallId = $parts[3] ?? '';

        try {
            // 删除token
            $this->tokenModel->where('cache_key', $cacheKey)->delete();

            // 更新店铺状态
            if ($mallId && $mallId !== 'pending' && $mallId !== 'default') {
                $shop = $this->shopModel->where('mall_id', $mallId)->find();
                if ($shop) {
                    $shop->token_status = 'invalid';
                    $shop->save();
                }
            }

            return true;

        } catch (\Exception $e) {
            trace("Cache delete error: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * 清空所有缓存
     */
    public function clear()
    {
        try {
            $this->tokenModel->where('id', '>', 0)->delete();

            // 更新所有店铺token状态
            $this->shopModel->where('id', '>', 0)->update([
                'token_status' => 'invalid',
            ]);

            return true;
        } catch (\Exception $e) {
            trace("Cache clear error: " . $e->getMessage(), 'error');
            return false;
        }
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

    // ==================== 业务扩展方法 ====================

    /**
     * 获取指定店铺的有效token
     * 
     * @param string $mallId 店铺ID
     * @return array|null
     */
    public function getTokenByMallId(string $mallId): ?array
    {
        $row = $this->tokenModel
            ->where('mall_id', $mallId)
            ->where('expire_at', '>', time())
            ->order('updated_at', 'desc')
            ->find();

        if (!$row) {
            return null;
        }

        $value = unserialize($row->cache_value);
        return $value !== false ? $value : null;
    }

    /**
     * 获取所有有效店铺列表
     * 
     * @return array
     */
    public function getValidShops(): array
    {
        return $this->shopModel
            ->alias('s')
            ->join('xshop_tokens t', 's.mall_id = t.mall_id')
            ->where('t.expire_at', '>', time())
            ->where('s.status', 1)
            ->field('s.*, t.expire_at')
            ->select()
            ->toArray();
    }

    /**
     * 获取即将过期的token (用于定时刷新)
     * 
     * @param int $threshold 提前多少秒认为即将过期 (默认300秒=5分钟)
     * @return array
     */
    public function getExpiringTokens(int $threshold = 300): array
    {
        return $this->shopModel
            ->alias('s')
            ->join('xshop_tokens t', 's.mall_id = t.mall_id')
            ->where('t.expire_at', '<=', time() + $threshold)
            ->where('t.expire_at', '>', time())
            ->where('s.status', 1)
            ->field('s.*, t.cache_key, t.expire_at, t.refresh_token')
            ->select()
            ->toArray();
    }

    /**
     * 获取店铺信息
     * 
     * @param string $mallId
     * @return array|null
     */
    public function getShopInfo(string $mallId): ?array
    {
        $shop = $this->shopModel->where('mall_id', $mallId)->find();
        return $shop ? $shop->toArray() : null;
    }

    /**
     * 获取店铺列表 (支持筛选)
     * 
     * @param array $filters
     * @return array
     */
    public function getShopList(array $filters = []): array
    {
        $query = $this->shopModel->where('id', '>', 0);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['token_status'])) {
            $query->where('token_status', $filters['token_status']);
        }

        return $query->order('updated_at', 'desc')->select()->toArray();
    }

    /**
     * 更新店铺信息
     * 
     * @param string $mallId
     * @param array $data
     * @return bool
     */
    public function updateShop(string $mallId, array $data): bool
    {
        $shop = $this->shopModel->where('mall_id', $mallId)->find();
        if (!$shop) {
            return false;
        }

        $allowed = ['mall_name', 'status', 'remark', 'token_status'];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $shop->$key = $value;
            }
        }

        return $shop->save();
    }

    /**
     * 删除店铺及其token
     * 
     * @param string $mallId
     * @return bool
     */
    public function deleteShop(string $mallId): bool
    {
        try {
            // 删除token
            $this->tokenModel->where('mall_id', $mallId)->delete();

            // 删除店铺
            $this->shopModel->where('mall_id', $mallId)->delete();

            return true;
        } catch (\Exception $e) {
            trace("Delete shop error: " . $e->getMessage(), 'error');
            return false;
        }
    }
}
