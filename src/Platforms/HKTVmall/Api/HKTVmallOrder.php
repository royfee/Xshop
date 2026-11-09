<?php

namespace Royfee\XShop\Platforms\HKTVmall\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Royfee\XShop\Contracts\OrderInterface;
use Royfee\XShop\Contracts\HttpClientInterface;
use Royfee\XShop\Platforms\HKTVmall\Auth\HKTVmallAuth;
use Royfee\XShop\Platforms\HKTVmall\HKTVmall;
use Royfee\XShop\Platforms\HKTVmall\Mapper\HKTVmallOrderMapper;
use Royfee\XShop\Data\XOrder;
use Psr\Log\LoggerInterface;

class HKTVmallOrder implements OrderInterface
{
    protected $config;
    protected $http;
    protected $auth;
    protected $logger;
    protected $mapper;
    protected $platform;
    protected $guzzle;

    public function __construct(
        array $config,
        HttpClientInterface $http,
        HKTVmallAuth $auth,
        ?LoggerInterface $logger = null,
        ?HKTVmall $platform = null
    ) {
        $this->config = $config;
        $this->http = $http;
        $this->auth = $auth;
        $this->logger = $logger;
        $this->mapper = new HKTVmallOrderMapper();
        $this->platform = $platform;

        $this->guzzle = new Client([
            'timeout' => $config['timeout'] ?? 30,
            'connect_timeout' => $config['connect_timeout'] ?? 10,
            'http_errors' => false,
        ]);
    }

    protected function getBaseUrl(): string
    {
        return rtrim($this->config['base_url'] ?? 'https://merchant-oapi.shoalter.com', '/');
    }

    protected function getCommonHeaders(): array
    {
        return [
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'x-auth-token'  => $this->auth->getJwtToken(),
            'storeCode'     => $this->config['store_code'] ?? '',
            'platformCode'  => $this->config['platform_code'] ?? 'HKTV',
            'businessType'  => $this->config['business_type'] ?? 'eCommerce',
        ];
    }

