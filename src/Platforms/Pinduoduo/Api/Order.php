<?php
namespace royfee\xshop\Platforms\Pinduoduo\Api;

use royfee\xshop\Platforms\BaseApi;
class Order extends BaseApi
{
    /**
     * 获取订单列表
     */
    public function getList(array $params = [])
    {
        $result = $this->call('pdd.order.list.get', $params);
exit('HuangFei');
        $result = include('mock.php');
        if($result['code'] !== '0000'){
            return $this->app->error();
        }
var_dump($result['content']);exit;
        $mapOrders = $this->getMapper()
                        ->mapOrders($result['content']['orders_info']);

        return $this->app->success($mapOrders,$result['message']);
    }

    /**
     * 获取订单详情 getDetail.txt
     */
    public function getDetail(string $orderId)
    {
        $result = $this->call('sq.order.detail.get', [
            'order_id' => $orderId
        ]);

        return $result;
    }

    /**
     * 发货
     */
    public function deliver($orderId, $logisticsCompanyCode, $logisticsCompanyName,$trackingNumber)
    {
        return $this->call('sq.order.deliver', [
            'deliver_orders'    =>  [
                [
                    'order_id'	                =>  $orderId,
                    'logistics_company_id'	    =>	$logisticsCompanyCode,
                    'logistics_company_name'    =>	$logisticsCompanyName,
                    'tracking_number'	        =>	$trackingNumber,
                    'partial_delivery_status'	=>  2, //1 部分发货，2 完全发货
                ]
            ]
        ]);
    }

    private function getMapper(){
        return $this->app->getMapper();
    }
}