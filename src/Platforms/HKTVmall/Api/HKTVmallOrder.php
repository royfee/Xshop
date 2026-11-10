<?php

namespace Royfee\XShop\Platforms\HKTVmall\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Royfee\XShop\Contracts\HttpClientInterface;
use Royfee\XShop\Contracts\OrderInterface;
use Royfee\XShop\Contracts\PlatformInterface;
use Royfee\XShop\Data\XOrder;
use Royfee\XShop\Mapper\AbstractMapper;
use Royfee\XShop\Platforms\AbstractApiResource;
use Royfee\XShop\Platforms\HKTVmall\Auth\HKTVmallAuth;
use Royfee\XShop\Platforms\HKTVmall\Mapper\HKTVmallOrderMapper;

/**
 * HKTVmall 订单接口 (Shoalter 开放网关, RS256 JWT 鉴权)
 */
class HKTVmallOrder extends AbstractApiResource implements OrderInterface
{
    /** @var Client 需要发送带 body 的 GET 请求，统一客户端不支持，暂用原生 Guzzle */
    protected $guzzle;

    public function __construct(
        array $config,
        HttpClientInterface $http,
        HKTVmallAuth $auth,
        ?LoggerInterface $logger = null,
        ?PlatformInterface $platform = null
    ) {
        parent::__construct($config, $http, $auth, $logger, $platform);

        $this->guzzle = new Client([
            'timeout' => $config['timeout'] ?? 30,
            'connect_timeout' => $config['connect_timeout'] ?? 10,
            'http_errors' => false,
        ]);
    }

    protected function createMapper(): AbstractMapper
    {
        return new HKTVmallOrderMapper();
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
            // HKTVmall 网关对 User-Agent 中含 "curl/" 的请求直接返回 404，
            // 而 Guzzle 6 的默认 UA 带有该片段，故显式指定
            'User-Agent'    => 'XShop-HKTVmall/1.0',
            'x-auth-token'  => $this->auth->getJwtToken(),
            'storeCode'     => $this->config['store_code'] ?? '',
            'platformCode'  => $this->config['platform_code'] ?? 'HKTV',
            'businessType'  => $this->config['business_type'] ?? 'eCommerce',
        ];
    }

    /**
     * 发送携带 JSON body 的请求 (GET 时与 Shoalter 文档 curl 一致；POST 用于面单等接口)
     *
     * @return array 解析后的响应；非 JSON 响应 (如面单 HTML) 放在 raw_body 键下
     */
    protected function sendWithBody(string $method, string $url, array $body, array $headers): array
    {
        $this->log('debug', 'HKTVmall API ' . $method . ' (with body) Request', [
            'url' => $url,
            'body' => $body,
        ]);

        try {
            $response = $this->guzzle->request($method, $url, [
                'headers' => $headers,
                'body' => json_encode($body),
            ]);

            $responseBody = (string) $response->getBody();
            $statusCode = $response->getStatusCode();

            $this->log('debug', 'HKTVmall API Response', [
                'url' => $url,
                'http_status' => $statusCode,
                // 面单 HTML 可达数十 KB，截断避免日志膨胀
                'body' => strlen($responseBody) > 2000
                    ? substr($responseBody, 0, 2000) . '... [共 ' . strlen($responseBody) . ' 字节]'
                    : $responseBody,
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
        $body = $this->buildListBody($this->prepareParams($params));
        $url = $this->getBaseUrl() . '/oapi/api/order/orders';
        $headers = $this->getCommonHeaders();

        $response = $this->sendWithBody('GET', $url, $body, $headers);
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

        $response = $this->sendWithBody('GET', $url, $body, $headers);

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

        $response = $this->sendWithBody('GET', $url, $body, $headers);

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

    /**
     * 获取打印面单 (POST /oapi/api/order/printWaybills/html)
     *
     * 单号格式 "订单号|运单号"，运单号可省略；支持字符串或数组批量，
     * 可与 getList 组合使用:
     *   $orders = $hktv->order()->getList($params);
     *   $lines  = array_map(fn($o) => $o->platformOrderNo . '|' . $o->trackingNo, $orders);
     *   $label  = $hktv->order()->getLabel($lines);
     *   echo $label['html'];
     *
     * @param string|array $param 单号或单号数组
     * @param array $options 打印参数，如 ['paperSize' => 'A4']，其余键透传给平台
     * @return array ['html' => string 面单 HTML, 'raw' => array 平台原始响应]
     */
    public function getLabel($param, array $options = []): array
    {
        [$numbers, $waybills] = $this->parseLabelLines($param);

        $body = array_merge($options, [
            'waybill'         => implode(',', $waybills),
            'suborderNumbers' => implode(',', $numbers),
            'paperSize'       => $options['paperSize'] ?? $options['paper_size'] ?? 'A5',
        ]);
        unset($body['paper_size']);

        $url = $this->getBaseUrl() . '/oapi/api/order/printWaybills/html';
        $headers = $this->getCommonHeaders();
        $headers['Accept'] = 'text/html, application/json';

        $response = $this->sendWithBody('POST', $url, $body, $headers);
        $httpStatus = (int) ($response['http_status'] ?? 200);
        unset($response['http_status']);

        // 网关直出 HTML 时没有其它附加信息，raw 为空
        if (isset($response['raw_body'])) {
            $html = (string) $response['raw_body'];
            $this->checkRawResponseError($httpStatus, $html, 'getLabel');
            return ['html' => $html, 'raw' => []];
        }

        // 网关返回 JSON 信封: {"code":"success","message":null,"data":"<!DOCTYPE html>..."}
        $response['http_status'] = $httpStatus;
        $this->checkResponseError($response, 'getLabel');

        $data = $response['data'] ?? null;
        if (!is_string($data) || $data === '') {
            throw new \RuntimeException(
                'HKTVmall getLabel: 接口未返回面单 HTML，响应: '
                . substr(json_encode($response, JSON_UNESCAPED_UNICODE), 0, 500)
            );
        }

        return ['html' => $data, 'raw' => $response];
    }

    /**
     * 解析 "订单号|运单号" 形式的单号，返回 [订单号数组, 运单号数组]
     *
     * @param string|array $param
     * @return array
     */
    protected function parseLabelLines($param): array
    {
        $lines = is_array($param) ? $param : (is_string($param) ? [$param] : []);

        $numbers = [];
        $waybills = [];
        foreach ($lines as $line) {
            $parts = explode('|', trim((string) $line));
            if ($parts[0] === '') {
                continue;
            }
            $numbers[] = $parts[0];
            $waybills[] = trim($parts[1] ?? '');
        }

        if (empty($numbers)) {
            throw new \InvalidArgumentException('HKTVmall getLabel: 订单号不能为空');
        }

        return [$numbers, $waybills];
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

    /**
     * 面单接口返回非 JSON 内容时的错误判断 (正常为 HTML，异常时为网关错误页)
     */
    protected function checkRawResponseError(int $httpStatus, string $body, string $context): void
    {
        if ($httpStatus < 400) {
            return;
        }

        $body = trim($body);
        $this->checkResponseError([
            'http_status' => $httpStatus,
            'message'     => $body === '' ? 'Empty response body' : substr($body, 0, 500),
        ], $context);
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
}
