<?php
/**
 * WooCommerce 平台测试示例
 *
 * 测试前请先安装依赖:
 *   composer install
 *
 * 运行方式:
 *   php examples/woocommerce_test.php
 *  

 * 配置说明:
 *   修改下方 $config 中的 site_url / consumer_key / consumer_secret 为你的真实值
 *   或在 WooCommerce 后台生成 API 密钥:
 *   WooCommerce > Settings > Advanced > REST API > Add key
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Royfee\XShop\XShop;

// ========== 配置 ==========

$config = [
    'debug' => true,
    'logger' => [
        'enabled' => true,
        'level' => 'debug',
    ],
    'http' => [
        'timeout' => 30,
        'connect_timeout' => 10,
        'retries' => 2,
    ],
    'platforms' => [
        'woocommerce' => [
            'enabled' => true,
            'site_url' => 'https://publishing.mishixueshe.com',           // 你的 WooCommerce 站点地址
            'consumer_key' => '',  // WooCommerce REST API Consumer Key
            'consumer_secret' => '', // WooCommerce REST API Consumer Secret
            'api_version' => 'v3',
            'per_page' => 10,
        ],
    ],
];

// ========== 初始化 ==========

$xshop = new XShop($config);
$wc = $xshop->woocommerce();

/*
echo "========================================\n";
echo "  WooCommerce 平台测试\n";
echo "========================================\n\n";

// ========== 测试1: 认证状态 ==========

echo "[测试1] 认证状态检查\n";
echo "----------------------------------------\n";
try {
    $isValid = $wc->auth()->isTokenValid();
    echo "Consumer Key:    " . $wc->auth()->getConsumerKey() . "\n";
    echo "Consumer Secret: " . substr($wc->auth()->getConsumerSecret(), 0, 6) . "******\n";
    echo "Token 有效:      " . ($isValid ? '是' : '否') . "\n";
    echo "结果: ✓ 通过\n\n";
} catch (\Exception $e) {
    echo "结果: ✗ 失败 - " . $e->getMessage() . "\n\n";
}
*/
// ========== 测试2: 获取订单列表 (默认获取已完成订单) ==========

echo "[测试2] 获取订单列表 (默认 status=completed)\n";
echo "----------------------------------------\n";
try {
    $orders = $wc->order()->getList(['status' => 'completed']);
    echo "获取到 " . count($orders) . " 条订单\n";

    foreach ($orders as $i => $order) {
        $idx = $i + 1;
        echo "\n  --- 订单 #{$idx} ---\n";
        echo "  订单ID:     {$order->orderId}\n";
        echo "  平台订单号: {$order->platformOrderNo}\n";
        echo "  状态:       {$order->status} ({$order->statusText})\n";
        echo "  总金额:     {$order->totalAmount}\n";
        echo "  商品金额:   {$order->goodsAmount}\n";
        echo "  运费:       {$order->postage}\n";
        echo "  折扣:       {$order->discountAmount}\n";
        echo "  支付方式:   {$order->payType}\n";
        echo "  下单时间:   {$order->createTime}\n";
        echo "  支付时间:   {$order->payTime}\n";
        echo "  买家:       {$order->buyerNickname}\n";
        echo "  收货人:     {$order->receiverName}\n";
        echo "  收货电话:   {$order->receiverPhone}\n";
        echo "  收货地址:   {$order->receiverAddress}\n";
        echo "  买家备注:   {$order->buyerRemark}\n";
        echo "  物流单号:   {$order->trackingNo}\n";
        echo "  物流公司:   {$order->logisticsCompany}\n";
        echo "  商品项数:   " . count($order->items) . "\n";

        foreach ($order->items as $j => $item) {
            $jdx = $j + 1;
            echo "    [商品{$jdx}] {$item->goodsName} | SKU:{$item->skuId} | "
                . "单价:{$item->price} x {$item->quantity} = {$item->subtotal}\n";
        }
    }

    echo "\n结果: ✓ 通过\n\n";
} catch (\Exception $e) {
    echo "结果: ✗ 失败 - " . $e->getMessage() . "\n\n";
}

// ========== 测试3: 按状态筛选订单 ==========

echo "[测试3] 按状态筛选订单 (processing)\n";
echo "----------------------------------------\n";
try {
    $orders = $wc->order()->getList([
        'status' => 'processing',
        'per_page' => 5,
        'page' => 1,
    ]);
    echo "获取到 " . count($orders) . " 条处理中订单\n";
    foreach ($orders as $order) {
        echo "  订单 #{$order->orderId} | 状态: {$order->status} | 金额: {$order->totalAmount}\n";
    }
    echo "结果: ✓ 通过\n\n";
} catch (\Exception $e) {
    echo "结果: ✗ 失败 - " . $e->getMessage() . "\n\n";
}

