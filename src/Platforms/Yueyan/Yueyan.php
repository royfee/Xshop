<?php
namespace royfee\xshop\Platforms\Yueyan;

use royfee\xshop\Platforms\BasePlatform;
use royfee\xshop\Platforms\Yueyan\Api\Order;

class Yueyan extends BasePlatform
{
    const API = 'https://open.shop2cn.com/apigateway/v1?app_id=';

    public function getPlatformName()
    {
        return 'yueyan';
    }
    
    public function registerModules($pimple)
    {
        $pimple['order'] = function($app) {
            return new Order($app);
        };
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

        $response = $this->http->json($this->getUrl($method),$allParams);

        $result = json_decode($response->getBody(),true);
                
        if ($result['code'] !== '0000') {
            throw new \Exception($result['message'] ?? '越洋[Yueyan]接口错误');
        }
        
        return $result;
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

    public function getVersion()
    {
        if (!$this->getConfig()['version'] ?? null) {
            throw new \Exception('version cannot be null');
        }
        return $this->getConfig()['version'];
    }

    private function getUrl($method)
    {
        return self::API.$this->config['app_id'].'&method='.$method;
    }
}