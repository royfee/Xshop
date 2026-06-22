<?php

namespace Royfee\XShop\Contracts;

use Royfee\XShop\Data\XGoods;

/**
 * 商品接口 - 所有平台的商品模块必须实现
 */
interface GoodsInterface
{
    /**
     * 获取商品列表
     * @param array $params 查询参数
     * @return XGoods[]
     */
    public function getList(array $params = []): array;

    /**
     * 获取商品详情
     * @param string $goodsId 商品ID
     * @return XGoods|null
     */
    public function getDetail(string $goodsId): ?XGoods;

    /**
     * 上架商品
     * @param string $goodsId 商品ID
     * @return bool
     */
    public function onSale(string $goodsId): bool;

    /**
     * 下架商品
     * @param string $goodsId 商品ID
     * @return bool
     */
    public function offSale(string $goodsId): bool;

    /**
     * 更新库存
     * @param string $goodsId 商品ID
     * @param int $quantity 库存数量
     * @return bool
     */
    public function updateStock(string $goodsId, int $quantity): bool;
}
