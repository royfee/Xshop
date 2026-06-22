<?php
/**
 * ThinkPHP 中完整使用 XShop 示例
 * 
 * 包含:
 * 1. 模型定义
 * 2. 缓存适配器
 * 3. 控制器使用
 * 4. 定时任务
 */

// ========== 1. 配置 ==========
// config/xshop.php

return [
    'debug' => env('XSHOP_DEBUG', true),
    'logger' => [
        'enabled' => env('XSHOP_LOGGER_ENABLED', true),
        'path' => env('XSHOP_LOGGER_PATH', runtime_path() . 'log/xshop.log'),
        'level' => 'debug',
    ],
    'cache' => [
        // 不在这里配置 handler，在控制器中传入
        'ttl' => 7000,
    ],
    'http' => [
        'timeout' => 30,
        'connect_timeout' => 10,
        'retries' => 3,
    ],
    'platforms' => [
        'pdd' => [
            'enabled' => true,
            'client_id' => env('PDD_CLIENT_ID', ''),
            'client_secret' => env('PDD_CLIENT_SECRET', ''),
            'redirect_uri' => env('PDD_REDIRECT_URI', ''),
            'api_url' => 'https://gw-api.pinduoduo.com/api/router',
            'auth_url' => 'https://open-api.pinduoduo.com/oauth/token',
        ],
    ],
];

// ========== 2. 控制器 ==========
// app/controller/PddController.php

namespace app\controller;

use think\Controller;
use Royfee\XShop\XShop;
use app\common\cache\ThinkPhpDbCache;

class PddController extends Controller
{
    protected $xshop;
    protected $cache;

    public function initialize()
    {
        // 初始化数据库缓存适配器 (ThinkPHP模型实现)
        $this->cache = new ThinkPhpDbCache();

        // 读取配置
        $config = config('xshop');
        $config['cache']['handler'] = $this->cache;

        // 初始化 XShop
        $this->xshop = new XShop($config);
    }

    /**
     * 授权入口 - 跳转到拼多多授权页
     */
    public function auth()
    {
        $pdd = $this->xshop->pdd();
        $url = $pdd->auth()->getAuthorizeUrl();

        return redirect($url);
    }

    /**
     * 授权回调
     */
    public function callback()
    {
        $code = $this->request->get('code');
        $state = $this->request->get('state');

        try {
            $pdd = $this->xshop->pdd();
            $token = $pdd->auth()->getToken($code);

            $mallId = (string) ($token['owner_id'] ?? '');
            $mallName = $token['owner_name'] ?? '';

            // 保存店铺信息到数据库
            $this->cache->updateShop($mallId, [
                'mall_name' => $mallName,
                'status' => 1,
                'token_status' => 'valid',
            ]);

            return json(['code' => 0, 'msg' => '授权成功', 'data' => [
                'mall_id' => $mallId,
                'mall_name' => $mallName,
            ]]);

        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 获取所有店铺列表
     */
    public function shops()
    {
        $shops = $this->cache->getShopList(['status' => 1]);
        return json(['code' => 0, 'data' => $shops]);
    }

    /**
     * 获取指定店铺订单
     */
    public function orders($mallId)
    {
        $shop = $this->cache->getShopInfo($mallId);
        if (!$shop || $shop['status'] != 1) {
            return json(['code' => 1, 'msg' => '店铺不存在或已禁用']);
        }

        try {
            $pdd = $this->xshop->pdd(['mall_id' => $mallId]);

            $orders = $pdd->order()->getList([
                'order_status' => 1,
                'page' => $this->request->param('page', 1),
                'page_size' => $this->request->param('page_size', 20),
            ]);

            return json(['code' => 0, 'data' => [
                'shop_name' => $shop['mall_name'],
                'total' => count($orders),
                'list' => array_map(function($o) {
                    return $o->toArray();
                }, $orders),
            ]]);

        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 获取所有有效店铺的订单 (批量)
     */
    public function allOrders()
    {
        $shops = $this->cache->getValidShops();
        $result = [];

        foreach ($shops as $shop) {
            try {
                $pdd = $this->xshop->pdd(['mall_id' => $shop['mall_id']]);
                $orders = $pdd->order()->getList([
                    'order_status' => 1,
                    'page' => 1,
                    'page_size' => 50,
                ]);

                $result[] = [
                    'mall_id' => $shop['mall_id'],
                    'mall_name' => $shop['mall_name'],
                    'order_count' => count($orders),
                ];

            } catch (\Exception $e) {
                $result[] = [
                    'mall_id' => $shop['mall_id'],
                    'mall_name' => $shop['mall_name'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return json(['code' => 0, 'data' => $result]);
    }

    /**
     * 发货
     */
    public function send()
    {
        $mallId = $this->request->post('mall_id');
        $orderSn = $this->request->post('order_sn');
        $trackingNo = $this->request->post('tracking_no');
        $shippingId = $this->request->post('shipping_id');

        try {
            $pdd = $this->xshop->pdd(['mall_id' => $mallId]);
            $result = $pdd->order()->send($orderSn, [
                'tracking_number' => $trackingNo,
                'shipping_id' => $shippingId,
            ]);

            return json(['code' => 0, 'data' => $result]);

        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }
}

// ========== 3. 定时任务 (刷新即将过期的token) ==========
// app/command/RefreshToken.php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use Royfee\XShop\XShop;
use app\common\cache\ThinkPhpDbCache;

class RefreshToken extends Command
{
    protected function configure()
    {
        $this->setName('xshop:refresh-token')
            ->setDescription('刷新即将过期的拼多多token');
    }

    protected function execute(Input $input, Output $output)
    {
        $cache = new ThinkPhpDbCache();
        $config = config('xshop');
        $config['cache']['handler'] = $cache;
        $xshop = new XShop($config);

        // 获取1小时内即将过期的token
        $expiring = $cache->getExpiringTokens(3600);

        $output->writeln("发现 " . count($expiring) . " 个即将过期的token");

        foreach ($expiring as $shop) {
            try {
                $pdd = $xshop->pdd(['mall_id' => $shop['mall_id']]);
                $token = $pdd->auth()->getCachedToken();

                if ($token && !empty($token['refresh_token'])) {
                    $pdd->auth()->refreshToken($token['refresh_token']);
                    $output->writeln("✓ 店铺 {$shop['mall_name']} token刷新成功");
                }

            } catch (\Exception $e) {
                $output->writeln("✗ 店铺 {$shop['mall_name']} token刷新失败: " . $e->getMessage());

                // 标记为无效
                $cache->updateShop($shop['mall_id'], ['token_status' => 'invalid']);
            }
        }

        $output->writeln("完成");
    }
}

// 注册命令: config/console.php
// 'commands' => [
//     'app\command\RefreshToken',
// ]

// 执行: php think xshop:refresh-token
// 加入crontab: */10 * * * * cd /path && php think xshop:refresh-token
