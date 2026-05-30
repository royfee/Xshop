<?php
require_once 'vendor/autoload.php';

use royfee\xshop\Shop;

// 创建入口
$shop = new Shop('src/Config/shop.php');
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

$orderInfo = $shop->yueyan->order->getDetail('100014426050');
var_dump($orderInfo);


//file_put_contents('529.txt',var_export($orderList,true));
