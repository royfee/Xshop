<?php
namespace Royfee\XShop\Platforms\Pdd\Api;

use Royfee\XShop\Contracts\OrderApiInterface;
use Royfee\XShop\Core\HttpClient;
use Royfee\XShop\Platforms\Pdd\Mapper\PddOrderMapper;
use Royfee\XShop\Platforms\Pdd\PddAuth;
use Royfee\XShop\Data\XOrder;

class OrderApi implements OrderApiInterface
{
    protected $auth;
    protected $http;
    protected $config;
    protected $mapper;

    // 拼多多官方统一网关地址
    protected const API_GATEWAY = 'https://gw-api.pinduoduo.com/api/router';

    public function __construct(PddAuth $auth, HttpClient $http, array $config)
    {
        $this->auth   = $auth;
        $this->http   = $http;
        $this->config = $config;
        $this->mapper = new PddOrderMapper();
    }

    /**
     * 按接口契约实现：获取订单列表
     * @param string $startTime 秒级时间戳字符串
     * @param string $endTime 秒级时间戳字符串
     * @param int $page
     * @param int $pageSize
     * @return XOrder[]
     */
    public function getList(array $param): array
    {
        $token = $this->auth->getToken();

        // 组装公共参数 + 业务参数（拼多多官方要求）
        $params = array_merge([
            'type'         => 'pdd.order.list.get',
            'client_id'    => $this->config['client_id'],
            'access_token' => $token,
            'timestamp'    => (string)time(),
            'data_type'    => 'json',
            'order_status' => 1,
            'refund_status' => 5,
            'version'      => '1',
            'sign_method'  => 'md5',
            'page'         => 1,
            'page_size'    => 10,
            //'start_confirm_at'   => strtotime($startTime),
            //'end_confirm_at'     => strtotime($endTime),
        ],$param);

        // 生成官方标准签名
        $params['sign'] = $this->makeSign($params);

        $res = $this->http->post(self::API_GATEWAY, $params);

        // 捕获拼多多错误码
        if (!empty($res['error_code'])) {
            throw new \Exception("拼多多接口错误 [{$res['error_code']}]：{$res['error_msg']}");
        }

        $rawList = $res['order_list_get_response']['order_list'] ?? [];
        $result  = [];
        foreach ($rawList as $item) {
            $result[] = $this->mapper->transform($item);
        }
        return $result;
    }

    /**
     * 按接口契约实现：获取订单详情
     * @param string $orderSn
     * @return XOrder|null
     */
    public function getDetail(string $orderSn)
    {
        $token = $this->auth->getToken();

        $params = [
            'type'         => 'pdd.order.information.get',
            'client_id'    => $this->config['client_id'],
            'access_token' => $token,
            'timestamp'    => (string)time(),
            'data_type'    => 'json',
            'version'      => '1',
            'sign_method'  => 'md5',
            'order_sn'     => $orderSn
        ];

        $params['sign'] = $this->makeSign($params);
        $res = $this->http->post(self::API_GATEWAY, $params);

        if (!empty($res['error_code'])) {
            throw new \Exception("拼多多接口错误 [{$res['error_code']}]：{$res['error_msg']}");
        }

        $info = $res['order_information_get_response']['order_info'] ?? [];
        if (empty($info)) {
            return null;
        }

        return $this->mapper->transform($info);
    }

    /**
     * 拼多多官方 MD5 签名算法
     */
    protected function makeSign(array $params): string
    {
        $secret = $this->config['client_secret'] ?? '';
        if (empty($secret)) {
            throw new \Exception('缺少配置 client_secret');
        }

        // 移除 sign 自身
        unset($params['sign']);
        // 按键名ASCII升序排序
        ksort($params);

        // 键值拼接
        $str = '';
        foreach ($params as $k => $v) {
            $str .= $k . $v;
        }

        // 前后拼接 secret 并 MD5 转大写
        $signRaw = $secret . $str . $secret;
        return strtoupper(md5($signRaw));
    }
}