<?php
namespace royfee\xshop\Platforms\Taobao;

use royfee\xshop\Platforms\BasePlatform;
use royfee\xshop\Platforms\Taobao\Api\Goods;
use royfee\xshop\Platforms\Taobao\Api\Order;
use royfee\xshop\Platforms\Taobao\Api\AfterSale;

class Taobao extends BasePlatform
{
    public function getPlatformName()
    {
        return 'taobao';
    }
    
    public function registerModules($pimple)
    {
        $pimple['goods'] = function($app) {
            return new Goods($app);
        };
        
        $pimple['order'] = function($app) {
            return new Order($app);
        };
        
        $pimple['after_sale'] = function($app) {
            return new AfterSale($app);
        };
    }
    
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