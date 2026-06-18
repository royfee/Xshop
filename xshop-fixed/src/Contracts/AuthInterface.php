<?php

namespace Royfee\XShop\Contracts;

interface AuthInterface
{
    public function getToken(): string;
    public function refreshToken(): string;
}