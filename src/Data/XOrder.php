<?php
namespace royfee\xshop\Data;

/**
 * 统一订单数据结构
 * 将所有平台的订单数据映射到统一字段
 */
class XOrder
{
    // 基础信息
    public $platform;           // 平台名称: taobao, jd, pdd
    public $orderId;            // 订单ID（统一格式）
    public $originalId;         // 原始平台订单ID
    public $orderStatus;        // 订单状态（统一状态码）
    public $orderStatusText;    // 订单状态文本
    
    // 金额信息
    public $totalAmount;        // 订单总金额
    public $paymentAmount;      // 实付金额
    public $discountAmount;     // 优惠金额
    public $shippingFee;        // 运费
    public $currency;           // 货币单位
    
    // 时间信息
    public $createdAt;          // 下单时间（统一格式 Y-m-d H:i:s）
    public $paidAt;             // 付款时间
    public $shippedAt;          // 发货时间
    public $completedAt;        // 完成时间
    public $closedAt;           // 关闭时间
    
    // 买家信息
    public $buyerNick;          // 买家昵称
    public $buyerName;          // 买家真实姓名
    public $buyerPhone;         // 买家电话
    public $buyerAddress;       // 买家地址
    
    // 收货信息
    public $receiverName;       // 收货人姓名
    public $receiverPhone;      // 收货人电话
    public $receiverAddress;    // 收货地址
    public $receiverZip;        // 收货邮编
    
    // 商品信息
    public $items;              // 商品列表（UnifiedOrderItem数组）
    public $itemCount;          // 商品总数
    
    // 物流信息
    public $logisticsCompany;   // 物流公司
    public $logisticsNo;        // 物流单号
    public $logisticsStatus;    // 物流状态
    
    // 其他信息
    public $buyerMessage;       // 买家留言
    public $sellerMessage;      // 卖家备注
    public $rawData;            // 原始数据（保留原始平台数据）
    
    /**
     * 将数组转换为UnifiedOrder对象
     */
    public static function fromArray(array $data): self
    {
        $order = new self();
        foreach ($data as $key => $value) {
            if (property_exists($order, $key)) {
                $order->$key = $value;
            }
        }
        return $order;
    }
    
    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
    
    /**
     * 判断订单是否有效
     */
    public function isValid(): bool
    {
        return !empty($this->orderId) && !empty($this->platform);
    }
}