<?php
namespace Royfee\XShop\Contracts;

interface OrderApiInterface
{
    /**
     * 根据时间范围同步订单列表
     * @param string $startTime 开始时间 Y-m-d H:i:s
     * @param string $endTime 结束时间 Y-m-d H:i:s
     * @param int $page 页码
     * @param int $pageSize 每页条数
     * @return XOrder[]
     */
    public function getList(array $params): array;

    /**
     * 根据订单号获取单条订单详情
     * @param string $orderSn
     * @return XOrder|null
     */
    public function getDetail(string $orderSn);
}