<?php
namespace Royfee\XShop\Platforms\Pdd\Api;

use Royfee\XShop\Contracts\GoodsApiInterface;
use Royfee\XShop\Core\HttpClient;
use Royfee\XShop\Platforms\Pdd\Mapper\PddGoodsMapper;
use Royfee\XShop\Platforms\Pdd\PddAuth;

// 实现 GoodsApiInterface 接口
class GoodsApi implements GoodsApiInterface
{
    protected $auth;
    protected $http;
    protected $config;
    protected $mapper;

    public function __construct(PddAuth $auth, HttpClient $http, array $config)
    {
        $this->auth   = $auth;
        $this->http   = $http;
        $this->config = $config;
        $this->mapper = new PddOrderMapper();
    }

    public function getDetail($goodsId)
    {
        $token  = $this->auth->getToken();
        $params = [
            'access_token' => $token,
            'goods_id'     => $goodsId
        ];

        $result    = $this->http->post('https://open-api.pinduoduo.com/goods/detail', $params);
        $pddGoods  = $result['goods_info'] ?? [];

        return $this->mapper->transform($pddGoods);
    }
}