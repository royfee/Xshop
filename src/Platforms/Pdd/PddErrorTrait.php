<?php

namespace Royfee\XShop\Platforms\Pdd;

/**
 * 拼多多响应错误解析
 *
 * 拼多多网关与 OAuth 端点使用同一套错误信封:
 *   {"error_response": {"error_code": 70012, "error_msg": "code\u5df2\u8fc7\u671f"}}
 * Api 模块与 Auth 模块共用此解析逻辑，使用方需同时引入 LoggerAwareTrait。
 */
trait PddErrorTrait
{
    /** @var array 视为 access_token 失效、需重新授权的错误码 */
    protected $tokenExpiredCodes = [70012, 70014, 70016];

    /**
     * 检查响应中的错误
     */
    protected function checkResponseError(array $response, string $context): void
    {
        if (!isset($response['error_response'])) {
            return;
        }

        $error = $response['error_response'];
        $errorCode = $error['error_code'] ?? 0;
        $errorMsg = $this->decodeUnicode($error['error_msg'] ?? 'Unknown error');

        $this->log('error', "PDD API error during {$context}", array_merge([
            'error_code' => $errorCode,
            'error_msg' => $errorMsg,
        ], $this->errorContext()));

        if (in_array($errorCode, $this->tokenExpiredCodes)) {
            throw new \RuntimeException("PDD token expired [{$errorCode}]: {$errorMsg}", $errorCode);
        }

        throw new \RuntimeException("PDD API error [{$errorCode}]: {$errorMsg}", $errorCode);
    }

    /**
     * 错误日志的附加上下文，由使用方按需覆写
     */
    protected function errorContext(): array
    {
        return [];
    }

    /**
     * 解码 Unicode 转义字符 (如 "code\u5df2\u8fc7\u671f" -> "code已过期")
     */
    protected function decodeUnicode(string $str): string
    {
        return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($matches) {
            return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UCS-2BE');
        }, $str);
    }
}
