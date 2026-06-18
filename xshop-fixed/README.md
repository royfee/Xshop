# royfee/xshop

> 多平台电商 SDK 统一入口, 集成淘宝 / 拼多多 / 有赞 / 悦言 等.

通过统一的 `Shop` 入口和各平台 `BaseApi` 抽象, 你可以一行切换平台访问订单、商品、售后等业务数据, 并通过 `OrderMapper` 拿到 `XOrder` / `XOrderItem` 统一数据模型.

## 安装

```bash
composer require royfee/xshop
```

## 快速开始

```php
use royfee\xshop\Shop;
use royfee\xshop\Platforms\PlatformException;

$shop = new Shop(__DIR__ . '/config/shop.php');

// 1. 淘宝: 搜索商品
$list = $shop->taobao->goods->getList(['keyword' => '手机', 'page' => 1]);

// 2. 悦言: 拉取订单 (自动走 Mapper, 返回 XOrder[])
$resp = $shop->yueyan->order->getList();
foreach ($resp['data'] as $order) {
    // $order 是 XOrder 实例, 字段在所有平台一致
    echo $order->orderId, ' / ', $order->paymentAmount, PHP_EOL;
}

// 3. 拼多多: 走 OAuth token
$token = $shop->pdd->access_token->getToken();

// 4. 有赞
$result = $shop->youzan->api->request('youzan.trade.get', ['tid' => 'xxx']);
```

## 配置

复制 `src/Config/shop.php` 到你的项目, **强烈建议** 把真实凭据放在 `config.local.php` (`.gitignore` 已加) 或环境变量, 不要提交到代码仓库.

```php
// config.local.php
return [
    'platforms' => [
        'yueyan' => [
            'class'  => \royfee\xshop\Platforms\Yueyan\Yueyan::class,
            'config' => [
                'app_id'     => getenv('YUEYAN_APP_ID'),
                'app_secret' => getenv('YUEYAN_APP_SECRET'),
                'auth_code'  => getenv('YUEYAN_AUTH_CODE'),
            ],
        ],
    ],
];
```

## 自定义平台

```php
$shop->addPlatform('myPlatform', \My\Platform\Impl::class, [
    'app_key'    => 'xxx',
    'app_secret' => 'yyy',
]);
```

你的平台类只要继承 `\royfee\xshop\Platforms\BasePlatform` 并实现 `getGateway` / `buildSystemParams` / `buildSign` 三个方法即可复用统一 HTTP 调度.

## 日志

```php
$logger = new \Monolog\Logger('xshop');
$logger->pushHandler(new \Monolog\Handler\StreamHandler(__DIR__ . '/xshop.log'));

$shop = new Shop(...);
$shop->taobao->setLogger($logger);
```

## 异常体系

- `\royfee\xshop\Platforms\PlatformException` — 所有平台基类异常, 带 `getRawData()` 取原始返回.
- `\royfee\xshop\Platforms\Youzan\YouzanException`
- `\royfee\xshop\Platforms\Pinduoduo\PinduoduoException`
- `\royfee\xshop\Platforms\Yueyan\YueyanException`

```php
try {
    $shop->yueyan->order->getList();
} catch (\royfee\xshop\Platforms\Yueyan\YueyanException $e) {
    // 平台特定错误
    var_dump($e->getMessage(), $e->getCode(), $e->getRawData());
} catch (\royfee\xshop\Platforms\PlatformException $e) {
    // 通用平台错误
}
```

## 统一数据模型

```php
use royfee\xshop\Data\XOrder;
use royfee\xshop\Data\XOrderItem;

// 通过 Mapper 转换, 跨平台字段一致
$mapper = $shop->yueyan->getMapper();
$order  = $mapper->mapOrder($rawYueyanOrder);
$item   = $mapper->mapItem($rawYueyanItem);
```

## 开发

```bash
composer install
composer test
```

## License

MIT
