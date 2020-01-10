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
            $apiUrl = 'http://v4.ip.ss.zxinc.org/getip';
            break;
        case IP_TYPE_V6:
            $apiUrl = 'http://v6.ip.ss.zxinc.org/getip';
            break;
        default:
            throw new InvalidArgumentException('$ipType only is IP_TYPE_V4 or IP_TYPE_V6');
    }
    $client = new Client([
        'base_uri' => $apiUrl
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
