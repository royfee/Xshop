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
}
