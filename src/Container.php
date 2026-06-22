<?php

namespace Royfee\XShop;

use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface as PsrCacheInterface;
use Psr\Log\LoggerInterface;
use Royfee\XShop\Cache\Cache;
use Royfee\XShop\Logger\Logger;
use Royfee\XShop\Http\HttpClientFactory;
use Royfee\XShop\Contracts\HttpClientInterface;

/**
 * 服务容器 - 惰性加载，按需实例化
 */
class Container implements ContainerInterface
{
    /** @var array 配置 */
    protected $config = [];

    /** @var array 实例缓存 */
    protected $instances = [];

    /** @var array 已注册的工厂方法 */
    protected $factories = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->registerDefaults();
    }

    protected function registerDefaults(): void
    {
        // 日志服务
        $this->factories['logger'] = function () {
            $loggerConfig = $this->config['logger'] ?? [];
            Logger::configure($loggerConfig);

            if (!empty($loggerConfig['handler']) && class_exists($loggerConfig['handler'])) {
                $handler = new $loggerConfig['handler']();
                if ($handler instanceof LoggerInterface) {
                    Logger::setCustomLogger($handler);
                }
            }

            return Logger::getInstance();
        };

        // 缓存服务
        $this->factories['cache'] = function () {
            $cacheConfig = $this->config['cache'] ?? [];
            $handler = null;

            // 支持传入实例对象或类名字符串
            if (!empty($cacheConfig['handler'])) {
                if ($cacheConfig['handler'] instanceof PsrCacheInterface) {
                    // 传入实例对象 (推荐)
                    $handler = $cacheConfig['handler'];
                } elseif (is_string($cacheConfig['handler']) && class_exists($cacheConfig['handler'])) {
                    // 传入类名字符串
                    $instance = new $cacheConfig['handler']();
                    if ($instance instanceof PsrCacheInterface) {
                        $handler = $instance;
                    }
                }
            }

            return new Cache($handler, 'xshop_', $cacheConfig['cache_dir'] ?? null);
        };

        // HTTP 客户端
        $this->factories['http'] = function () {
            $httpConfig = $this->config['http'] ?? [];
            $logger = $this->get('logger');
            $debug = $this->config['debug'] ?? false;

            if (!empty($httpConfig['client']) && class_exists($httpConfig['client'])) {
                HttpClientFactory::setCustomClient($httpConfig['client']);
            }

            return HttpClientFactory::create($httpConfig, $logger, $debug);
        };
    }

    public function register(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    public function get($id)
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->factories[$id])) {
            throw new \RuntimeException("Service not found: {$id}");
        }

        $instance = ($this->factories[$id])();
        $this->instances[$id] = $instance;

        return $instance;
    }

    public function has($id): bool
    {
        return isset($this->factories[$id]) || isset($this->instances[$id]);
    }

    public function getConfig(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }
        return $this->config[$key] ?? $default;
    }

    public function getPlatformConfig(string $platform): array
    {
        return $this->config['platforms'][$platform] ?? [];
    }
}
