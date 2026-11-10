<?php

namespace Royfee\XShop\Platforms;

use Psr\Log\LoggerInterface;
use Royfee\XShop\Contracts\AuthInterface;
use Royfee\XShop\Contracts\HttpClientInterface;
use Royfee\XShop\Contracts\PlatformInterface;
use Royfee\XShop\Mapper\AbstractMapper;
use Royfee\XShop\Support\LoggerAwareTrait;

/**
 * 平台 API 模块抽象基类
 *
 * 收纳各平台 Order/Goods 模块共有的依赖装配、统一参数映射、日志与面单默认行为，
 * 子类只需实现 createMapper() 与自身的接口方法。
 */
abstract class AbstractApiResource
{
    use LoggerAwareTrait;

    /** @var array 平台配置 */
    protected $config;

    /** @var HttpClientInterface HTTP 客户端 */
    protected $http;

    /** @var AuthInterface 认证模块 */
    protected $auth;

    /** @var LoggerInterface|null 日志 */
    protected $logger;

    /** @var AbstractMapper 数据映射器 */
    protected $mapper;

    /** @var PlatformInterface|null 所属平台，用于统一参数映射 */
    protected $platform;

    public function __construct(
        array $config,
        HttpClientInterface $http,
        AuthInterface $auth,
        ?LoggerInterface $logger = null,
        ?PlatformInterface $platform = null
    ) {
        $this->config = $config;
        $this->http = $http;
        $this->auth = $auth;
        $this->logger = $logger;
        $this->platform = $platform;
        $this->mapper = $this->createMapper();
    }

    /**
     * 返回本模块使用的映射器
     */
    abstract protected function createMapper(): AbstractMapper;

    /**
     * 校验平台响应，失败时抛出异常
     */
    abstract protected function checkResponseError(array $response, string $context): void;

    /**
     * 将 xshop 统一参数转为平台原生参数
     */
    protected function prepareParams(array $params): array
    {
        return $this->platform ? $this->platform->mapParams($params) : $params;
    }

    /**
     * 面单打印：平台默认不支持，支持的平台自行覆写
     */
    public function getLabel($param, array $options = []): array
    {
        return [];
    }
}
