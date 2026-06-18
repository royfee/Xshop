<?php
require __DIR__ . '/../vendor/autoload.php';

use Royfee\XShop\XShop;

$xshop = XShop::make();
$pdd   = $xshop->platform('pdd');

try {
    $token = $pdd->auth()->getToken();
    echo "当前可用Token：" . $token . PHP_EOL . PHP_EOL;


    // 根据Code换取AccessToken，自动存入缓存
    $accessToken = $pdd->auth()->refreshToken();

    echo "<hr>";
    echo "✅ 获取RefreshToken成功：<br>";
    echo $accessToken . "<br>";
    echo "Token已自动存入缓存，后续接口直接读取缓存";

} catch (\Exception $e) {
    echo "❌ 换取Token失败：" . $e->getMessage();
}