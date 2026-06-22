<?php
/**
 * ThinkPHP 中使用 XShop 示例
 * 
 * 关键: 在 ThinkPHP 中，使用框架的 env() 或 config() 读取配置，
 * 然后传入 XShop，不要依赖包内的 env() 函数。
 */

// ========== 1. ThinkPHP 配置文件 ==========
// config/xshop.php

/*
<?php
return [
    'debug' => env('XSHOP_DEBUG', true),
    'logger' => [
        'enabled' => env('XSHOP_LOGGER_ENABLED', true),
        'path' => env('XSHOP_LOGGER_PATH', null),
        'level' => env('XSHOP_LOGGER_LEVEL', 'debug'),
    ],
    'cache' => [
        'ttl' => env('XSHOP_CACHE_TTL', 7000),
    ],
    'http' => [
        'timeout' => env('XSHOP_HTTP_TIMEOUT', 30),
    ],
    'platforms' => [
        'pdd' => [
            'enabled' => env('PDD_ENABLED', true),
            'client_id' => env('PDD_CLIENT_ID', ''),           // 从 .env 读取
            'client_secret' => env('PDD_CLIENT_SECRET', ''),   // 从 .env 读取
            'redirect_uri' => env('PDD_REDIRECT_URI', ''),     // 从 .env 读取
            'api_url' => 'https://gw-api.pinduoduo.com/api/router',
            'auth_url' => 'https://open-api.pinduoduo.com/oauth/token',
        ],
    ],
];
*/

// ========== 2. .env 文件配置 ==========

/*
PDD_CLIENT_ID=your_client_id_here
PDD_CLIENT_SECRET=your_client_secret_here
PDD_REDIRECT_URI=https://your-site.com/callback
*/

// ========== 3. 控制器中使用 ==========

namespace app\controller;

use think\Controller;
use Royfee\XShop\XShop;

class PddController extends Controller
{
    protected $xshop;

    public function initialize()
    {
        // 读取 ThinkPHP 配置
        $config = config('xshop');

        // 传入 XShop
        $this->xshop = new XShop($config);
    }

    /**
     * 获取授权URL
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

        $pdd = $this->xshop->pdd();
        $token = $pdd->auth()->getToken($code);

        $mallId = (string) ($token['owner_id'] ?? '');

        // 保存 mall_id 到数据库
        // ...

        return json(['code' => 0, 'mall_id' => $mallId]);
    }

    /**
     * 获取订单
     */
    public function orders()
    {
        $mallId = $this->request->param('mall_id');

        $pdd = $this->xshop->pdd(['mall_id' => $mallId]);
        $orders = $pdd->order()->getList([
            'page' => 1,
            'page_size' => 20,
        ]);

        return json([
            'code' => 0,
            'data' => array_map(function($o) {
                return $o->toArray();
            }, $orders),
        ]);
    }
}
