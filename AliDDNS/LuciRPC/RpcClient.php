<?php
declare(strict_types=1);

namespace AliDDNS\LuciRPC;

use AliDDNS\Logger;
use Exception;
use GuzzleHttp\Client;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use UnexpectedValueException;

class RpcClient
{
    private static ?Client $rpcClient = null;

    /**
     * 初始化 RPC 客户端
     */
    public static function init(): void
    {
        if (self::$rpcClient !== null) {
            return;
        }
        self::$rpcClient = new Client([
            'base_uri' => CONFIG_LUCI_RPC_URL,
            'cookies' => true
        ]);
        try {
            self::RPC('auth', 'login', [CONFIG_LUCI_USER, CONFIG_LUCI_PASSWORD]);
        } catch (Exception $e) {
            self::$rpcClient = null;
            Logger::send(Logger::ERROR, $e->getMessage());
            throw new RuntimeException('Luci RPC Auth failed');
        }
    }

    /**
     * RPC 调用函数
     *
     * @param string $uri RPC 方法类型
     * @param string $method RPC 请求方法
     * @param array $params RPC 请求参数
     * @return StreamInterface
     * @throws Exception
     */
    public static function RPC(string $uri, string $method, array $params): StreamInterface
    {
        if (self::$rpcClient === null) {
            throw new UnexpectedValueException('RPC client is null');
        }
        $result = self::$rpcClient->post($uri, [
            'json' => [
                'method' => $method,
                'params' => $params
            ]
        ]);
        if (!self::rpcIsSuccess($result)) {
            Logger::send(Logger::ERROR, "RPC response error: $uri - $method - " . implode(',', $params));
            throw new UnexpectedValueException('RPC response error');
        }
        return $result->getBody();
    }

    /**
     * 检查 RPC 请求是否成功
     *
     * @param ResponseInterface $response RPC 请求的响应
     * @return bool 请求是否成功
     */
    private static function rpcIsSuccess(ResponseInterface $response): bool
    {
        $result = false;
        if ($response->getStatusCode() === 200) {
            try {
                $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
                if (!empty($json) && isset($json['result'])) {
                    $result = true;
                }
            } catch (JsonException $e) {
                Logger::send(Logger::ERROR, $e->getMessage());
            }
        }
        return $result;
    }
}
