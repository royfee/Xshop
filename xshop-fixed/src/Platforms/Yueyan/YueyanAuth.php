<?php
namespace Royfee\XShop\Platforms\Yueyan;

use Royfee\XShop\Contracts\AuthInterface;

class YueyanAuth implements AuthInterface
{
    public function getToken(): string
    {
        return '';
    }

    public function refreshToken(): string
    {
        return '';
    }
}