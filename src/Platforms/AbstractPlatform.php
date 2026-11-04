<?php

namespace Royfee\XShop\Platforms;

use Royfee\XShop\Contracts\PlatformInterface;
use Royfee\XShop\Contracts\HttpClientInterface;
use Royfee\XShop\Container;

/**
 * 平台抽象基类
 * 
 * 提供通用方法和参数映射逻辑
 */
abstract class AbstractPlatform implements PlatformInterface
{
    /** @var array 平台配置 */
    protected $config;

    /** @var Container 服务容器 */
    protected $container;

    /** @var HttpClientInterface|null HTTP客户端 */
    protected $httpInstance = null;

    /**
     * 参数映射：[xshop参数 => 平台参数]
     * 各平台子类需要定义自己的映射关系
     */
    protected $paramMap = [];

    /**
     * 状态映射：[xshop状态 => 平台状态]
     * 各平台子类需要定义自己的状态映射
     */
    protected $statusMap = [];

    public function __construct(array $config, Container $container)
    {
        $this->config = $config;
        $this->container = $container;
    }

    /**
     * 获取HTTP客户端 (惰性加载)
     */
    public function http(): HttpClientInterface
    {
        if ($this->httpInstance === null) {
            $this->httpInstance = $this->container->get('http');
        }
        return $this->httpInstance;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * 将 xshop 统一参数转为平台原生参数
     * 
     * @param array $params xshop 统一参数
     * @return array 平台原生参数
     */
    public function mapParams(array $params): array
    {
        $mapped = [];

        foreach ($params as $key => $value) {
            if (isset($this->paramMap[$key])) {
                $platformKey = $this->paramMap[$key];
                if ($key === 'status' && isset($this->statusMap[$value])) {
                    $mapped[$platformKey] = $this->statusMap[$value];
                } else {
                    $mapped[$platformKey] = $value;
                }
            } else {
                $mapped[$key] = $value;
            }
        }

        return $mapped;
    }
}
