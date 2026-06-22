<?php
/**
 * ThinkPHP 数据库迁移
 * 
 * 使用 think-migration 插件:
 * composer require topthink/think-migration
 * 
 * 执行迁移:
 * php think migrate:run
 */

use think\migration\Migrator;
use think\migration\db\Column;

class CreateXshopTables extends Migrator
{
    public function change()
    {
        // 店铺表
        $table = $this->table('pdd_shops', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci', 'comment' => '拼多多店铺表']);
        $table->addColumn('mall_id', 'string', ['limit' => 50, 'null' => false, 'comment' => '拼多多店铺ID(owner_id)'])
            ->addColumn('mall_name', 'string', ['limit' => 255, 'null' => true, 'default' => null, 'comment' => '店铺名称'])
            ->addColumn('client_id', 'string', ['limit' => 255, 'null' => false, 'comment' => '应用ID'])
            ->addColumn('platform', 'string', ['limit' => 20, 'null' => false, 'default' => 'pdd', 'comment' => '平台标识'])
            ->addColumn('status', 'integer', ['limit' => 1, 'null' => false, 'default' => 1, 'comment' => '店铺状态: 1-正常 0-禁用'])
            ->addColumn('token_status', 'string', ['limit' => 20, 'null' => true, 'default' => 'invalid', 'comment' => 'token状态: valid-有效 invalid-无效 expired-过期'])
            ->addColumn('token_expire_at', 'datetime', ['null' => true, 'default' => null, 'comment' => 'token过期时间'])
            ->addColumn('remark', 'string', ['limit' => 500, 'null' => true, 'default' => null, 'comment' => '备注'])
            ->addTimestamps()
            ->addIndex(['mall_id'], ['unique' => true, 'name' => 'uk_mall_id'])
            ->addIndex(['client_id'], ['name' => 'idx_client_id'])
            ->addIndex(['status'], ['name' => 'idx_status'])
            ->addIndex(['token_status'], ['name' => 'idx_token_status'])
            ->create();

        // Token 缓存表
        $table = $this->table('xshop_tokens', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci', 'comment' => 'Token缓存表']);
        $table->addColumn('cache_key', 'string', ['limit' => 255, 'null' => false, 'comment' => '缓存key'])
            ->addColumn('platform', 'string', ['limit' => 20, 'null' => false, 'default' => 'pdd', 'comment' => '平台标识'])
            ->addColumn('client_id', 'string', ['limit' => 255, 'null' => false, 'comment' => '应用ID'])
            ->addColumn('mall_id', 'string', ['limit' => 50, 'null' => false, 'comment' => '店铺ID(owner_id)'])
            ->addColumn('access_token', 'text', ['null' => false, 'comment' => '访问令牌'])
            ->addColumn('refresh_token', 'text', ['null' => true, 'comment' => '刷新令牌'])
            ->addColumn('expires_in', 'integer', ['limit' => 11, 'null' => true, 'default' => 3600, 'comment' => '有效期(秒)'])
            ->addColumn('expire_at', 'integer', ['limit' => 11, 'null' => false, 'comment' => '过期时间戳'])
            ->addColumn('cache_value', 'text', ['null' => true, 'comment' => '序列化的完整token数据'])
            ->addTimestamps()
            ->addIndex(['cache_key'], ['unique' => true, 'name' => 'uk_cache_key'])
            ->addIndex(['mall_id'], ['name' => 'idx_mall_id'])
            ->addIndex(['client_id'], ['name' => 'idx_client_id'])
            ->addIndex(['expire_at'], ['name' => 'idx_expire_at'])
            ->create();
    }
}
