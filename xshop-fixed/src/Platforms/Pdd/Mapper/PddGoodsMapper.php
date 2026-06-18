<?php

namespace Royfee\XShop\Platforms\Pdd\Mapper;

use Royfee\XShop\Mapper\BaseMapper;
use Royfee\XShop\Data\XGoods;

class PddGoodsMapper extends BaseMapper
{
    public function transform($data): XGoods
    {
        $goods = new XGoods();
        $goods->goodsId = $data['goods_id'] ?? '';
        $goods->title = $data['goods_name'] ?? '';
        $goods->price = $data['market_price'] ?? 0;
        $goods->stock = $data['goods_stock'] ?? 0;
        $goods->picUrl = $data['goods_image'] ?? '';
        $goods->status = $data['is_onsale'] ?? 0;
        $goods->platform = 'pdd';

        return $goods;
    }
}