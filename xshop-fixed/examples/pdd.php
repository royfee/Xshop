<?php
require __DIR__ . '/../vendor/autoload.php';

use Royfee\XShop\XShop;

// 初始化
$xshop = XShop::make();

// 获取拼多多平台实例
$pdd = $xshop->platform('pdd');

try {
    // 获取Token（自动缓存）
    $token = $pdd->auth()->getToken();
    echo "=== Access Token ===\n";
    echo $token . "\n\n";

    // 替换为你真实的商品ID测试
    $goodsId = "123456789";
    $goods   = $pdd->goods()->getDetail($goodsId);

    echo "=== 统一商品数据 ===\n";
    print_r($goods->toArray());

} catch (\Exception $e) {
    echo "错误：" . $e->getMessage() . PHP_EOL;
}