<?php
namespace Royfee\XShop;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Royfee\XShop\Core\Container;
use Royfee\XShop\Core\HttpClient;
use Royfee\XShop\Platforms\Pdd\PddPlatform;
use Royfee\XShop\Platforms\Yueyan\YueyanPlatform;

class XShop
{
    protected $container;
    protected $config;
    protected $logger;
    protected $platforms = [];

    public function __construct(array $config = [])
    {
        $this->config = $config ?: require __DIR__ . '/../config/xshop.php';
        $this->container = Container::getInstance();
        $this->initLogger();
        $this->bindPlatforms();
    }

    // 初始化日志（支持自定义，无配置则使用默认）
    protected function initLogger()
    {
        $loggerConfig = $this->config['logger'];

        $this->logger = new Logger($loggerConfig['channel']);
        $this->logger->pushHandler(new StreamHandler(
            $loggerConfig['path'],
            $loggerConfig['level']
        ));
    }

    // 绑定平台到容器
    protected function bindPlatforms()
    {
        $this->container->bind('pdd', function () {
            $http = new HttpClient($this->logger, $this->config['logger']['debug']);
            return new PddPlatform($this->config['platforms']['pdd'], $http);
        });

        $this->container->bind('yueyan', function () {
            return new YueyanPlatform(
                $this->config['platforms']['yueyan'], 
                new HttpClient($this->logger, $this->config['logger']['debug'])
            );
        });
    }

    // 获取平台实例（惰性加载）
    public function platform(string $name = null)
    {
        $name = $name ?: $this->config['default'];
        if (isset($this->platforms[$name])) {
            return $this->platforms[$name];
        }
        return $this->platforms[$name] = $this->container->make($name);
    }

    // 静态调用
    public static function make(array $config = [])
    {
        return new self($config);
    }
}