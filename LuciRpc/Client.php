<?php

declare(strict_types=1);

namespace LuciRpc;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use LuciRpc\Exceptions\RpcRequestException;

class Client
{
    private HttpClient $client;

    public function __construct(string $url, string $username, string $password)
    {
        $this->client = new HttpClient([
            'allow_redirects' => true,
            'base_uri' => str_ends_with($url, '/') ? $url : "$url/",
            'cookies' => true,
            'debug' => CONFIG_DEBUG,
            'timeout' => 5,
        ]);
        try {
            $this->request('auth', 'login', [$username, $password]);
        } catch (GuzzleException $ex) {
            throw new \RuntimeException('Luci RPC authentication failed');
        }
    }

    /**
     * RPC 请求
     *
     * @param  string  $module
     * @param  string  $method
     * @param  array  $params
     * @return mixed 如果 JSON 解析失败或不存在结果，返回 null
     * @throws GuzzleException
     * @throws RpcRequestException
     */
    public function request(string $module, string $method, array $params = []): mixed
    {
        $response = $this->client->post($module, [
            'json' => [
                'method' => $method,
                'params' => $params
            ]
        ]);
        try {
            $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
        $error = $json['error'] ?? null;
        if ($error) {
            throw new RpcRequestException($error);
        }
        return $json['result'] ?? null;
    }
}
