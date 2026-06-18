<?php

namespace Royfee\XShop\Contracts;

interface HttpClientInterface
{
    public function post(string $url, array $params = []): array;
    public function get(string $url, array $params = []): array;
}