<?php

namespace Royfee\XShop\Contracts;

/**
 * 数据映射接口 - 将平台原始数据映射为XShop统一格式
 */
interface MapperInterface
{
    /**
     * 映射为统一格式
     * @param array $rawData 平台原始数据
     * @return mixed 统一格式的数据对象
     */
    public function map(array $rawData);

    /**
     * 批量映射
     * @param array $rawDataList 平台原始数据列表
     * @return array 统一格式的数据对象列表
     */
    public function mapList(array $rawDataList): array;
}
