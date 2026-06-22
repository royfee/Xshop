<?php

namespace Royfee\XShop\Http;

use Royfee\XShop\Contracts\HttpClientInterface;
use Royfee\XShop\Logger\Logger;
use Psr\Log\LoggerInterface;

/**
 * HTTP 客户端工厂
 * 支持自定义 HTTP 客户端扩展
 */
class HttpClientFactory
{
    /** @var string|null 自定义HTTP客户端类名 */
    protected static $customClientClass = null;

    /**
     * 设置自定义 HTTP 客户端类
     * @param string $className 必须实现 HttpClientInterface 或继承 HttpClient
     */
    public static function setCustomClient(string $className): void
    {
        if (!is_subclass_of($className, HttpClientInterface::class)) {
            throw new \InvalidArgumentException(
                "Custom HTTP client must implement " . HttpClientInterface::class
            );
        }
        self::$customClientClass = $className;
    }

    /**
     * 创建 HTTP 客户端实例
     */
    public static function create(array $config = [], ?LoggerInterface $logger = null, bool $debug = false): HttpClientInterface
    {
        $class = self::$customClientClass ?? HttpClient::class;
        return new $class($config, $logger, $debug);
    }
}
