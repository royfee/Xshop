<?php
namespace Royfee\XShop\Platforms\Pdd;

use Royfee\XShop\Contracts\PlatformInterface;
use Royfee\XShop\Contracts\GoodsApiInterface;
use Royfee\XShop\Contracts\OrderApiInterface;
use Royfee\XShop\Contracts\DecryptApiInterface;

use Royfee\XShop\Core\HttpClient;
use Royfee\XShop\Platforms\Pdd\Api\GoodsApi;
use Royfee\XShop\Platforms\Pdd\Api\OrderApi;
use Royfee\XShop\Platforms\Pdd\Api\DecryptApi;

class PddPlatform implements PlatformInterface
{
    protected $config;
    protected $http;
    protected $auth;

    public function __construct(array $config, HttpClient $http)
    {
        $this->config = $config;
        $this->http   = $http;
    }

    public function auth(): PddAuth
    {
        if (!$this->auth) {
            $this->auth = new PddAuth($this->config, $this->http);
        }
        return $this->auth;
    }

    public function http(): HttpClient
    {
        return $this->http;
    }

    public function goods(): GoodsApiInterface
    {
        return new GoodsApi($this->auth(), $this->http(), $this->config);
    }

    // 修正：返回契约层的 OrderApiInterface
    public function order(): OrderApiInterface
    {
        return new OrderApi($this->auth(), $this->http(), $this->config);
    }

    public function decrypt(): DecryptApiInterface
    {
        return new DecryptApi($this->auth, $this->http, $this->config);
    }    
}