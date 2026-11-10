<?php

namespace Royfee\XShop\Platforms\WooCommerce\Api;

use Psr\Log\LoggerInterface;
use Royfee\XShop\Contracts\HttpClientInterface;
use Royfee\XShop\Contracts\OrderInterface;
use Royfee\XShop\Contracts\PlatformInterface;
use Royfee\XShop\Data\XOrder;
use Royfee\XShop\Mapper\AbstractMapper;
use Royfee\XShop\Platforms\AbstractApiResource;
use Royfee\XShop\Platforms\WooCommerce\Auth\WooCommerceAuth;
use Royfee\XShop\Platforms\WooCommerce\Mapper\WooCommerceOrderMapper;

/**
 * WooCommerce 订单接口 (REST API v3, HTTP Basic Auth)
 */
class WooCommerceOrder extends AbstractApiResource implements OrderInterface
{
    public function __construct(
        array $config,
        HttpClientInterface $http,
        WooCommerceAuth $auth,
        ?LoggerInterface $logger = null,
        ?PlatformInterface $platform = null
    ) {
        parent::__construct($config, $http, $auth, $logger, $platform);
    }

    protected function createMapper(): AbstractMapper
    {
        return new WooCommerceOrderMapper();
    }

    protected function getApiUrl(string $endpoint = 'orders'): string
    {
        $siteUrl = rtrim($this->config['site_url'] ?? '', '/');
        $apiVersion = $this->config['api_version'] ?? 'v3';
        return "{$siteUrl}/wp-json/wc/{$apiVersion}/{$endpoint}";
    }

    protected function getAuthHeaders(): array
    {
        return [
            'Authorization' => $this->auth->getBasicAuthHeader(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public function getList(array $params = []): array
    {
        $queryParams = $this->buildListParams($this->prepareParams($params));
        $url = $this->getApiUrl('orders');
        $headers = $this->getAuthHeaders();

        $this->log('debug', 'WooCommerce API Request', [
            'method' => 'GET',
            'url' => $url,
            'params' => $queryParams,
        ]);

        $response = $this->http->get($url, $queryParams, $headers);

        $this->log('debug', 'WooCommerce API Response', [
            'url' => $url,
            'status' => $response['http_status'] ?? null,
            'count' => is_array($response) ? count($response) : 0,
        ]);

        $this->checkResponseError($response, 'GET orders');

        $orders = array_filter($response, function ($item) {
            return is_array($item) && isset($item['id']);
        });

        return $this->mapper->mapList(array_values($orders));
    }

    public function getDetail(string $orderId): ?XOrder
    {
        $url = $this->getApiUrl("orders/{$orderId}");
        $headers = $this->getAuthHeaders();

        $this->log('debug', 'WooCommerce API Request', [
            'method' => 'GET',
            'url' => $url,
        ]);

        $response = $this->http->get($url, [], $headers);

        $this->log('debug', 'WooCommerce API Response', [
            'url' => $url,
            'status' => $response['http_status'] ?? null,
        ]);

        $this->checkResponseError($response, "GET order {$orderId}");

        if (!isset($response['id'])) {
            return null;
        }

        return $this->mapper->map($response);
    }

    public function getLogistics(string $orderId): array
    {
        $order = $this->getDetail($orderId);
        if (!$order) {
            return [];
        }

        $raw = $order->rawData;
        $shippingLines = $raw['shipping_lines'] ?? [];
        $result = [];

        foreach ($shippingLines as $line) {
            $result[] = [
                'method_id' => $line['method_id'] ?? '',
                'method_title' => $line['method_title'] ?? '',
                'total' => $line['total'] ?? '0.00',
            ];
        }

        if (!empty($order->trackingNo)) {
            $result[] = [
                'tracking_no' => $order->trackingNo,
                'logistics_company' => $order->logisticsCompany,
            ];
        }

        return $result;
    }

    public function send(string $orderId, array $params): bool
    {
        $url = $this->getApiUrl("orders/{$orderId}");
        $headers = $this->getAuthHeaders();

        $body = [];
        if (isset($params['status'])) {
            $body['status'] = $params['status'];
        }

        if (!empty($params['tracking_number']) || !empty($params['tracking_company'])) {
            $body['meta_data'] = [
                [
                    'key' => '_tracking_number',
                    'value' => $params['tracking_number'] ?? '',
                ],
                [
                    'key' => '_tracking_company',
                    'value' => $params['tracking_company'] ?? '',
                ],
            ];
        }

        if (empty($body)) {
            return false;
        }

        $this->log('debug', 'WooCommerce API Request', [
            'method' => 'PUT',
            'url' => $url,
            'body' => $body,
        ]);

        $response = $this->http->post($url, $body, array_merge($headers, [
            'X-HTTP-Method-Override' => 'PUT',
        ]));

        $this->log('debug', 'WooCommerce API Response', [
            'url' => $url,
            'status' => $response['http_status'] ?? null,
        ]);

        $this->checkResponseError($response, "PUT order {$orderId}");

        return isset($response['id']);
    }

    protected function buildListParams(array $params): array
    {
        $allowed = [
            'page', 'per_page', 'search', 'after', 'before',
            'exclude', 'include', 'offset', 'order', 'orderby',
            'status', 'customer', 'product', 'dp',
        ];

        $query = [];
        foreach ($allowed as $key) {
            if (isset($params[$key])) {
                $query[$key] = $params[$key];
            }
        }

        if (!isset($query['per_page'])) {
            $query['per_page'] = $this->config['per_page'] ?? 10;
        }
        if (!isset($query['page'])) {
            $query['page'] = 1;
        }

        return $query;
    }

    protected function checkResponseError(array $response, string $context): void
    {
        $httpStatus = $response['http_status'] ?? 200;

        if ($httpStatus >= 400) {
            $code = $response['code'] ?? 'unknown';
            $message = $response['message'] ?? 'Unknown WooCommerce API error';

            $this->log('error', "WooCommerce API error during {$context}", [
                'http_status' => $httpStatus,
                'code' => $code,
                'message' => $message,
            ]);

            throw new \RuntimeException(
                "WooCommerce API error [{$httpStatus}]: {$message}",
                $httpStatus
            );
        }
    }
}
