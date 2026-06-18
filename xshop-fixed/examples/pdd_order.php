<?php
require __DIR__ . '/../vendor/autoload.php';

use Royfee\XShop\XShop;

$xshop = XShop::make();
$pdd = $xshop->platform('pdd');

try {
    // 1. 先保证已授权并获取到Token（走之前的授权流程）
    $token = $pdd->auth()->getToken();
    echo "当前可用Token：" . $token . PHP_EOL . PHP_EOL;

    // 2. 按时间范围同步订单列表
    $start = date('Y-m-d H:i:s', strtotime('-1 day'));
    $end   = date('Y-m-d H:i:s');
    $orderList = $pdd->order()->getList([
        'start_confirm_at'   => strtotime($start),
        'end_confirm_at'     => strtotime($end),
        'page'               => 1,
        'page_size'          => 10,
    ]);

    echo "===== 订单列表（" . $start . " ~ " . $end . "）=====" . PHP_EOL;
    foreach ($orderList as $order) {
        print_r($order->toArray());
        echo '-------------------------' . PHP_EOL;
    }

    // 3. 根据订单号查询单条详情（替换为真实订单号）
    /*
    $orderSn = 'XXXXXXXXXXXXXX';
    $orderInfo = $pdd->order()->getDetail($orderSn);
    if ($orderInfo) {
        echo PHP_EOL . "===== 单条订单详情 =====" . PHP_EOL;
        print_r($orderInfo->toArray());
    }
    */

} catch (\Exception $e) {
    echo "异常：" . $e->getMessage();
}