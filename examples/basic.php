<?php
/**
 * XShop 基础使用示例
 * 
 * 配置方式:
 * 1. 直接传入配置数组 (推荐，最可靠)
 * 2. 传入配置文件路径
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Royfee\XShop\XShop;

// ========== 方式1: 直接传入配置数组 (推荐) ==========

$config = [
    'debug' => true,
    'logger' => ['enabled' => true],
    'cache' => ['ttl' => 7000],
    'http' => ['timeout' => 30],
    'platforms' => [
        'pdd' => [
            'enabled' => true,
            'client_id' => 'your_client_id_here',          // 从拼多多开放平台获取
            'client_secret' => 'your_client_secret_here',   // 从拼多多开放平台获取
            'redirect_uri' => 'https://your-site.com/callback',
            'api_url' => 'https://gw-api.pinduoduo.com/api/router',
            'auth_url' => 'https://open-api.pinduoduo.com/oauth/token',
        ],
    ],
];

//$xshop = new XShop($config);
$xshop = new XShop(__DIR__ . '/../config/xshop.php');

// ========== 方式2: 传入配置文件路径 ==========
// 配置文件内容就是上面的数组，不需要 env() 函数
// $xshop = new XShop(__DIR__ . '/../config/xshop.php');

// ========== 方式3: 在框架中使用 (如 ThinkPHP) ==========
/*
// 在 ThinkPHP 的 config/xshop.php 中:
return [
    'platforms' => [
        'pdd' => [
            'client_id' => env('PDD_CLIENT_ID', ''),
            'client_secret' => env('PDD_CLIENT_SECRET', ''),
            'redirect_uri' => env('PDD_REDIRECT_URI', ''),
        ],
    ],
];

// 控制器中:
$config = config('xshop');
$xshop = new XShop($config);
*/

// ========== 授权流程 ==========

// 1. 获取授权URL
$pdd = $xshop->pdd();
//$authUrl = $pdd->auth()->getAuthorizeUrl();
//echo "授权URL: " . $authUrl . "\n\n";exit;

// 2. 用户授权后，回调处理
$code = '4b5fba120c474b438e20391c91d942649cc0b175';//$_GET['code'];
$token = $pdd->auth()->getToken($code);
var_dump($token);
// $mallId = $token['owner_id'];

// 3. 后续调用指定店铺
// $shop = $xshop->pdd(['mall_id' => $mallId]);
// $orders = $shop->order()->getList(['page' => 1]);
