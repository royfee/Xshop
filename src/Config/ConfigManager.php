<?php
namespace royfee\xshop\Config;

class ConfigManager
{
    protected $config = [];
    
    public function __construct($config)
    {
        if(is_array($config)){
            $this->config = $config;
        } else if (is_string($config) && file_exists($config)) {
            $this->config = include $config;
        } else {
            throw new \InvalidArgumentException('配置必须是数组或有效的配置文件路径');
        }
    }
    
    /**
     * 获取配置项
     * @param string $key 支持点语法，如 'platforms.taobao.app_key'
     * @param mixed $default
     * @return mixed
     */
    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }
        
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $segment) {
            if (!isset($value[$segment])) {
                return $default;
            }
            $value = $value[$segment];
        }
        
        return $value;
    }
    
    public function set($key, $value)
    {
        $keys = explode('.', $key);
        $target = &$this->config;
        
        foreach ($keys as $i => $segment) {
            if ($i === count($keys) - 1) {
                $target[$segment] = $value;
            } else {
                if (!isset($target[$segment]) || !is_array($target[$segment])) {
                    $target[$segment] = [];
                }
                $target = &$target[$segment];
            }
        }
        
        return $this;
    }
}