    /**
     * 发送 GET 请求并携带 JSON body（与 Shoalter 文档 curl 一致）
     */
    protected function sendGetWithBody(string $url, array $body, array $headers): array
    {
        $this->log('debug', 'HKTVmall API GET (with body) Request', [
            'url' => $url,
            'body' => $body,
        ]);

        try {
            $response = $this->guzzle->request('GET', $url, [
                'headers' => $headers,
                'body' => json_encode($body),
            ]);

            $responseBody = (string) $response->getBody();
            $statusCode = $response->getStatusCode();

            $this->log('debug', 'HKTVmall API Response', [
                'url' => $url,
                'http_status' => $statusCode,
                'body' => $responseBody,
            ]);

            $data = json_decode($responseBody, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $data = ['raw_body' => $responseBody];
            }
            $data['http_status'] = $statusCode;

            return $data;

        } catch (RequestException $e) {
            $this->log('error', 'HKTVmall API request exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException(
                "HKTVmall API request failed: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * 获取订单列表
     *
     * 流程: 先调 /orders 拿到 subOrderNumbers，再调 /details 拿完整数据
     *
     * @param array $params 支持:
     *   - orderDateStart: 开始时间 "Y-m-d H:i:s"
     *   - orderDateEnd:   结束时间 "Y-m-d H:i:s"
     *   - page:           页码 (默认1)
     *   - pageSize:       每页条数 (默认10)
     *   - start_time:     别名 for orderDateStart
     *   - end_time:       别名 for orderDateEnd
     *   - per_page:       别名 for pageSize
     * @return XOrder[]
     */
    public function getList(array $params = []): array
    {
        if ($this->platform) {
            $params = $this->platform->mapParams($params);
        }

        $body = $this->buildListBody($params);
        $url = $this->getBaseUrl() . '/oapi/api/order/orders';
        $headers = $this->getCommonHeaders();

        $response = $this->sendGetWithBody($url, $body, $headers);
        $this->checkResponseError($response, 'getList');


        $subOrderNumbers = $this->extractSubOrderNumbers($response);

        $this->log('debug', 'HKTVmall getList: found subOrderNumbers', [
            'count' => count($subOrderNumbers),
            'pagination' => $response['pagination'] ?? null,
        ]);

        if (empty($subOrderNumbers)) {
            return [];
        }

        return $this->getDetails($subOrderNumbers);
    }

    /**
     * 获取订单详情
     *
     * @param string $orderId 子订单号 (subOrderNumber)
     * @return XOrder|null
     */
    public function getDetail(string $orderId): ?XOrder
    {
        $body = [
            'subOrderNumbers' => [$orderId],
        ];

        $url = $this->getBaseUrl() . '/oapi/api/order/details';
        $headers = $this->getCommonHeaders();

        $response = $this->sendGetWithBody($url, $body, $headers);

        $this->checkResponseError($response, 'getDetail');

        $orders = $this->extractOrdersFromResponse($response);

        if (empty($orders)) {
            return null;
        }

        return $this->mapper->map($orders[0]);
    }

    /**
     * 批量获取订单详情
     *
     * @param array $subOrderNumbers 子订单号数组
     * @return XOrder[]
     */
    public function getDetails(array $subOrderNumbers): array
    {
        $body = [
            'subOrderNumbers' => $subOrderNumbers,
        ];

        $url = $this->getBaseUrl() . '/oapi/api/order/details';
        $headers = $this->getCommonHeaders();

        $response = $this->sendGetWithBody($url, $body, $headers);

        $this->checkResponseError($response, 'getDetails');

        $orders = $this->extractOrdersFromResponse($response);

        return $this->mapper->mapList($orders);
    }

    public function getLogistics(string $orderId): array
    {
        $order = $this->getDetail($orderId);
        if (!$order) {
            return [];
        }

        $result = [];
        if (!empty($order->trackingNo)) {
            $result[] = [
                'tracking_no' => $order->trackingNo,
                'logistics_company' => $order->logisticsCompany,
            ];
        }

        $raw = $order->rawData;
        $shippingInfo = $raw['shippingInfo'] ?? $raw['shipping_info'] ?? $raw['logistics'] ?? [];
        if (is_array($shippingInfo) && !empty($shippingInfo)) {
            $result[] = $shippingInfo;
        }

        return $result;
    }

    public function send(string $orderId, array $params): bool
    {
        throw new \RuntimeException('HKTVmall order send (ship) is not implemented yet');
    }

    protected function buildListBody(array $params): array
    {
        $body = [];

        $body['orderDateStart'] = $params['orderDateStart']
            ?? $params['start_time']
            ?? date('Y-m-d 00:00:00', strtotime('-30 days'));

        $body['orderDateEnd'] = $params['orderDateEnd']
            ?? $params['end_time']
            ?? date('Y-m-d 23:59:59');

        $body['page'] = (int) ($params['page'] ?? 1);
        $body['pageSize'] = (int) ($params['pageSize']
            ?? $params['per_page']
            ?? ($this->config['page_size'] ?? 10));

        if (isset($params['status'])) {
            $body['status'] = $params['status'];
        }

        return $body;
    }

    protected function extractSubOrderNumbers(array $response): array
    {
        $data = $response['data'] ?? [];

        if (isset($data['subOrderNumbers']) && is_array($data['subOrderNumbers'])) {
            return $data['subOrderNumbers'];
        }

        $orders = $this->extractOrdersFromResponse($response);
        $numbers = [];
        foreach ($orders as $order) {
            $no = $order['subOrderNumber'] ?? $order['subOrderNumbers']
                ?? $order['orderNumber'] ?? $order['order_number'] ?? null;
            if ($no !== null) {
                $numbers[] = $no;
            }
        }
        return $numbers;
    }

    protected function extractOrdersFromResponse(array $response): array
    {
        unset($response['http_status']);

        $data = $response['data'] ?? $response['result'] ?? $response;

        if (is_array($data)) {
            // HKTVmall 详情接口: data.successList
            if (isset($data['successList']) && is_array($data['successList'])) {
                return $data['successList'];
            }

            foreach (['orders', 'list', 'items', 'records', 'content', 'rows'] as $key) {
                if (isset($data[$key]) && is_array($data[$key])) {
                    return $data[$key];
                }
            }

            if (isset($data[0]) && is_array($data[0])) {
                return $data;
            }

            if (isset($data['subOrderNumber']) || isset($data['orderId'])
                || isset($data['orderNumber']) || isset($data['order_number'])) {
                return [$data];
            }
        }

        return [];
    }

    protected function checkResponseError(array $response, string $context): void
    {
        $httpStatus = $response['http_status'] ?? 200;
        $debugResponse = $response;
        unset($debugResponse['http_status']);
        $rawBody = json_encode($debugResponse, JSON_UNESCAPED_UNICODE);

        if ($httpStatus >= 400) {
            $code = $response['code'] ?? $response['errorCode'] ?? 'unknown';
            $message = $response['message'] ?? $response['msg']
                ?? $response['errorMessage'] ?? $response['error']
                ?? $response['response'] ?? 'Unknown error';
            if (is_array($message)) {
                $message = json_encode($message, JSON_UNESCAPED_UNICODE);
            }

            $this->log('error', "HKTVmall API HTTP error during {$context}", [
                'http_status' => $httpStatus,
                'code' => $code,
                'message' => $message,
                'raw_response' => $rawBody,
            ]);

            throw new \RuntimeException(
                "HKTVmall API HTTP error [{$httpStatus}]: {$message}\n"
                . "Response body: {$rawBody}",
                $httpStatus
            );
        }

        $apiCode = $response['code'] ?? $response['errorCode'] ?? null;
        if ($apiCode !== null) {
            $normalizedCode = is_string($apiCode) ? strtolower($apiCode) : $apiCode;
            $successCodes = [0, '0', 200, '200', 'success', 'ok'];
            if (!in_array($normalizedCode, $successCodes, true)) {
                $message = $response['message'] ?? $response['msg']
                    ?? $response['errorMessage'] ?? $response['error']
                    ?? $response['response'] ?? 'Unknown error';
                if (is_array($message)) {
                    $message = json_encode($message, JSON_UNESCAPED_UNICODE);
                }

                $this->log('error', "HKTVmall API business error during {$context}", [
                    'code' => $apiCode,
                    'message' => $message,
                    'raw_response' => $rawBody,
                ]);

                throw new \RuntimeException(
                    "HKTVmall API business error [{$apiCode}]: {$message}\n"
                    . "Response body: {$rawBody}",
                    is_numeric($apiCode) ? (int) $apiCode : 0
                );
            }
        }
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->$level($message, $context);
        }
    }
}
