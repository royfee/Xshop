<?php
namespace royfee\xshop\Platforms;

use Hanson\Foundation\AbstractAPI;

/**
 * API基类 - 所有业务API继承此类
 */
abstract class BaseApi extends AbstractAPI
{
    protected $app;
    
    public function __construct($app) {
        $this->app = $app;
    }
    
    /**
     * 调用API
     * @param string $method API方法名
     * @param array $params 请求参数
     * @return mixed
     */
    protected function call($method, $params = [])
    {
        return $this->app->request($method, $params);
    }
}