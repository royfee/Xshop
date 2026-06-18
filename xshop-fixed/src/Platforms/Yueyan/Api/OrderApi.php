<?php
namespace royfee\xshop\Platforms\Yueyan\Api;

use Royfee\XShop\Contracts\OrderApiInterface;
use Royfee\XShop\Platforms\Yueyan\YueyanAuth;
use Royfee\XShop\Core\HttpClient;
use Royfee\XShop\Platforms\Yueyan\Mapper\YueYanOrderMapper;

/**
 * 悦言订单业务
 */
class OrderApi implements OrderApiInterface
{
    const API = 'https://open.shop2cn.com/apigateway/v1?app_id=';

    public function __construct(YueyanAuth $auth, HttpClient $http, array $config)
    {
        $this->auth   = $auth;
        $this->http   = $http;
        $this->config = $config;
        $this->mapper = new YueYanOrderMapper();
    }

    /**
     * 获取订单列表
     */
    public function getList(array $params = []): array
    {
        $response = $this->request('sq.order.list.get', $params);
        $result  = [];
        foreach ($response['content']['orders_info'] as $order) {
            $result[] = $this->mapper->transform($order)->toArray();
        }
        return $result;
    }

    /**
     * 获取订单详情
     */
    public function getDetail(string $orderId): array
    {
        
    }

    /**
     * 发货
     */
    public function deliver(
        string $orderId,
        string $logisticsCompanyCode,
        string $logisticsCompanyName,
        string $trackingNumber,
        int $partialDeliveryStatus = 2
    ): array {
        return $this->call('sq.order.deliver', [
            'deliver_orders' => [
                [
                    'order_id'                => $orderId,
                    'logistics_company_id'    => $logisticsCompanyCode,
                    'logistics_company_name'  => $logisticsCompanyName,
                    'tracking_number'         => $trackingNumber,
                    'partial_delivery_status' => $partialDeliveryStatus,
                ],
            ],
        ]);
    }

    public function request($method, array $params = [])
    {
        $allParams = [
            'method'        => $method,
            'app_id'        => $this->config['app_id'],
            'auth_code'     => $this->config['auth_code'],
            'nonce_str'     => '123456789',
            'sign_method'   => 'MD5',
            'biz_content'   => json_encode($params),
            'timestamp'     => date('Y-m-d H:i:s'),
        ];

        $allParams['sign'] = $this->generateSign($allParams);
        $response = $this->http->post($this->getUrl($method),$allParams);

        if ($response['code'] !== '0000') {
            throw new \Exception("Yueyan接口错误 [{$res['error_code']}]：{$res['error_msg']}");
        }
        
        return $response;
    }

    protected function generateSign(array $params)
    {
        ksort($params);
        $cipherArr = [];
        foreach($params as $k => $v){
            $cipherArr[] = $k.'='.$v;
        }
        $cipherText = implode('&',$cipherArr).'&app_secret='.$this->config['app_secret'];
        return strtoupper(md5($cipherText));
    }

    private function getMapper()
    {
        return $this->app->getMapper();
    }

    private function getUrl($method)
    {
        return self::API.$this->config['app_id'].'&method='.$method;
    }
}
