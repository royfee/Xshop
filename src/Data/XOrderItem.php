<?php

namespace Royfee\XShop\Data;

/**
 * XShop 统一订单商品项格式
 */
class XOrderItem
{
    /** @var string 商品ID */
    public $goodsId;

    /** @var string SKU ID */
    public $skuId;

    /** @var string 商品名称 */
    public $goodsName;

    /** @var string SKU规格 */
    public $skuSpec;

    /** @var string 商品图片 */
    public $goodsImage;

    /** @var int 数量 */
    public $quantity;

    /** @var float 单价 */
    public $price;

    /** @var float 小计金额 */
    public $subtotal;

    /** @var string 外部编码 */
    public $outerId;

    /**
     * 转换为数组
     * @return array
     */
    public function toArray(): array
    {
        return [
            'goodsId' => $this->goodsId,
            'skuId' => $this->skuId,
            'goodsName' => $this->goodsName,
            'skuSpec' => $this->skuSpec,
            'goodsImage' => $this->goodsImage,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'subtotal' => $this->subtotal,
            'outerId' => $this->outerId,
        ];
    }

    /**
     * 从数组创建
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $item = new self();
        foreach ($data as $key => $value) {
            $item->$key = $value;
        }
        return $item;
    }
}
