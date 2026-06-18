<?php
namespace Royfee\XShop\Platforms\Pdd\Mapper;

use Royfee\XShop\Mapper\BaseMapper;
use Royfee\XShop\Data\XGoods;
use Royfee\XShop\Data\XOrder;

class PddOrderMapper extends BaseMapper
{
    /**
     * 单条商品数据映射
     */
    public function transformGoods($data): XGoods
    {
        $goods = new XGoods();
        $goods->goodsId     = $data['goods_id'] ?? '';
        $goods->title       = $data['goods_name'] ?? '';
        $goods->price       = isset($data['goods_price']) ? (float)$data['goods_price'] : 0;
        $goods->quantity    = $data['goods_count'] ?? '';
        $goods->spec        = $data['goods_spec'] ?? '';
        $goods->sku         = $data['sku_id'] ?? '';
        $goods->platform    = 'pdd';

        return $goods;
    }

    /**
     * 订单主数据映射 + 自动嵌套订单商品列表
     */
    public function transform($data): XOrder
    {
        $order = new XOrder();

        // 订单基础字段
        $order->orderSn        = $data['order_sn'] ?? '';
        $order->parentOrderSn  = '';
        $order->buyerNick      = '';

        $order->payAmount      = isset($data['pay_amount']) ? (float)$data['pay_amount'] : 0.00;
        $order->goodsAmount    = isset($data['goods_amount']) ? (float)$data['goods_amount'] : 0.00;
        $order->freightAmount  = isset($data['postage']) ? (float)$data['postage'] : 0.00;

        $order->orderStatus    = $data['order_status'] ?? 0;

        //收货人
        $order->recName        = $data['receiver_name'] ?? '';
        $order->recMobile      = $data['receiver_phone'] ?? '';

        $order->recProvince    = $data['province'];
        $order->recCity        = $data['city'];
        $order->recArea        = $data['town'];
        $order->recAddress     = $data['address'] ?? '';

        $order->createTime     = $data['created_time'] ?? '';
        $order->payTime        = $data['pay_time'] ?? '';
        $order->deliveryTime   = $data['last_ship_time'] ?? '';

        $order->mobile         = $data['receiver_phone'] ?? '';
        $order->platform       = 'pdd';

        // 解析订单内商品 item_list，批量映射
        $itemList = $data['item_list'] ?? [];
        $goodsArr = [];
        foreach ($itemList as $item) {
            $goodsArr[] = $this->transformGoods($item);
        }
        $order->goodsList = $goodsArr;

        return $order;
    }
}