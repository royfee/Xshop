<?php
namespace royfee\xshop\Platforms\Pinduoduo;

use royfee\xshop\Platforms\BasePlatform;
use royfee\xshop\Platforms\Pinduoduo\Api\AccessToken;
use royfee\xshop\Platforms\Pinduoduo\Api\Api;
use royfee\xshop\Platforms\Pinduoduo\Api\Order;

class Pinduoduo extends BasePlatform
{
    public function getPlatformName()
    {
        return 'pdd';
    }
    
    public function registerModules($pimple)
    {
        $pimple['access_token'] = function($app) use ($pimple) {
            return new AccessToken(
                $pimple->getConfig('client_id'),
                $pimple->getConfig('client_secret'),
                $app
            );
        };

        $pimple['api'] = function ($pimple) {
            return new Api($pimple);
        };

        $pimple['order'] = function ($pimple) {
            return new Order($pimple);
        };
    }
    
    /*
    protected function request($method, array $params = [])
    {
        // 淘宝API请求实现
        $systemParams = [
            'method' => $method,
            'app_key' => $this->config['app_key'],
            'session' => $this->accessToken,
            'timestamp' => date('Y-m-d H:i:s'),
            'v' => $this->config['version'] ?? '2.0',
            'format' => $this->config['format'] ?? 'json',
            'sign_method' => $this->config['sign_method'] ?? 'md5',
        ];
        
        $allParams = array_merge($systemParams, $params);
        $allParams['sign'] = $this->generateSign($allParams);
        
        // 使用Guzzle发送请求
        $client = new \GuzzleHttp\Client(['timeout' => $this->config['timeout'] ?? 30]);
        $response = $client->post($this->config['gateway'], [
            'form_params' => $allParams
        ]);
        
        $result = json_decode($response->getBody(), true);
        
        if (isset($result['error_response'])) {
            throw new \Exception($result['error_response']['msg'] ?? '淘宝API错误');
        }
        
        return $result;
    }
    */

    protected function generateSign(array $params)
    {
        ksort($params);
        $str = $this->config['app_secret'];
        foreach ($params as $k => $v) {
            if ($k !== 'sign' && $v !== '') {
                $str .= $k . $v;
            }
        }
        $str .= $this->config['app_secret'];
        return strtoupper(md5($str));
    }
}