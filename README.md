# Royfee XShop

多平台电商 SDK 统一封装库，支持拼多多、淘宝、京东等平台，提供统一的 API 接口和数据格式。

## 安装

```bash
composer require royfee/xshop
```

## 快速开始

```php
use Royfee\XShop\XShop;

// 初始化
$xshop = new XShop(__DIR__ . '/config/xshop.php');

// 获取拼多多平台
$pdd = $xshop->pdd();

// 获取订单列表
$orders = $pdd->order()->getList(['page' => 1, 'page_size' => 20]);

// 获取商品列表
$goods = $pdd->goods()->getList(['page' => 1, 'page_size' => 10]);
```

## 核心特性

- **统一接口**：所有平台订单/商品映射为 `XOrder`/`XGoods` 统一格式
- **集中配置**：单文件 `config/xshop.php` 管理所有平台配置
- **自定义 Logger**：支持 Monolog 或自定义日志适配器 (PSR-3)
- **自定义 Cache**：支持文件缓存或自定义缓存适配器 (PSR-16)
- **Token 缓存**：OAuth token 自动缓存，支持自动刷新
- **HTTP 独立**：封装 GuzzleHttp，支持自定义 HTTP 客户端
- **容器化**：惰性加载，按需实例化

## 目录结构

```
src/
├── Contracts/     # 接口定义 (PSR-3, PSR-16, 自定义接口)
├── Data/          # 统一数据格式 (XOrder, XGoods)
├── Mapper/        # 映射基类
├── Http/          # HTTP 客户端
├── Cache/         # 缓存管理 (FileCache + 扩展指南)
├── Logger/        # 日志管理 (Monolog)
├── Container.php  # 服务容器
├── XShop.php      # 核心工厂类
└── Platforms/     # 各平台实现
    └── Pdd/       # 拼多多平台
```

## 自定义缓存适配器

本包内置 `FileCache` 作为默认缓存。生产环境建议自定义缓存适配器。

### 要求

自定义缓存必须实现 **PSR-16** `Psr\SimpleCache\CacheInterface` 接口。

### ThinkPHP 示例

```php
// 1. 创建适配器
namespace app\common\cache;

use Psr\SimpleCache\CacheInterface;
use think\facade\Cache as ThinkCache;

class ThinkCacheAdapter implements CacheInterface
{
    protected $prefix = 'xshop_';

    public function get($key, $default = null) {
        return ThinkCache::get($this->prefix . $key, $default);
    }

    public function set($key, $value, $ttl = null) {
        return ThinkCache::set($this->prefix . $key, $value, $ttl);
    }

    // ... 其他 PSR-16 方法
}

// 2. 使用
use app\common\cache\ThinkCacheAdapter;

$xshop = new XShop([
    'cache' => [
        'handler' => new ThinkCacheAdapter(),  // 传入实例
    ],
    // ... 其他配置
]);
```

### Laravel 示例

```php
namespace App\Services;

use Psr\SimpleCache\CacheInterface;
use Illuminate\Support\Facades\Cache;

class XShopCacheAdapter implements CacheInterface
{
    protected $prefix = 'xshop_';

    public function get($key, $default = null) {
        return Cache::get($this->prefix . $key, $default);
    }

    public function set($key, $value, $ttl = null) {
        return Cache::put($this->prefix . $key, $value, $ttl);
    }

    // ... 其他 PSR-16 方法
}
```

### 数据库缓存示例 (与店铺表关联)

```php
class DbCacheAdapter implements CacheInterface
{
    protected $pdo;
    protected $prefix = 'xshop_';

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function get($key, $default = null) {
        $row = $this->pdo->prepare("SELECT cache_value FROM xshop_tokens 
            WHERE cache_key = ? AND expire_at > ?")
            ->execute([$this->prefix . $key, time()])
            ->fetchColumn();
        return $row ? unserialize($row) : $default;
    }

    public function set($key, $value, $ttl = null) {
        // 保存 token，同步更新店铺表
        // ...
    }

    // ... 其他 PSR-16 方法
}
```

## 多店铺支持

单个应用可授权多个拼多多店铺，通过 `mall_id` 区分：

```php
// 授权 (mall_id 从响应中获取)
$pdd = $xshop->pdd();
$token = $pdd->auth()->getToken('code_here');
$mallId = $token['owner_id'];  // "4567890"

// 后续调用指定店铺
$shopA = $xshop->pdd(['mall_id' => '4567890']);
$shopB = $xshop->pdd(['mall_id' => '7890123']);

$ordersA = $shopA->order()->getList(['page' => 1]);
$ordersB = $shopB->order()->getList(['page' => 1]);
```

## 扩展新平台

1. 实现 `PlatformInterface`
2. 实现 `AuthInterface`、`OrderInterface`、`GoodsInterface`
3. 创建 Mapper 继承 `AbstractMapper`
4. 注册平台：`XShop::registerPlatform('new_platform', NewPlatform::class)`

## License

MIT
