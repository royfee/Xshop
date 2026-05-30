<?php
namespace MultiShop\Platforms\Taobao\Api;

use MultiShop\Platforms\BaseApi;

class Order extends BaseApi
{
    /**
     * 获取订单列表
     */
    public function getList($params = [])
    {
        return $this->call('taobao.trades.sold.get', [
            'fields' => 'tid,status,payment,created,total_fee',
            'page_no' => $params['page'] ?? 1,
            'page_size' => $params['page_size'] ?? 20,
            'start_created' => $params['start_time'] ?? '',
            'end_created' => $params['end_time'] ?? '',
            'status' => $params['status'] ?? ''
        ]);
    }
    
    /**
     * 获取订单详情
     */
    public function getDetail($orderId)
    {
        return $this->call('taobao.trade.fullinfo.get', [
            'fields' => 'tid,status,payment,orders,shipping,receiver_name',
            'tid' => $orderId
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