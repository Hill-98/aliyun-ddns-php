<?php
declare(strict_types=1);

use GuzzleHttp\Client;

/**
 * @param int $ipType
 * @return string|null
 * @throws Exception
 */
function getIP(int $ipType): ?string
{
    switch ($ipType) {
        case IP_TYPE_V4:
            $ipResolve = 'v4';
            break;
        case IP_TYPE_V6:
            $ipResolve = 'v6';
            break;
        default:
            throw new InvalidArgumentException('$ipType only is IP_TYPE_V4 or IP_TYPE_V6');
    }
    $client = new Client([
        'base_uri' => 'https://ip.lsy.cn/getip',
        'connect_timeout' => 3,
        'force_ip_resolve' => $ipResolve
    ]);
    try {
        $response = $client->get('/getip');
        $ip = str_replace(["\r\n", "\n"], '', $response->getBody());
        // 验证获取到的 IP 是否正确
        if (filter_var($ip, FILTER_VALIDATE_IP, $ipType) === $ip) {
            return $ip;
        }
        throw new UnexpectedValueException('IP format is incorrect.');
    } catch (Exception $e) {
        throw $e;
    }
}
