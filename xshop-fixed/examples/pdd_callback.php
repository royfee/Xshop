<?php
require __DIR__ . '/../vendor/autoload.php';

use Royfee\XShop\XShop;

$xshop = XShop::make();
$pdd   = $xshop->platform('pdd');

/*
// 接收拼多多回调参数
$code  = $_GET['code'] ?? '123';
$state = $_GET['state'] ?? '';
$error = $_GET['error'] ?? '';

echo "<h3>拼多多授权回调</h3>";

// 授权失败
if (!empty($error)) {
    die("授权失败：" . $error);
}

// 校验Code
if (empty($code)) {
    die("未获取到授权Code");
}
*/
$code = 'f7146c3e8089496782902e08109453ce8db04532';
try {
    echo "获取到授权Code：{$code}<br>";
    echo "自定义State：{$state}<br>";

    // 根据Code换取AccessToken，自动存入缓存
    $accessToken = $pdd->auth()->getTokenByCode($code);
    echo "<hr>";
    echo "✅ 获取AccessToken成功：<br>";
    echo $accessToken . "<br>";
    echo "Token已自动存入缓存，后续接口直接读取缓存";

} catch (\Exception $e) {
    echo "❌ 换取Token失败：" . $e->getMessage();
}