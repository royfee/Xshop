<?php
require __DIR__ . '/../vendor/autoload.php';

use Royfee\XShop\XShop;

$xshop = XShop::make();
$pdd   = $xshop->platform('pdd');

try {
    // 直接从缓存获取Token（无需重复授权）
    $token = $pdd->auth()->getToken();
    echo "从缓存读取Token：" . $token . "<br>";

    // 后续正常调用商品/订单等接口
    // $goods = $pdd->goods()->getDetail(123456789);

} catch (\Exception $e) {
    echo "错误：" . $e->getMessage();
}