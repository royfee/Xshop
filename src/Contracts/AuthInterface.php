<?php

namespace Royfee\XShop\Contracts;

/**
 * 认证接口 - 所有平台的认证模块必须实现
 */
interface AuthInterface
{
    /**
     * 获取访问令牌
     * @param string|null $code 授权码 (OAuth授权码模式需要)
     * @return array 返回token信息
     */
    public function getToken(?string $code = null): array;

    /**
     * 刷新访问令牌
     * @param string $refreshToken 刷新令牌
     * @return array 返回新的token信息
     */
    public function refreshToken(string $refreshToken): array;

    /**
     * 获取授权URL (用于引导用户授权)
     * @param string|null $redirectUri 回调地址
     * @param string|null $state 状态参数
     * @return string
     */
    public function getAuthorizeUrl(?string $redirectUri = null, ?string $state = null): string;

    /**
     * 检查token是否有效
     * @return bool
     */
    public function isTokenValid(): bool;
}
