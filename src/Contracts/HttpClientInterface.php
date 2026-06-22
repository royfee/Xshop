<?php

namespace Royfee\XShop\Contracts;

/**
 * HTTP客户端接口
 */
interface HttpClientInterface
{
    /**
     * GET 请求
     * @param string $url 请求地址
     * @param array $params 请求参数
     * @param array $headers 请求头 (会合并到默认请求头)
     * @return array 返回解析后的响应数据
     */
    public function get(string $url, array $params = [], array $headers = []): array;

    /**
     * POST 请求
     * @param string $url 请求地址
     * @param array $params 请求参数
     * @param array $headers 请求头 (会合并到默认请求头)
     * @return array 返回解析后的响应数据
     */
    public function post(string $url, array $params = [], array $headers = []): array;

    /**
     * 设置请求超时
     * @param int $seconds
     * @return self
     */
    public function setTimeout(int $seconds): self;

    /**
     * 设置默认请求头 (追加)
     * @param array $headers
     * @return self
     */
    public function setHeaders(array $headers): self;

    /**
     * 获取当前默认请求头
     * @return array
     */
    public function getHeaders(): array;

    /**
     * 移除指定默认请求头
     * @param string $name
     * @return self
     */
    public function removeHeader(string $name): self;
}
