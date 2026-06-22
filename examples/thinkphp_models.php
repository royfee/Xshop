<?php
/**
 * ThinkPHP 模型定义
 * 
 * 文件位置:
 * - app/common/model/XshopToken.php
 * - app/common/model/PddShop.php
 */

// ==================== Token 模型 ====================
// app/common/model/XshopToken.php

namespace app\common\model;

use think\Model;

/**
 * XShop Token 缓存模型
 */
class XshopToken extends Model
{
    protected $table = 'xshop_tokens';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;

    protected $schema = [
        'id' => 'int',
        'cache_key' => 'string',
        'platform' => 'string',
        'client_id' => 'string',
        'mall_id' => 'string',
        'access_token' => 'string',
        'refresh_token' => 'string',
        'expires_in' => 'int',
        'expire_at' => 'int',
        'cache_value' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 根据 cache_key 保存或更新
     */
    public function saveOrUpdate(array $data, array $uniqueKey = ['cache_key'])
    {
        $where = [];
        foreach ($uniqueKey as $key) {
            $where[$key] = $data[$key];
        }

        $exist = $this->where($where)->find();

        if ($exist) {
            return $this->where($where)->update($data);
        } else {
            return $this->save($data);
        }
    }
}


// ==================== 店铺模型 ====================
// app/common/model/PddShop.php

namespace app\common\model;

use think\Model;

/**
 * 拼多多店铺模型
 */
class PddShop extends Model
{
    protected $table = 'pdd_shops';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;

    protected $schema = [
        'id' => 'int',
        'mall_id' => 'string',
        'mall_name' => 'string',
        'client_id' => 'string',
        'platform' => 'string',
        'status' => 'int',
        'token_status' => 'string',
        'token_expire_at' => 'datetime',
        'remark' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 获取有效店铺列表
     */
    public function getValidList()
    {
        return $this->where('status', 1)
            ->where('token_status', 'valid')
            ->order('updated_at', 'desc')
            ->select();
    }

    /**
     * 根据 mall_id 获取店铺
     */
    public function getByMallId(string $mallId)
    {
        return $this->where('mall_id', $mallId)->find();
    }
}
