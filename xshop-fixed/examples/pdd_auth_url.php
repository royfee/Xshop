<?php
require __DIR__ . '/../vendor/autoload.php';

use Royfee\XShop\XShop;

// 初始化SDK
$config = include(__DIR__.'/../config/xshop.php');

$xshop = XShop::make($config);
$pdd   = $xshop->platform('pdd');

try {
    // 自定义state，回调可校验
    $state = 'xshop_' . time();
    // 生成授权URL
    $authUrl = $pdd->auth()->getAuthUrl($state);

    echo "<h3>拼多多网页授权链接</h3>";
    echo "State: " . $state . "<br>";
    echo "授权地址：<a href='{$authUrl}' target='_blank'>{$authUrl}</a>";

} catch (\Exception $e) {
    echo "错误：" . $e->getMessage();
}