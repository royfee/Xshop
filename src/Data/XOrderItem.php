<?php
namespace royfee\xshop;

/**
 * 统一订单商品数据结构
 */
class XOrderItem
{
    public $skuId;              // SKU ID
    public $productId;          // 商品ID
    public $productTitle;       // 商品标题
    public $productImage;       // 商品图片
    public $price;              // 单价
    public $quantity;           // 数量
    public $totalPrice;         // 总价
    public $discountPrice;      // 优惠后价格
    
    public $skuProperties;      // SKU属性（颜色、尺寸等）
    public $productUrl;         // 商品链接
    
    public $rawData;            // 原始数据
    
    public static function fromArray(array $data): self
    {
        $item = new self();
        foreach ($data as $key => $value) {
            if (property_exists($item, $key)) {
                $item->$key = $value;
            }
        }
        return $item;
    }
}