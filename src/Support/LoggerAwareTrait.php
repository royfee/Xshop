<?php

namespace Royfee\XShop\Support;

/**
 * 日志辅助
 *
 * 使用方需持有 $logger 属性 (Psr\Log\LoggerInterface|null)。
 */
trait LoggerAwareTrait
{
    protected function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->$level($message, $context);
        }
    }
}
