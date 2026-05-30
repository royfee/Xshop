<?php
namespace royfee\xshop\Platforms\Yueyan\Api;

use royfee\xshop\Platforms\BaseApi;
class Order extends BaseApi
{
    /**
     * 获取订单列表
     */
    public function getList(array $params = [])
    {
        return $this->call('sq.order.list.get', $params);
    }

    /**
     * 获取订单详情
     */
    public function getDetail(string $orderId)
    {
        return $this->call('sq.order.detail.get', [
            'order_id' => $orderId
        ]);
    }

    /**
     * 发货
     */
    public function deliver($orderId, $logisticsCompany, $logisticsNo)
    {
        return $this->call('taobao.logistics.offline.send', [
            'tid' => $orderId,
            'company_code' => $logisticsCompany,
            'out_sid' => $logisticsNo
        ]);
    }
}