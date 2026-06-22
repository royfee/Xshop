<?php

namespace Royfee\XShop\Data;

/**
 * XShop 统一商品数据格式
 * 所有平台的商品数据都会映射成此格式
 */
class XGoods
{
    /** @var string 商品ID */
    public $goodsId;

    /** @var string 商品名称 */
    public $goodsName;

    /** @var string 商品副标题 */
    public $subtitle;

    /** @var string 商品描述 */
    public $description;

    /** @var string 商品主图 */
    public $mainImage;

    /** @var string[] 商品轮播图 */
    public $galleryImages = [];

    /** @var string 类目ID */
    public $categoryId;

    /** @var string 类目名称 */
    public $categoryName;

    /** @var float 商品价格 */
    public $price;

    /** @var float 市场价 */
    public $marketPrice;

    /** @var float 成本价 */
    public $costPrice;

    /** @var int 库存数量 */
    public $stock;

    /** @var int 销量 */
    public $sales;

    /** @var string 商品状态: on_sale-上架, off_sale-下架, deleted-删除 */
    public $status;

    /** @var string 商品编码 */
    public $outerId;

    /** @var float 重量(kg) */
    public $weight;

    /** @var string 创建时间 */
    public $createTime;

    /** @var string 更新时间 */
    public $updateTime;

    /** @var XGoodsSku[] SKU列表 */
    public $skus = [];

    /** @var array 原始数据 */
    public $rawData = [];

    /**
     * 转换为数组
     * @return array
     */
    public function toArray(): array
    {
        return [
            'goodsId' => $this->goodsId,
            'goodsName' => $this->goodsName,
            'subtitle' => $this->subtitle,
            'description' => $this->description,
            'mainImage' => $this->mainImage,
            'galleryImages' => $this->galleryImages,
            'categoryId' => $this->categoryId,
            'categoryName' => $this->categoryName,
            'price' => $this->price,
            'marketPrice' => $this->marketPrice,
            'costPrice' => $this->costPrice,
            'stock' => $this->stock,
            'sales' => $this->sales,
            'status' => $this->status,
            'outerId' => $this->outerId,
            'weight' => $this->weight,
            'createTime' => $this->createTime,
            'updateTime' => $this->updateTime,
            'skus' => array_map(function ($sku) {
                return $sku instanceof XGoodsSku ? $sku->toArray() : $sku;
            }, $this->skus),
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
        $goods = new self();
        foreach ($data as $key => $value) {
            if ($key === 'skus' && is_array($value)) {
                $goods->skus = array_map(function ($item) {
                    return is_array($item) ? XGoodsSku::fromArray($item) : $item;
                }, $value);
            } else {
                $goods->$key = $value;
            }
        }
        return $goods;
    }
}
