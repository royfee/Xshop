<?php
namespace Royfee\XShop\Core;

class Container
{
    protected $bindings = [];
    protected $instances = [];
    protected static $instance;

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 绑定：支持 类名 / 闭包工厂
     */
    public function bind($abstract, $concrete)
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * 获取实例，兼容类名、闭包工厂
     */
    public function make($abstract, $params = [])
    {
        // 已有单例直接返回
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (!isset($this->bindings[$abstract])) {
            throw new \Exception("Class {$abstract} not bound");
        }

        $concrete = $this->bindings[$abstract];

        // 1. 如果是闭包工厂，执行闭包得到实例
        if ($concrete instanceof \Closure) {
            $object = $concrete(...$params);
        }
        // 2. 如果是类名字符串，正常 new 实例
        else {
            $object = new $concrete(...$params);
        }

        $this->instances[$abstract] = $object;
        return $object;
    }
}