<?php

namespace Royfee\XShop\Platforms\Pdd\Api;

use Royfee\XShop\Contracts\GoodsInterface;
use Royfee\XShop\Platforms\Pdd\Mapper\PddGoodsMapper;
use Royfee\XShop\Data\XGoods;

/**
 * 拼多多商品接口
 */
class PddGoods extends AbstractPddApi implements GoodsInterface
{
    protected function createMapper(): PddGoodsMapper
    {
        return new PddGoodsMapper();
    }

    public function getList(array $params = []): array
    {
        $response = $this->request('pdd.goods.list.get', $params);
        $goodsList = $response['goods_list_get_response']['goods_list'] ?? [];
        return $this->mapper->mapList($goodsList);
    }

    public function getDetail(string $goodsId): ?XGoods
    {
        $response = $this->request('pdd.goods.information.get', [
            'goods_id' => $goodsId,
        ]);
        $goods = $response['goods_info_get_response'] ?? null;
        return $goods ? $this->mapper->map($goods) : null;
    }

    public function onSale(string $goodsId): bool
    {
        $response = $this->request('pdd.goods.sale.status.set', [
            'goods_id' => $goodsId,
            'is_onsale' => 1,
        ]);
        return ($response['goods_sale_status_set_response']['is_success'] ?? false) === true;
    }

    public function offSale(string $goodsId): bool
    {
        $response = $this->request('pdd.goods.sale.status.set', [
            'goods_id' => $goodsId,
            'is_onsale' => 0,
        ]);
        return ($response['goods_sale_status_set_response']['is_success'] ?? false) === true;
    }

    public function updateStock(string $goodsId, int $quantity): bool
    {
        $response = $this->request('pdd.goods.quantity.update', [
            'goods_id' => $goodsId,
            'quantity' => $quantity,
        ]);
        return ($response['goods_quantity_update_response']['is_success'] ?? false) === true;
    }
}
