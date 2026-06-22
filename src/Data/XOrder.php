<?php

namespace Royfee\XShop\Data;

/**
 * XShop 统一订单数据格式
 * 所有平台的订单数据都会映射成此格式
 */
class XOrder
{
    /** @var string 订单ID */
    public $orderId;

    /** @var string 平台订单号 */
    public $platformOrderNo;

    /** @var string 平台标识 */
    public $platform;

    /** @var string 店铺ID */
    public $shopId;

    /** @var string 买家ID */
    public $buyerId;

    /** @var string 买家昵称 */
    public $buyerNickname;

    /** @var string 订单状态 */
    public $status;

    /** @var string 订单状态描述 */
    public $statusText;

    /** @var float 订单总金额 */
    public $totalAmount;

    /** @var float 商品总金额 */
    public $goodsAmount;

    /** @var float 运费 */
    public $postage;

    /** @var float 折扣金额 */
    public $discountAmount;

    /** @var string 支付方式 */
    public $payType;

    /** @var string 支付时间 */
    public $payTime;

    /** @var string 下单时间 */
    public $createTime;

    /** @var string 发货时间 */
    public $sendTime;

    /** @var string 收货时间 */
    public $receiveTime;

    /** @var string 收货人姓名 */
    public $receiverName;

    /** @var string 收货人电话 */
    public $receiverPhone;

    /** @var string 收货地址 */
    public $receiverAddress;

    /** @var string 收货省份 */
    public $receiverProvince;

    /** @var string 收货城市 */
    public $receiverCity;

    /** @var string 收货区县 */
    public $receiverDistrict;

    /** @var string 买家备注 */
    public $buyerRemark;

    /** @var string 卖家备注 */
    public $sellerRemark;

    /** @var string 物流单号 */
    public $trackingNo;

    /** @var string 物流公司 */
    public $logisticsCompany;

    /** @var XOrderItem[] 订单商品列表 */
    public $items = [];

    /** @var array 原始数据(保留原始平台数据用于特殊处理) */
    public $rawData = [];

    /**
     * 转换为数组
     * @return array
     */
    public function toArray(): array
    {
        return [
            'orderId' => $this->orderId,
            'platformOrderNo' => $this->platformOrderNo,
            'platform' => $this->platform,
            'shopId' => $this->shopId,
            'buyerId' => $this->buyerId,
            'buyerNickname' => $this->buyerNickname,
            'status' => $this->status,
            'statusText' => $this->statusText,
            'totalAmount' => $this->totalAmount,
            'goodsAmount' => $this->goodsAmount,
            'postage' => $this->postage,
            'discountAmount' => $this->discountAmount,
            'payType' => $this->payType,
            'payTime' => $this->payTime,
            'createTime' => $this->createTime,
            'sendTime' => $this->sendTime,
            'receiveTime' => $this->receiveTime,
            'receiverName' => $this->receiverName,
            'receiverPhone' => $this->receiverPhone,
            'receiverAddress' => $this->receiverAddress,
            'receiverProvince' => $this->receiverProvince,
            'receiverCity' => $this->receiverCity,
            'receiverDistrict' => $this->receiverDistrict,
            'buyerRemark' => $this->buyerRemark,
            'sellerRemark' => $this->sellerRemark,
            'trackingNo' => $this->trackingNo,
            'logisticsCompany' => $this->logisticsCompany,
            'items' => array_map(function ($item) {
                return $item instanceof XOrderItem ? $item->toArray() : $item;
            }, $this->items),
            'rawData' => $this->rawData,
        ];
    }

    /**
     * 从数组创建
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $order = new self();
        foreach ($data as $key => $value) {
            if ($key === 'items' && is_array($value)) {
                $order->items = array_map(function ($item) {
                    return is_array($item) ? XOrderItem::fromArray($item) : $item;
                }, $value);
            } else {
                $order->$key = $value;
            }
        }
        return $order;
    }
}
