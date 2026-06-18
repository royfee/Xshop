<?php
namespace Royfee\XShop\Platforms\Pdd\Api;

use Royfee\XShop\Core\HttpClient;
use Royfee\XShop\Platforms\Pdd\PddAuth;
use Royfee\XShop\Contracts\DecryptApiInterface;

class DecryptApi implements DecryptApiInterface
{
    protected $auth;
    protected $http;
    protected $config;

    // 统一网关（和订单/商品接口一致）
    protected const API_GATEWAY = 'https://gw-api.pinduoduo.com/api/router';
    // 接口类型
    protected const API_TYPE = 'pdd.open.decrypt.batch';
    // 云外限流：10秒1次
    protected int $lastRequestTime = 0;
    protected const RATE_LIMIT = 10;

    public function __construct(PddAuth $auth, HttpClient $http, array $config)
    {
        $this->auth   = $auth;
        $this->http   = $http;
        $this->config = $config;
    }

    /**
     * 批量解密原始数据（标准入口）
     * @param array $dataList 待解密二维数组
     * @return array
     * @throws \Exception
     */
    public function batchDecrypt(array $dataList): array
    {
        if (empty($dataList)) {
            return [];
        }
$dataList = [
[
    'data_tag'  =>  '260612-050163945762047',
    'encrypted_data'  =>  '~AgAAAAQ8QMcFToQakAAp/+XZ8M5rYUVluWGC7ppm308=~0~',
],
[
    'data_tag'  =>  '260612-050163945762047',
    'encrypted_data'  =>  '~AgAAAAQ8QMcHToQakAFQA7pIltJx/687deA7HkifoZ3VB+0SxJLZlPlFzFXu7//3L+pOLMWJdf+CF4z/LsMNU9x4BL0U5nZ3PVM59QYTi5DnrfV0w1018vGWpw6sXwbPm6nHRPeReOUc5t0jH4SvOUq0RJfQspMlADEPYGhonvM=~0~',
]
];
        // 限流校验
        $now = time();
        if ($now - $this->lastRequestTime < self::RATE_LIMIT) {
            throw new \Exception("解密接口限流：" . self::RATE_LIMIT . " 秒内仅允许调用一次");
        }
        $this->lastRequestTime = $now;

        $token = $this->auth->getToken();
        $params = [
            'type'         => self::API_TYPE,
            'client_id'    => $this->config['client_id'],
            'access_token' => $token,
            'timestamp'    => (string)time(),
            'data_type'    => 'json',
            'version'      => '1',
            'sign_method'  => 'md5',
            'data_list'    => json_encode($dataList, JSON_UNESCAPED_UNICODE)
        ];

        // 生成签名
        $params['sign'] = $this->makeSign($params);
        $response = $this->http->post(self::API_GATEWAY, $params);

var_dump($response);exit('End');

        // 平台错误捕获
        if (!empty($response['error_code'])) {
            throw new \Exception(
                "解密接口异常 [{$response['error_code']}]：{$response['error_msg']}"
            );
        }

        return $response['open_decrypt_batch_response']['data_list'] ?? [];
    }

    /**
     * 单条订单解密（快捷方法，业务常用）
     * @param array $orderData 拼多多原始订单数组
     * @return array
     */
    public function decryptOrder(array $orderData): array
    {
        $encryptData = [
            'receiver_name'       => $orderData['receiver_name'] ?? '',
            'receiver_phone'      => $orderData['receiver_phone'] ?? '',
            'receiver_address'    => $orderData['receiver_address'] ?? '',
            'address'             => $orderData['address'] ?? '',
            'id_card_name'        => $orderData['id_card_name'] ?? '',
            'id_card_num'         => $orderData['id_card_num'] ?? '',
            'pay_no'              => $orderData['pay_no'] ?? '',
            'inner_transaction_id' => $orderData['inner_transaction_id'] ?? ''
        ];

        // 过滤空密文
        $encryptData = array_filter($encryptData);
        if (empty($encryptData)) {
            return $orderData;
        }

        $decryptResult = $this->batchDecrypt([$encryptData]);
        $decryptFields = $decryptResult[0] ?? [];

        // 明文覆盖原字段
        foreach ($decryptFields as $key => $val) {
            if (isset($orderData[$key])) {
                $orderData[$key] = $val;
            }
        }

        return $orderData;
    }

    /**
     * 拼多多标准 MD5 签名（和 OrderApi/GoodsApi 完全统一）
     * @param array $params
     * @return string
     */
    protected function makeSign(array $params): string
    {
        $secret = $this->config['client_secret'] ?? '';
        if (empty($secret)) {
            throw new \Exception('缺少配置：client_secret');
        }

        unset($params['sign']);
        ksort($params);

        $signStr = '';
        foreach ($params as $k => $v) {
            $signStr .= $k . $v;
        }

        return strtoupper(md5($secret . $signStr . $secret));
    }
}