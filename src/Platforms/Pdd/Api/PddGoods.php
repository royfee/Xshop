<?php

namespace Royfee\XShop\Platforms\Pdd\Api;

use Royfee\XShop\Contracts\GoodsInterface;
use Royfee\XShop\Contracts\HttpClientInterface;
use Royfee\XShop\Platforms\Pdd\Auth\PddAuth;
use Royfee\XShop\Platforms\Pdd\Mapper\PddGoodsMapper;
use Royfee\XShop\Data\XGoods;
use Psr\Log\LoggerInterface;

/**
 * 拼多多商品接口
 * 
 * 请求格式: POST JSON
 * Content-Type: application/json
 */
class PddGoods implements GoodsInterface
{
    protected $config;
    protected $http;
    protected $auth;
    protected $logger;
    protected $mapper;

    public function __construct(array $config, HttpClientInterface $http, PddAuth $auth, ?LoggerInterface $logger = null)
    {
        $this->config = $config;
        $this->http = $http;
        $this->auth = $auth;
        $this->logger = $logger;
        $this->mapper = new PddGoodsMapper();
    }

    protected function request(string $type, array $params = []): array
    {
        $token = $this->auth->getCachedToken();
        if (!$token) {
            throw new \RuntimeException('No valid token found');
        }

        $baseParams = [
            'type' => $type,
            'client_id' => $this->config['client_id'],
            'access_token' => $token['access_token'] ?? '',
            'timestamp' => time() * 1000,
            'data_type' => 'JSON',
            'version' => 'V1',
        ];

        $params = array_merge($baseParams, $params);
        $params['sign'] = $this->generateSign($params);

        $response = $this->http->post(
            $this->config['api_url'],
            $params,
            ['Content-Type' => 'application/json']
        );

        $this->checkResponseError($response, $type);

        return $response;
    }

    protected function checkResponseError(array $response, string $type): void
    {
        if (!isset($response['error_response'])) {
            return;
        }

        $error = $response['error_response'];
        $errorCode = $error['error_code'] ?? 0;
        $errorMsg = $error['error_msg'] ?? 'Unknown error';
        $errorMsg = $this->decodeUnicode($errorMsg);

        if (in_array($errorCode, [70012, 70014, 70016])) {
            throw new \RuntimeException(
                "PDD token expired [{$errorCode}]: {$errorMsg}",
                $errorCode
            );
        }

        throw new \RuntimeException(
            "PDD API error [{$errorCode}]: {$errorMsg}",
            $errorCode
        );
    }

    protected function decodeUnicode(string $str): string
    {
        return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($matches) {
            return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UCS-2BE');
        }, $str);
    }

    protected function generateSign(array $params): string
    {
        ksort($params);
        $string = $this->config['client_secret'];
        foreach ($params as $key => $value) {
            if ($key !== 'sign' && $value !== null && $value !== '') {
                $string .= $key . $value;
            }
        }
        $string .= $this->config['client_secret'];
        return strtoupper(md5($string));
    }

    public function getList(array $params = []): array
    {
        $response = $this->request('pdd.goods.list.get', $params);
        $goodsList = $response['goods_list_get_response']['goods_list'] ?? [];
        return $this->mapper->mapList($goodsList);
    }

    public function getDetail(string $goodsId): ?XGoods
    {
        $response = $this->request('pdd.goods.information.get', [
            'goods_id' => $goodsId,
        ]);
        $goods = $response['goods_info_get_response'] ?? null;
        return $goods ? $this->mapper->map($goods) : null;
    }

    public function onSale(string $goodsId): bool
    {
        $response = $this->request('pdd.goods.sale.status.set', [
            'goods_id' => $goodsId,
            'is_onsale' => 1,
        ]);
        return ($response['goods_sale_status_set_response']['is_success'] ?? false) === true;
    }

    public function offSale(string $goodsId): bool
    {
        $response = $this->request('pdd.goods.sale.status.set', [
            'goods_id' => $goodsId,
            'is_onsale' => 0,
        ]);
        return ($response['goods_sale_status_set_response']['is_success'] ?? false) === true;
    }

    public function updateStock(string $goodsId, int $quantity): bool
    {
        $response = $this->request('pdd.goods.quantity.update', [
            'goods_id' => $goodsId,
            'quantity' => $quantity,
        ]);
        return ($response['goods_quantity_update_response']['is_success'] ?? false) === true;
    }
}
