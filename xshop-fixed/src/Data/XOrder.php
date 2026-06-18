<?php
namespace Royfee\XShop\Data;

/**
 * 统一订单数据结构（含订单内商品明细）
 */
class XOrder
{
    // 国家
    public string $country = '';

    // 平台订单号
    public string $orderSn = '';
    // 父订单号
    public string $parentOrderSn = '';
    // 买家昵称
    public string $buyerNick = '';
    // 实付金额
    public float $payAmount = 0.00;
    // 商品总金额
    public float $goodsAmount = 0.00;
    // 运费
    public float $freightAmount = 0.00;
    // 订单状态
    public int $orderStatus = 0;
    // 下单时间
    public string $createTime = '';
    // 支付时间
    public string $payTime = '';
 
    // 货币
    public string $currency = '';

    // 省份
    public string $recProvince = '';

    // 城市
    public string $recCity = '';

    // 区
    public string $recArea = '';

    public string $recAddress = '';

    // 收货人
    public string $recName = '';
    public string $recIdnumber = '';
    public string $recMobile = '';

    // 手机号
    // 平台标识
    public string $platform = '';

    // 订单内商品列表（XGoods 数组）
    public array $goodsList = [];

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}