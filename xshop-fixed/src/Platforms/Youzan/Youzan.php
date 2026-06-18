<?php
namespace royfee\xshop\Platforms\Youzan;

use royfee\xshop\Platforms\BasePlatform;

/**
 * 有赞开放平台
 */
class Youzan extends BasePlatform
{
    public function getPlatformName(): string
    {
        return 'youzan';
    }

    public function registerModules($pimple)
    {
        $pimple['token'] = function ($app) {
            return new AccessToken(
                $this->config['client_id'],
                $this->config['client_secret'],
                $this->config['kdt_id'] ?? null,
                $app
            );
        };

        $pimple['api'] = function ($pimple) {
            return new Api($pimple);
        };
    }

    protected function getGateway(): string
    {
        // 实际网关按 method 拼装, 这里给一个占位
        return 'https://open.youzanyun.com/api/';
    }

    /**
     * 有赞 API 没有统一 system 参数, 由 Api::request 自行拼装
     */
    protected function buildSystemParams(string $method): array
    {
        return [];
    }

    protected function buildSign(array $params): string
    {
        return '';
    }

    /**
     * 有赞的请求走 Api 模块的独立 request 流程
     */
    public function request(string $method, array $params = []): array
    {
        return $this->offsetGet('api')->request($method, $params);
    }

    public function getVersion(): string
    {
        $version = $this->config['version'] ?? null;
        if (!$version) {
            throw new YouzanException('version cannot be null');
        }
        return (string)$version;
    }
}
