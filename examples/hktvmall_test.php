<?php
/**
 * HKTVmall (Shoalter) 平台测试脚本
 *
 * 运行方式:
 *   php examples/hktvmall_test.php
 *
 * 配置说明:
 *   修改下方 $config 中的凭证为你的真实值:
 *   - store_code:   你的店铺代码
 *   - api_key:      平台分配的 UUID
 *   - private_key:  RSA 私钥文件路径或 PEM 内容
 *
 * 私钥格式 (PEM):
 *   -----BEGIN RSA PRIVATE KEY-----
 *   ...你的私钥内容...
 *   -----END RSA PRIVATE KEY-----
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
        'hktvmall' => [
            'enabled' => true,
            'base_url' => 'https://merchant-oapi.shoalter.com',
            'store_code' => 'B2070001',           // 你的店铺代码
            'api_key' => '9df4483a-3763-4ac9-b4ae-64dff318ef6a',            // 平台分配的 UUID
            'private_key' => '', // RSA 私钥文件路径或 PEM 内容
            // 'private_key_passphrase' => '',            // 私钥密码 (如有)
            'platform_code' => 'HKTV',
            'business_type' => 'eCommerce',
            'page_size' => 10,
        ],
    ],
];

// ========== 初始化 ==========

$xshop = new XShop($config);
$hktv = $xshop->hktvmall();

echo "========================================\n";
echo "  HKTVmall (Shoalter) 平台测试\n";
echo "========================================\n\n";

// ========== 测试1: JWT Token 生成 ==========

echo "[测试1] JWT Token 生成\n";
echo "----------------------------------------\n";
try {
    $auth = $hktv->auth();
    $tokenData = $auth->getToken();
    $jwt = $tokenData['jwt'];

    echo "  JWT (完整):\n  {$jwt}\n";
    echo "  JWT 长度: " . strlen($jwt) . " 字符\n";
    echo "  签发时间: " . date('Y-m-d H:i:s', $tokenData['iat']) . "\n";
    echo "  过期时间: " . date('Y-m-d H:i:s', $tokenData['expires_at']) . "\n";
    echo "  Token 有效: " . ($auth->isTokenValid() ? '是' : '否') . "\n";

    // 验证 JWT 结构
    $parts = explode('.', $jwt);
    if (count($parts) === 3) {
        $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        echo "  JWT Header alg: " . ($header['alg'] ?? 'N/A') . "\n";
        echo "  JWT Payload sub: " . ($payload['sub'] ?? 'N/A') . "\n";
        echo "  JWT Payload x-api-key: " . substr($payload['x-api-key'] ?? '', 0, 8) . "...\n";
    }

    echo "结果: ✓ 通过\n\n";
} catch (\Exception $e) {
    echo "结果: ✗ 失败 - " . $e->getMessage() . "\n\n";
    echo "请检查:\n";
    echo "  1. private_key 配置是否正确 (文件路径或PEM内容)\n";
    echo "  2. api_key (UUID) 是否正确\n";
    echo "  3. PHP openssl 扩展是否已安装\n\n";
}

// ========== 测试2: 获取订单列表 ==========

echo "[测试2] 获取订单列表\n";
echo "----------------------------------------\n";
try {
    $orders = $hktv->order()->getList([
        'start_time' => date('Y-m-d 00:00:00', strtotime('-30 days')),
        'end_time'   => date('Y-m-d 23:59:59'),
        'page'           => 1,
        'pageSize'       => 5,
    ]);
file_put_contents('11.09.txt',var_export($orders,true));
    echo "  获取到 " . count($orders) . " 条订单\n";

    foreach ($orders as $i => $order) {
        $idx = $i + 1;
        echo "\n  --- 订单 #{$idx} ---\n";
        echo "  订单号:     {$order->orderNo}\n";
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
        echo "  商品项数:   " . count($order->items) . "\n";

        foreach ($order->items as $j => $item) {
            $jdx = $j + 1;
            echo "    [商品{$jdx}] {$item->goodsName} | SKU:{$item->sku} | "
                . "单价:{$item->price} x {$item->quantity} = {$item->subtotal}\n";
        }
    }

    echo "\n结果: ✓ 通过\n\n";
} catch (\Exception $e) {
    echo "结果: ✗ 失败 - " . $e->getMessage() . "\n\n";
    echo "请检查:\n";
    echo "  1. store_code 是否正确\n";
    echo "  2. 网络是否可达 merchant-oapi.shoalter.com\n";
    echo "  3. JWT Token 是否生成成功\n\n";
}

// ========== 测试3: 获取订单详情 ==========

echo "[测试3] 获取订单详情\n";
echo "----------------------------------------\n";
try {
    // 先从列表中获取一个订单号用于测试
    $listOrders = $hktv->order()->getList([
        'orderDateStart' => date('Y-m-d 00:00:00', strtotime('-90 days')),
        'orderDateEnd'   => date('Y-m-d 23:59:59'),
        'page'           => 1,
        'pageSize'       => 1,
    ]);

    if (empty($listOrders)) {
        echo "  无订单可测试详情接口，跳过\n";
        echo "结果: - 跳过\n\n";
    } else {
        $subOrderNo = $listOrders[0]->platformOrderNo;
        echo "  使用子订单号: {$subOrderNo}\n";

        $detail = $hktv->order()->getDetail($subOrderNo);

        if ($detail) {
            echo "  订单号:     {$detail->orderNo}\n";
            echo "  状态:       {$detail->status} ({$detail->statusText})\n";
            echo "  总金额:     {$detail->totalAmount}\n";
            echo "  收货人:     {$detail->receiverName}\n";
            echo "  收货地址:   {$detail->receiverAddress}\n";
            echo "  商品项数:   " . count($detail->items) . "\n";
            echo "  原始数据字段数: " . count($detail->rawData) . "\n";

            echo "\n  --- 原始数据 (rawData) ---\n";
            echo "  " . json_encode($detail->rawData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";

            echo "\n结果: ✓ 通过\n\n";
        } else {
            echo "结果: ✗ 失败 - 返回为空\n\n";
        }
    }
} catch (\Exception $e) {
    echo "结果: ✗ 失败 - " . $e->getMessage() . "\n\n";
}

// ========== 测试4: 输出 toArray 格式 ==========

echo "[测试4] XOrder 数据格式输出 (toArray)\n";
echo "----------------------------------------\n";
try {
    $orders = $hktv->order()->getList([
        'orderDateStart' => date('Y-m-d 00:00:00', strtotime('-30 days')),
        'orderDateEnd'   => date('Y-m-d 23:59:59'),
        'page'           => 1,
        'pageSize'       => 1,
    ]);

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
echo "\n提示: 如果测试失败，请将错误信息和 rawData 输出发给我，\n";
echo "      我会据此调整 Mapper 的字段映射。\n";
