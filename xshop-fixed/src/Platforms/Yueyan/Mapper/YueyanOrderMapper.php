<?php
namespace Royfee\XShop\Platforms\Yueyan\Mapper;

use Royfee\XShop\Mapper\BaseMapper;
use Royfee\XShop\Data\XGoods;
use Royfee\XShop\Data\XOrder;

class YueyanOrderMapper extends BaseMapper
{
    /**
     * 单条商品数据映射
     */
    public function transformGoods($data): XGoods
    {
        $goods = new XGoods();
        $goods->title       = $data['product_title'] ?? '';
        $goods->quantity    = $data['num'] ?? '';
        $goods->price       = $data['custom_declare']['declare_unit_price'];
        $goods->spec        = $data['sku_properties_name'] ?? '';
        $goods->sku         = $data['outer_sku_id'] ?? '';
        $goods->platform    = 'yueyan';
        return $goods;
    }

    /**
     * 订单主数据映射 + 自动嵌套订单商品列表
     */
    public function transform($data): XOrder
    {
        $order = new XOrder();

        $addr = explode(',',$data['receiver_address']);

        // 订单基础字段
        $order->orderSn        = $data['order_id'] ?? '';
        $order->currency       = $data['payment_currency'] ?? '';

        //收货人
        $order->recName        = $data['receiver_name'] ?? '';
        $order->recIdnumber    = $data['id_cards'] ? $data['id_cards'][0]['receiver_id_no'] : '';
        $order->recMobile      = $data['receiver_phone'] ?? '';

        $order->recProvince    = $addr[0]??'';
        $order->recCity        = $addr[1]??'';
        $order->recArea        = $addr[2]??'';
        $order->recAddress     = $data['receiver_address'] ?? '';


        $order->orderStatus    = $data['order_status'] ?? 0;

        $order->createTime     = $data['created_time'] ?? '';
        $order->payTime        = $data['pay_time'] ?? '';
        $order->deliveryTime   = $data['last_ship_time'] ?? '';

        $order->address        = $data['address_mask'] ?? '';

        $order->platform       = 'yueyan';

        // 解析订单内商品 item_list，批量映射
        $itemList = $data['order_items_info'] ?? [];
        $goodsArr = [];
        foreach ($itemList as $item) {
            $goodsArr[] = $this->transformGoods($item)->toArray();
        }
        $order->goodsList = $goodsArr;

        return $order;
    }
}