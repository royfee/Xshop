<?php

namespace Royfee\XShop\Contracts;

use Royfee\XShop\Data\XOrder;

/**
 * 订单接口 - 所有平台的订单模块必须实现
 */
interface OrderInterface
{
    /**
     * 获取订单列表
     * @param array $params 查询参数
     * @return XOrder[]
     */
    public function getList(array $params = []): array;

    /**
     * 获取订单详情
     * @param string $orderId 订单ID
     * @return XOrder|null
     */
    public function getDetail(string $orderId): ?XOrder;

    /**
     * 获取订单物流信息
     * @param string $orderId 订单ID
     * @return array
     */
    public function getLogistics(string $orderId): array;

    /**
     * 订单发货
     * @param string $orderId 订单ID
     * @param array $params 发货参数
     * @return bool
     */
    public function send(string $orderId, array $params): bool;

    /**
     * 获取打印面单
     *
     * 单号统一格式: 订单号|运单号 (运单号可省略)，支持字符串或数组批量。
     * 返回值固定为 ['html' => string, 'raw' => array]:
     *   - html: 面单 HTML，可直接 echo 输出打印
     *   - raw : 平台原始响应 (平台直出 HTML、无附加信息时为 [])
     * 不支持打印面单的平台返回空数组 []。
     *
     * @param string|array $param 单号，如 "H261108512201-B2070001|8287048424" 或其数组
     * @param array $options 打印参数，如 ['paperSize' => 'A5']
     * @return array ['html' => string, 'raw' => array]，不支持的平台返回 []
     */
    public function getLabel($param, array $options = []): array;
}
