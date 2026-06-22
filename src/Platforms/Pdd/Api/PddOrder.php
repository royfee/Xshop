<?php

namespace Royfee\XShop\Platforms\Pdd\Api;

use Royfee\XShop\Contracts\OrderInterface;
use Royfee\XShop\Contracts\HttpClientInterface;
use Royfee\XShop\Platforms\Pdd\Auth\PddAuth;
use Royfee\XShop\Platforms\Pdd\Mapper\PddOrderMapper;
use Royfee\XShop\Data\XOrder;
use Psr\Log\LoggerInterface;

/**
 * 拼多多订单接口
 * 
 * 请求格式: POST JSON
 * Content-Type: application/json
 */
class PddOrder implements OrderInterface
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
        $this->mapper = new PddOrderMapper();
    }

    /**
     * 调用拼多多API
     * 
     * POST https://gw-api.pinduoduo.com/api/router
     * Content-Type: application/json
     */
    protected function request(string $type, array $params = []): array
    {
        $token = $this->auth->getCachedToken();
        if (!$token) {
            throw new \RuntimeException('No valid token found, please authorize first');
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

        if ($this->logger) {
            $this->logger->debug('PDD API Request', ['type' => $type, 'params' => $params]);
        }

        // 拼多多请求是 JSON 格式
        $response = $this->http->post(
            $this->config['api_url'],
            $params,
            ['Content-Type' => 'application/json']
        );

        if ($this->logger) {
            $this->logger->debug('PDD API Response', ['type' => $type, 'response' => $response]);
        }

        // 检查响应错误
        $this->checkResponseError($response, $type);

        return $response;
    }

    /**
     * 检查响应中的错误
     * 
     * 处理 Unicode 编码的错误信息
     */
    protected function checkResponseError(array $response, string $type): void
    {
        if (!isset($response['error_response'])) {
            return;
        }

        $error = $response['error_response'];
        $errorCode = $error['error_code'] ?? 0;
        $errorMsg = $error['error_msg'] ?? 'Unknown error';

        // 解码 Unicode 转义字符
        $errorMsg = $this->decodeUnicode($errorMsg);

        // 如果是 token 过期，抛出特定异常以便上层处理
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

    /**
     * 解码 Unicode 转义字符
     */
    protected function decodeUnicode(string $str): string
    {
        return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($matches) {
            return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UCS-2BE');
        }, $str);
    }

    /**
     * 生成签名
     */
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
        $response = $this->request('pdd.order.number.list.get', $params);
        $orders = $response['order_list_get_response']['order_list'] ?? [];
        return $this->mapper->mapList($orders);
    }

    public function getDetail(string $orderId): ?XOrder
    {
        $response = $this->request('pdd.order.information.get', [
            'order_sn' => $orderId,
        ]);
        $order = $response['order_info_get_response'] ?? null;
        return $order ? $this->mapper->map($order) : null;
    }

    public function getLogistics(string $orderId): array
    {
        $response = $this->request('pdd.order.traces.get', [
            'order_sn' => $orderId,
        ]);
        return $response['order_traces_get_response']['logistics_trace_list'] ?? [];
    }

    public function send(string $orderId, array $params): bool
    {
        $response = $this->request('pdd.order.shipping', array_merge([
            'order_sn' => $orderId,
        ], $params));
        return ($response['order_shipping_response']['is_success'] ?? false) === true;
    }
}
