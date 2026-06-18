<?php
require __DIR__ . '/../vendor/autoload.php';

use Royfee\XShop\XShop;

$xshop = XShop::make();
$pdd = $xshop->platform('pdd');

try {
    // 1. 先保证已授权并获取到Token（走之前的授权流程）
    $token = $pdd->auth()->getToken();
    echo "当前可用Token：" . $token . PHP_EOL . PHP_EOL;

    $orderList = $pdd->decrypt()->decryptOrder([
        'receiver_name' =>  '~AgAAAAQ8QMcFToQakAAp/+XZ8M5rYUVluWGC7ppm308=~0~'
    ]);

    var_dump($orderList);
} catch (\Exception $e) {
    echo "异常：" . $e->getMessage();
}