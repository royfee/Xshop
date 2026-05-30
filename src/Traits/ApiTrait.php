<?php
namespace royfee\xshop\Traits;

trait ApiTrait
{
    /**
     * 记录日志
     */
    protected function log($message, $level = 'info')
    {
        if ($this->config['log_enabled'] ?? false) {
            // 实现日志记录
            error_log("[{$level}] {$message}\n", 3, $this->config['log_path'] ?? '/tmp/shop.log');
        }
    }
}