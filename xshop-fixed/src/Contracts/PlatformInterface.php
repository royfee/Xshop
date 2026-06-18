<?php

namespace Royfee\XShop\Contracts;

interface PlatformInterface
{
    public function auth(): AuthInterface;
    public function http(): HttpClientInterface;
    public function goods(): GoodsApiInterface;
    public function order(): OrderApiInterface;
}