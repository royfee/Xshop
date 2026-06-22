<?php

namespace Royfee\XShop\Logger;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LoggerInterface;

/**
 * 日志管理器
 * 默认使用 Monolog，支持自定义 Logger 适配器
 */
class Logger
{
    /** @var LoggerInterface|null 自定义 Logger */
    protected static $customLogger = null;

    /** @var MonologLogger|null 默认 Logger */
    protected static $defaultLogger = null;

    /** @var array 日志配置 */
    protected static $config = [
        'enabled' => true,
        'path' => null,
        'level' => 'debug',
    ];

    /**
     * 设置自定义 Logger
     * @param LoggerInterface $logger
     */
    public static function setCustomLogger(LoggerInterface $logger): void
    {
        self::$customLogger = $logger;
    }

    /**
     * 配置日志
     */
    public static function configure(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
        self::$defaultLogger = null; // 重置默认logger，下次获取时重新创建
    }

    /**
     * 获取 Logger 实例
     */
    public static function getInstance(): LoggerInterface
    {
        if (self::$customLogger) {
            return self::$customLogger;
        }

        if (self::$defaultLogger) {
            return self::$defaultLogger;
        }

        return self::$defaultLogger = self::createDefaultLogger();
    }

    /**
     * 创建默认 Logger (Monolog)
     */
    protected static function createDefaultLogger(): MonologLogger
    {
        $logger = new MonologLogger('xshop');

        $level = self::parseLevel(self::$config['level'] ?? 'debug');
        $path = self::$config['path'] ?? sys_get_temp_dir() . '/xshop.log';

        $handler = new StreamHandler($path, $level);

        // 自定义格式：包含时间、级别、消息、上下文
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s.u',
            true,
            true
        );
        $handler->setFormatter($formatter);

        $logger->pushHandler($handler);

        return $logger;
    }

    /**
     * 解析日志级别
     */
    protected static function parseLevel(string $level): int
    {
        $levels = [
            'debug' => MonologLogger::DEBUG,
            'info' => MonologLogger::INFO,
            'notice' => MonologLogger::NOTICE,
            'warning' => MonologLogger::WARNING,
            'error' => MonologLogger::ERROR,
            'critical' => MonologLogger::CRITICAL,
            'alert' => MonologLogger::ALERT,
            'emergency' => MonologLogger::EMERGENCY,
        ];

        return $levels[strtolower($level)] ?? MonologLogger::DEBUG;
    }

    /**
     * 快捷方法：debug
     */
    public static function debug(string $message, array $context = []): void
    {
        self::getInstance()->debug($message, $context);
    }

    /**
     * 快捷方法：info
     */
    public static function info(string $message, array $context = []): void
    {
        self::getInstance()->info($message, $context);
    }

    /**
     * 快捷方法：warning
     */
    public static function warning(string $message, array $context = []): void
    {
        self::getInstance()->warning($message, $context);
    }

    /**
     * 快捷方法：error
     */
    public static function error(string $message, array $context = []): void
    {
        self::getInstance()->error($message, $context);
    }
}
