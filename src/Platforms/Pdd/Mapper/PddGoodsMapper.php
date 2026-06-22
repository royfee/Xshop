<?php

namespace Royfee\XShop\Platforms\Pdd\Mapper;

use Royfee\XShop\Mapper\AbstractMapper;
use Royfee\XShop\Data\XGoods;
use Royfee\XShop\Data\XGoodsSku;

/**
 * 拼多多商品数据映射器
 * 将拼多多商品数据映射为 XShop 统一格式
 */
class PddGoodsMapper extends AbstractMapper
{
    /**
     * 拼多多商品状态映射表
     */
    protected $statusMap = [
        1 => 'on_sale',
        2 => 'off_sale',
        3 => 'deleted',
    ];

    public function map(array $rawData): XGoods
    {
        $goods = new XGoods();
        $goods->platform = 'pdd';
        $goods->rawData = $rawData;

        $goods->goodsId = (string) $this->get($rawData, 'goods_id');
        $goods->goodsName = $this->get($rawData, 'goods_name');
        $goods->subtitle = $this->get($rawData, 'goods_desc');
        $goods->description = $this->get($rawData, 'detail_html');
        $goods->mainImage = $this->get($rawData, 'thumb_url');
        $goods->galleryImages = $this->get($rawData, 'gallery', []);
        $goods->categoryId = (string) $this->get($rawData, 'cat_id');
        $goods->categoryName = $this->get($rawData, 'cat_name');

        // 金额 (分转元)
        $goods->price = $this->fenToYuan($this->get($rawData, 'min_group_price'));
        $goods->marketPrice = $this->fenToYuan($this->get($rawData, 'market_price'));
        $goods->costPrice = $this->fenToYuan($this->get($rawData, 'cost_price'));

        $goods->stock = (int) $this->get($rawData, 'quantity', 0);
        $goods->sales = (int) $this->get($rawData, 'sold_quantity', 0);

        $statusCode = $this->get($rawData, 'is_onsale');
        $goods->status = $this->statusMap[$statusCode] ?? 'unknown';

        $goods->outerId = $this->get($rawData, 'out_goods_sn');
        $goods->weight = (float) $this->get($rawData, 'weight', 0);

        $goods->createTime = $this->formatTime($this->get($rawData, 'created_at'));
        $goods->updateTime = $this->formatTime($this->get($rawData, 'updated_at'));

        // SKU列表
        $skus = $this->get($rawData, 'sku_list', []);
        $goods->skus = array_map(function ($sku) {
            return $this->mapSku($sku);
        }, $skus);

        return $goods;
    }

    protected function mapSku(array $rawSku): XGoodsSku
    {
        $sku = new XGoodsSku();
        $sku->skuId = (string) $this->get($rawSku, 'sku_id');
        $sku->goodsId = (string) $this->get($rawSku, 'goods_id');
        $sku->spec = $this->get($rawSku, 'spec');
        $sku->price = $this->fenToYuan($this->get($rawSku, 'group_price'));
        $sku->stock = (int) $this->get($rawSku, 'quantity', 0);
        $sku->outerId = $this->get($rawSku, 'outer_id');
        $sku->skuImage = $this->get($rawSku, 'thumb_url');
        $sku->weight = (float) $this->get($rawSku, 'weight', 0);

        return $sku;
    }
}
