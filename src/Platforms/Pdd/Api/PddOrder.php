<?php

namespace Royfee\XShop\Platforms\Pdd\Api;

use Royfee\XShop\Contracts\OrderInterface;
use Royfee\XShop\Platforms\Pdd\Mapper\PddOrderMapper;
use Royfee\XShop\Data\XOrder;

/**
 * 拼多多订单接口
 */
class PddOrder extends AbstractPddApi implements OrderInterface
{
    protected function createMapper(): PddOrderMapper
    {
        return new PddOrderMapper();
    }

    public function getList(array $params = []): array
    {
        $response = $this->request('pdd.order.number.list.get', $this->prepareParams($params));
        $orders = $response['order_list_get_response']['order_list'] ?? [];
        return $this->mapper->mapList($orders);
    }

    public function getDetail(string $orderId): ?XOrder
    {
        $response = $this->request('pdd.order.information.get', [
            'order_sn' => $orderId,
        ]);
        $order = $response['order_info_get_response'] ?? null;
        return $order ? $this->mapper->map($order) : null;
    }

    public function getLogistics(string $orderId): array
    {
        $response = $this->request('pdd.order.traces.get', [
            'order_sn' => $orderId,
        ]);
        return $response['order_traces_get_response']['logistics_trace_list'] ?? [];
    }

    public function send(string $orderId, array $params): bool
    {
        $response = $this->request('pdd.order.shipping', array_merge([
            'order_sn' => $orderId,
        ], $params));
        return ($response['order_shipping_response']['is_success'] ?? false) === true;
    }
}
