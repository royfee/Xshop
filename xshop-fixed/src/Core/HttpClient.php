<?php

namespace Royfee\XShop\Core;

use Royfee\XShop\Contracts\HttpClientInterface;
use Monolog\Logger;

class HttpClient implements HttpClientInterface
{
    protected $logger;
    protected $debug;

    public function __construct(Logger $logger, bool $debug = false)
    {
        $this->logger = $logger;
        $this->debug = $debug;
    }

    public function post(string $url, array $params = []): array
    {
        return $this->request('POST', $url, $params);
    }

    public function get(string $url, array $params = []): array
    {
        return $this->request('GET', $url, $params);
    }

    protected function request(string $method, string $url, array $params): array
    {
        if ($this->debug) {
            $this->logger->info("HTTP Request", [
                'method' => $method,
                'url' => $url,
                'params' => $params
            ]);
        }

        $ch = curl_init();
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        } else {
            $url .= '?' . http_build_query($params);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->logger->error("HTTP Error", ['error' => $error]);
            throw new \Exception($error);
        }

        $result = json_decode($response, true) ?: [];

        if ($this->debug) {
            $this->logger->info("HTTP Response", ['result' => $result]);
        }

        return $result;
    }
}