// ========== 测试4: 按时间范围筛选 ==========

echo "[测试4] 按时间范围筛选订单\n";
echo "----------------------------------------\n";
try {
    $orders = $wc->order()->getList([
        'status' => 'completed',
        'after' => date('Y-01-01T00:00:00'),
        'before' => date('Y-m-dT23:59:59'),
        'per_page' => 5,
        'orderby' => 'date',
        'order' => 'desc',
    ]);
    echo "获取到 " . count($orders) . " 条今年订单\n";
    foreach ($orders as $order) {
        echo "  订单 #{$order->orderId} | 下单: {$order->createTime} | 金额: {$order->totalAmount}\n";
    }
    echo "结果: ✓ 通过\n\n";
} catch (\Exception $e) {
    echo "结果: ✗ 失败 - " . $e->getMessage() . "\n\n";
}

// ========== 测试5: 分页测试 ==========

echo "[测试5] 分页测试 (每页3条，共2页)\n";
echo "----------------------------------------\n";
try {
    for ($page = 1; $page <= 2; $page++) {
        $orders = $wc->order()->getList([
            'status' => 'completed',
            'page' => $page,
            'per_page' => 3,
        ]);
        echo "  第 {$page} 页: " . count($orders) . " 条\n";
        foreach ($orders as $order) {
            echo "    订单 #{$order->orderId} | {$order->statusText}\n";
        }
    }
    echo "结果: ✓ 通过\n\n";
} catch (\Exception $e) {
    echo "结果: ✗ 失败 - " . $e->getMessage() . "\n\n";
}

// ========== 测试6: 获取订单详情 ==========

echo "[测试6] 获取订单详情\n";
echo "----------------------------------------\n";
try {
    $listOrders = $wc->order()->getList(['status' => 'completed', 'per_page' => 1]);
    if (empty($listOrders)) {
        echo "  无订单可测试详情接口，跳过\n";
        echo "结果: - 跳过\n\n";
    } else {
        $firstOrderId = $listOrders[0]->orderId;
        echo "  使用订单 #{$firstOrderId} 测试详情接口\n";

        $detail = $wc->order()->getDetail($firstOrderId);
        if ($detail) {
            echo "  订单ID:     {$detail->orderId}\n";
            echo "  状态:       {$detail->status} ({$detail->statusText})\n";
            echo "  总金额:     {$detail->totalAmount}\n";
            echo "  收货人:     {$detail->receiverName}\n";
            echo "  收货地址:   {$detail->receiverAddress}\n";
            echo "  商品项数:   " . count($detail->items) . "\n";
            echo "  原始数据字段数: " . count($detail->rawData) . "\n";
            echo "结果: ✓ 通过\n\n";
        } else {
            echo "结果: ✗ 失败 - 返回为空\n\n";
        }
    }
} catch (\Exception $e) {
    echo "结果: ✗ 失败 - " . $e->getMessage() . "\n\n";
}

// ========== 测试7: 获取订单物流信息 ==========

echo "[测试7] 获取订单物流信息\n";
echo "----------------------------------------\n";
try {
    $listOrders = $wc->order()->getList(['status' => 'completed', 'per_page' => 1]);
    if (empty($listOrders)) {
        echo "  无订单可测试物流接口，跳过\n";
        echo "结果: - 跳过\n\n";
    } else {
        $firstOrderId = $listOrders[0]->orderId;
        $logistics = $wc->order()->getLogistics($firstOrderId);
        echo "  订单 #{$firstOrderId} 物流信息:\n";
        if (empty($logistics)) {
            echo "    (无物流信息)\n";
        } else {
            foreach ($logistics as $line) {
                echo "    " . json_encode($line, JSON_UNESCAPED_UNICODE) . "\n";
            }
        }
        echo "结果: ✓ 通过\n\n";
    }
} catch (\Exception $e) {
    echo "结果: ✗ 失败 - " . $e->getMessage() . "\n\n";
}

// ========== 测试8: 输出 toArray 格式 ==========

echo "[测试8] XOrder 数据格式输出 (toArray)\n";
echo "----------------------------------------\n";
try {
    $orders = $wc->order()->getList(['status' => 'completed', 'per_page' => 1]);
    if (empty($orders)) {
        echo "  无订单数据，跳过\n";
        echo "结果: - 跳过\n\n";
    } else {
        $arr = $orders[0]->toArray();
        echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        echo "结果: ✓ 通过\n\n";
    }
} catch (\Exception $e) {
    echo "结果: ✗ 失败 - " . $e->getMessage() . "\n\n";
}

// ========== 完成 ==========

echo "========================================\n";
echo "  测试完成\n";
echo "========================================\n";
