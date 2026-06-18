<?php
namespace Royfee\XShop\Platforms\Yueyan;

use Royfee\XShop\Contracts\PlatformInterface;
use Royfee\XShop\Contracts\GoodsApiInterface;
use Royfee\XShop\Contracts\OrderApiInterface;
use Royfee\XShop\Contracts\DecryptApiInterface;

use Royfee\XShop\Core\HttpClient;
use Royfee\XShop\Platforms\Yueyan\Api\OrderApi;
use Royfee\XShop\Platforms\Yueyan\Api\GoodsApi;

class YueyanPlatform implements PlatformInterface
{
    protected $config;
    protected $http;
    protected $auth;

    public function __construct(array $config, HttpClient $http)
    {
        $this->config = $config;
        $this->http   = $http;
    }

    public function http(): HttpClient
    {
        return $this->http;
    }

    public function auth(): YueyanAuth
    {
        return new YueyanAuth();
    }

    public function goods(): GoodsApiInterface
    {
        return new GoodsApi($this->auth(), $this->http(), $this->config);
    }

    public function order(): OrderApiInterface
    {
        return new OrderApi($this->auth(), $this->http(), $this->config);
    }
}