<?php

namespace Royfee\XShop\Data;

/**
 * XShop 统一商品SKU格式
 */
class XGoodsSku
{
    /** @var string SKU ID */
    public $skuId;

    /** @var string 商品ID */
    public $goodsId;

    /** @var string SKU规格 */
    public $spec;

    /** @var float SKU价格 */
    public $price;

    /** @var int 库存 */
    public $stock;

    /** @var string SKU编码 */
    public $outerId;

    /** @var string SKU图片 */
    public $skuImage;

    /** @var float 重量(kg) */
    public $weight;

    /**
     * 转换为数组
     * @return array
     */
    public function toArray(): array
    {
        return [
            'skuId' => $this->skuId,
            'goodsId' => $this->goodsId,
            'spec' => $this->spec,
            'price' => $this->price,
            'stock' => $this->stock,
            'outerId' => $this->outerId,
            'skuImage' => $this->skuImage,
            'weight' => $this->weight,
        ];
    }

    /**
     * 从数组创建
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $sku = new self();
        foreach ($data as $key => $value) {
            $sku->$key = $value;
        }
        return $sku;
    }
}
