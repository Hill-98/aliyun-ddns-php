<?php

declare(strict_types=1);

/**
 * @param int $ipType
 * @return string
 * @throws Exception
 */
function getIP(int $ipType): string
{
    switch ($ipType) {
        case IP_TYPE_V4:
            $queryType = 'A';
            $nameservers = [
                '208.67.222.123',
                '208.67.220.123',
            ];
            break;
        case IP_TYPE_V6:
            $queryType = 'AAAA';
            $nameservers = [
                '2620:119:35::35',
                '2620:119:53::53',
            ];
            break;
        default:
            throw new InvalidArgumentException('$ipType only is IP_TYPE_V4 or IP_TYPE_V6');
    }
    $resolver = new Net_DNS2_Resolver([
        'nameservers' => $nameservers,
        'dns_port' => 5353,
        'recurse' => false,
        'dnssec' => true,
    ]);
    $result = $resolver->query('myip.opendns.com', $queryType);
    if (!isset($result->answer[0]->address)) {
        throw new UnexpectedValueException('IP not found');
    }
    // 清理 IPv6 地址多余的 :0
    $ip = inet_ntop(inet_pton($result->answer[0]->address));
    // 验证获取到的 IP 是否正确
    if (filter_var($ip, FILTER_VALIDATE_IP, $ipType) === $ip) {
        return $ip;
    }
    throw new UnexpectedValueException('IP format is incorrect');
}
