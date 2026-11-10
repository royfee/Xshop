<?php

namespace Royfee\XShop\Platforms\HKTVmall\Mapper;

use Royfee\XShop\Mapper\AbstractMapper;
use Royfee\XShop\Data\XOrder;
use Royfee\XShop\Data\XOrderItem;

/**
 * HKTVmall Order Mapper
 * Maps HKTVmall/Shoalter API response to XShop unified format.
 *
 * 订单字段映射 (基于实际 API 响应):
 *   orderId          → 订单号
 *   consignmentCode  → 托运单号
 *   trackingId       → 物流追踪号
 *   transactionId    → 交易ID
 *   orderDate        → 下单时间
 *   consignmentStatus→ 订单状态
 *   pickUpDate       → 取件时间
 *   deliveryDate     → 送达时间
 *   deliveryMethod   → 配送方式
 *   recipientName    → 收货人
 *   contactPhoneNumber → 收货电话
 *   deliveryAddress  → 收货地址
 *   consignmentEntries → 商品明细
 *   warehouseId      → 仓库ID
 *   deliverToWarehouse → 配送仓库
 */
class HKTVmallOrderMapper extends AbstractMapper
{
    protected $statusTextMap = [
        'PENDING'      => '待处理',
        'NEW'          => '新订单',
        'ACKNOWLEDGED' => '已确认',
        'PROCESSING'   => '处理中',
        'PICKED'       => '已拣货',
        'PACKED'       => '已打包',
        'SHIPPED'      => '已发货',
        'DELIVERED'    => '已送达',
        'COMPLETED'    => '已完成',
        'CANCELLED'    => '已取消',
        'CANCELED'     => '已取消',
        'REFUNDED'     => '已退款',
        'RETURNED'     => '已退货',
    ];

    public function map(array $rawData): XOrder
    {
        $order = new XOrder();
        $order->platform = 'hktvmall';
        $order->rawData = $rawData;

        $order->orderNo = (string) $this->get($rawData, 'orderId');
        $order->platformOrderNo = $order->orderNo;
        $order->shopId = (string) $this->get($rawData, 'warehouseId');
        $order->buyerId = '';
        $order->buyerNickname = (string) $this->get($rawData, 'recipientName');

        $status = (string) $this->get($rawData, 'consignmentStatus', 'pending');
        $order->status = strtolower($status);
        $order->statusText = $this->statusTextMap[strtoupper($status)] ?? $status;

        $entries = $rawData['consignmentEntries'] ?? [];
        $totalAmount = 0.0;
        $goodsAmount = 0.0;
        $discountAmount = 0.0;

        foreach ($entries as $entry) {
            $unitPrice = $this->toFloat($this->get($entry, 'unitPrice'));
            $discount = $this->toFloat($this->get($entry, 'discountApplied'));
            $qty = (int) $this->get($entry, 'quantity', 1);
            $goodsAmount += $unitPrice * $qty;
            $discountAmount += $discount;
            $totalAmount += $this->toFloat($this->get($entry, 'totalPrice'));
        }

        $order->totalAmount = $totalAmount;
        $order->goodsAmount = $goodsAmount;
        $order->discountAmount = $discountAmount;
        $order->postage = 0.0;

        $order->payType = (string) $this->get($rawData, 'deliveryMethod');
        $order->payTime = null;
        $order->createTime = $this->toDateTime($this->get($rawData, 'orderDate'));
        $order->sendTime = $this->toDateTime($this->get($rawData, 'pickUpDate'));
        $order->receiveTime = $this->toDateTime($this->get($rawData, 'deliveryDate'));

        $order->receiverName = (string) $this->get($rawData, 'recipientName');
        $order->receiverPhone = (string) $this->get($rawData, 'contactPhoneNumber');
        $order->receiverAddress = (string) $this->get($rawData, 'deliveryAddress');
        $order->receiverProvince = '';
        $order->receiverCity = (string) $this->get($rawData, 'deliverToWarehouse');
        $order->receiverDistrict = '';

        $order->buyerRemark = (string) $this->get($rawData, 'message');
        $order->sellerRemark = '';

        $order->trackingNo = (string) $this->get($rawData, 'trackingId');
        $order->logisticsCompany = (string) $this->get($rawData, 'mainlandCourierCompanyName');

        $mainlandTracking = $this->get($rawData, 'mainlandCourierTrackingNumber');
        if (!empty($mainlandTracking)) {
            $order->trackingNo = (string) $mainlandTracking;
        }

        $order->items = array_map(function ($rawItem) {
            return $this->mapItem($rawItem);
        }, $entries);

        return $order;
    }

    protected function mapItem(array $rawItem): XOrderItem
    {
        $item = new XOrderItem();

        $item->goodsId = (string) $this->get($rawItem, 'pk');
        $item->sku = (string) $this->get($rawItem, 'skuId');
        $item->goodsName = (string) ($this->get($rawItem, 'skuNameZh')
            ?: $this->get($rawItem, 'skuNameEn'));
        $item->skuSpec = (string) $this->get($rawItem, 'brand');
        $item->goodsImage = '';
        $item->quantity = (int) $this->get($rawItem, 'quantity', 0);
        $item->price = $this->toFloat($this->get($rawItem, 'unitPrice'));
        $item->subtotal = $this->toFloat($this->get($rawItem, 'totalPrice'));
        $item->subtotalTax = 0.0;
        $item->outerNo = '';

        return $item;
    }
}
