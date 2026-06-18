<?php
namespace royfee\xshop\Platforms\Youzan;

use royfee\xshop\Platforms\BaseApi;

/**
 * 有赞 API 调度器
 */
class Api extends BaseApi
{
    const API = 'https://open.youzanyun.com/api/';

    /**
     * @var Youzan
     */
    protected $youzan;

    public function __construct(Youzan $youzan)
    {
        $this->youzan = $youzan;
    }

    /**
     * 请求有赞 API
     *
     * @param string $method 形如 'youzan.trade.get'
     * @param array  $params
     * @param array  $files
     * @return array
     * @throws YouzanException
     */
    public function request($method, $params = [], $files = [])
    {
        $url  = $this->buildUrl($method);
        $http = $this->getHttp();

        $token = $this->youzan['token']->getToken();
        $url  .= '?' . http_build_query(['access_token' => $token]);

        $response = $files
            ? $http->upload($url, $params, $this->formatFiles($files))
            : $http->json($url, $params ?: json_decode('{}'));

        $result = json_decode(strval($response->getBody()), true);
        if (!is_array($result)) {
            throw new YouzanException('有赞接口返回非 JSON 数据');
        }

        if (isset($result['gw_err_resp'])) {
            $err = $result['gw_err_resp'];
            throw new YouzanException(
                $err['err_msg'] ?? $err['err_message'] ?? '有赞接口错误',
                (int)($err['err_code'] ?? 0),
                $result
            );
        }

        return $result;
    }

    private function formatFiles(array $files): array
    {
        $formatted = [];
        foreach ($files as $name => $path) {
            if (is_array($path)) {
                $items = [];
                foreach ($path as $p) {
                    $items[] = ['contents' => $p, 'filename' => 'example'];
                }
                $formatted[$name] = $items;
            } else {
                $formatted[$name] = ['contents' => $path, 'filename' => 'example'];
            }
        }
        return $formatted;
    }

    private function buildUrl(string $method): string
    {
        return self::API . $method . '/' . $this->youzan->getVersion();
    }
}
