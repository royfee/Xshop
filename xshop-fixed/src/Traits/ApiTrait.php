<?php
namespace royfee\xshop\Traits;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * 提供平台级日志能力.
 * 期望使用方通过 setLogger() 注入一个 PSR-3 Logger (e.g. Monolog).
 */
trait ApiTrait
{
    /** @var LoggerInterface|null */
    protected $logger;

    /**
     * 注入 PSR-3 Logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * 平台级日志, 默认空实现, 由使用方按需注入
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        ($this->logger ?? new NullLogger())->log($level, $message, $context);
    }
}
