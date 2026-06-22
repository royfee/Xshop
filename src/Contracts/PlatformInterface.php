<?php

namespace Royfee\XShop\Contracts;

/**
 * 平台接口 - 所有平台必须实现此接口
 */
interface PlatformInterface
{
    /**
     * 获取平台名称
     * @return string
     */
    public function getName(): string;

    /**
     * 获取平台标识
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * 获取认证模块
     * @return AuthInterface
     */
    public function auth(): AuthInterface;

    /**
     * 获取订单接口
     * @return OrderInterface
     */
    public function order(): OrderInterface;

    /**
     * 获取商品接口
     * @return GoodsInterface
     */
    public function goods(): GoodsInterface;

    /**
     * 获取HTTP客户端
     * @return HttpClientInterface
     */
    public function http(): HttpClientInterface;
}
