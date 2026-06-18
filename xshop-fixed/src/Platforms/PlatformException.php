<?php
namespace royfee\xshop\Platforms;

use Exception;
use Throwable;

/**
 * 所有平台公共异常的基类
 */
class PlatformException extends Exception
{
    /** @var array 平台原始返回数据, 方便上层排查 */
    protected $rawData = [];

    public function __construct(string $message = '', int $code = 0, array $rawData = [], Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->rawData = $rawData;
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }
}
