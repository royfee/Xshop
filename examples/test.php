<?php
require __DIR__ . '/../vendor/autoload.php';

use royfee\xshop\Shop;

// 创建入口
$shop = new Shop('config/shop.php');

//获取授权url
//echo $shop->pdd->access_token->getAuthUrl('12345678');

//获取token
//var_dump($shop->pdd->access_token->setCode('2fcc8419bd4c47b487fbea2419b4c2178e909d39')->getToken());

var_dump($shop->pdd->order->getList([]));

/*
$orderList = $shop->yueyan->order->getList([
    'order_status'  => '17',
    'date_type'     =>  5,
    'sort_type'     =>  1,
    'page_no'       =>  1,
    'page_rows'     =>  100,
    'start_date'    =>  '2026-05-01 00:00:00',
    'end_date'      =>  '2026-05-29 23:59:59',
]);
var_dump($orderList);
*/

//$orderInfo = $shop->yueyan->order->getList([]);
//var_dump($orderInfo);

//$orderInfo = $shop->yueyan->order->deliver('100026817518','Y100','中国邮政国际包裹','BK016942687HK');
//var_dump($orderInfo);