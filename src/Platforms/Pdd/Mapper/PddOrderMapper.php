<?php

namespace Royfee\XShop\Platforms\Pdd\Mapper;

use Royfee\XShop\Mapper\AbstractMapper;
use Royfee\XShop\Data\XOrder;
use Royfee\XShop\Data\XOrderItem;

/**
 * 拼多多订单数据映射器
 * 将拼多多订单数据映射为 XShop 统一格式
 */
class PddOrderMapper extends AbstractMapper
{
    /**
     * 拼多多订单状态映射表
     */
    protected $statusMap = [
        1 => ['pending', '待发货'],
        2 => ['shipped', '已发货'],
        3 => ['signed', '已签收'],
        4 => ['after_sale', '售后中'],
        5 => ['completed', '已完成'],
        6 => ['cancelled', '已取消'],
    ];

    public function map(array $rawData): XOrder
    {
        $order = new XOrder();
        $order->platform = 'pdd';
        $order->rawData = $rawData;

        // 基础信息
        $order->orderId = $this->get($rawData, 'order_sn');
        $order->platformOrderNo = $this->get($rawData, 'order_sn');
        $order->shopId = $this->get($rawData, 'mall_id');
        $order->buyerId = $this->get($rawData, 'buyer_id');
        $order->buyerNickname = $this->get($rawData, 'buyer_nickname');

        // 状态映射
        $statusCode = $this->get($rawData, 'order_status');
        $statusInfo = $this->statusMap[$statusCode] ?? ['unknown', '未知状态'];
        $order->status = $statusInfo[0];
        $order->statusText = $statusInfo[1];

        // 金额 (拼多多金额单位为分)
        $order->totalAmount = $this->fenToYuan($this->get($rawData, 'pay_amount'));
        $order->goodsAmount = $this->fenToYuan($this->get($rawData, 'goods_amount'));
        $order->postage = $this->fenToYuan($this->get($rawData, 'postage'));
        $order->discountAmount = $this->fenToYuan($this->get($rawData, 'discount_amount'));

        // 支付信息
        $order->payType = $this->get($rawData, 'pay_type');
        $order->payTime = $this->formatTime($this->get($rawData, 'pay_time'));
        $order->createTime = $this->formatTime($this->get($rawData, 'created_time'));
        $order->sendTime = $this->formatTime($this->get($rawData, 'shipping_time'));
        $order->receiveTime = $this->formatTime($this->get($rawData, 'receive_time'));

        // 收货地址
        $order->receiverName = $this->get($rawData, 'receiver_name');
        $order->receiverPhone = $this->get($rawData, 'receiver_phone');
        $order->receiverProvince = $this->get($rawData, 'province');
        $order->receiverCity = $this->get($rawData, 'city');
        $order->receiverDistrict = $this->get($rawData, 'district');
        $order->receiverAddress = implode(' ', array_filter([
            $order->receiverProvince,
            $order->receiverCity,
            $order->receiverDistrict,
            $this->get($rawData, 'address'),
        ]));

        // 备注
        $order->buyerRemark = $this->get($rawData, 'remark');
        $order->sellerRemark = $this->get($rawData, 'note');

        // 物流
        $order->trackingNo = $this->get($rawData, 'tracking_number');
        $order->logisticsCompany = $this->get($rawData, 'logistics_company');

        // 商品项
        $items = $this->get($rawData, 'item_list', []);
        $order->items = array_map(function ($item) {
            return $this->mapItem($item);
        }, $items);

        return $order;
    }

    protected function mapItem(array $rawItem): XOrderItem
    {
        $item = new XOrderItem();
        $item->goodsId = $this->get($rawItem, 'goods_id');
        $item->skuId = $this->get($rawItem, 'sku_id');
        $item->goodsName = $this->get($rawItem, 'goods_name');
        $item->skuSpec = $this->get($rawItem, 'goods_spec');
        $item->goodsImage = $this->get($rawItem, 'goods_img');
        $item->quantity = (int) $this->get($rawItem, 'goods_count', 0);
        $item->price = $this->fenToYuan($this->get($rawItem, 'goods_price'));
        $item->subtotal = $this->fenToYuan($this->get($rawItem, 'goods_amount'));
        $item->outerId = $this->get($rawItem, 'outer_id');

        return $item;
    }
}
