<?php

namespace Royfee\XShop\Mapper;

use Royfee\XShop\Contracts\MapperInterface;

/**
 * 映射基类
 * 所有平台映射器继承此类
 */
abstract class AbstractMapper implements MapperInterface
{
    /**
     * 批量映射
     * @param array $rawDataList
     * @return array
     */
    public function mapList(array $rawDataList): array
    {
        return array_map(function ($rawData) {
            return $this->map($rawData);
        }, $rawDataList);
    }

    /**
     * 安全获取数组值
     */
    protected function get(array $data, string $key, $default = null)
    {
        return $data[$key] ?? $default;
    }

    /**
     * 安全获取嵌套数组值
     */
    protected function getNested(array $data, string $path, $default = null)
    {
        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return $default;
            }
            $current = $current[$key];
        }

        return $current;
    }

    /**
     * 格式化金额 (分转元)
     */
    protected function fenToYuan($amount): float
    {
        if ($amount === null || $amount === '') {
            return 0.0;
        }
        return round((float) $amount / 100, 2);
    }

    /**
     * 格式化时间戳
     */
    protected function formatTime($timestamp): ?string
    {
        if (empty($timestamp)) {
            return null;
        }
        // 处理毫秒时间戳
        if (strlen((string) $timestamp) > 10) {
            $timestamp = (int) ($timestamp / 1000);
        }
        return date('Y-m-d H:i:s', (int) $timestamp);
    }
}
