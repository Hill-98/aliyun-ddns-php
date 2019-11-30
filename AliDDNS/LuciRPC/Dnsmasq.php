<?php

namespace AliDDNS\LuciRPC;

use Exception;
use InvalidArgumentException;

class Dnsmasq
{
    /**
     * 更新 DNS 解析
     * @param string $domain 解析域名
     * @param string $destIP 解析指向 IP
     * @throws Exception
     */
    public static function updateResolve(string $domain, string $destIP = ''): void
    {
        if (empty($destIP) && empty(CONFIG_DNS_RESOLVE_ADDRESS)) {
            throw new InvalidArgumentException('$destIP and CONFIG_DNS_RESOLVE_ADDRESS are empty');
        }
        $ip = empty(CONFIG_DNS_RESOLVE_ADDRESS) ? $destIP : CONFIG_DNS_RESOLVE_ADDRESS;
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException('$destIP or CONFIG_DNS_RESOLVE_ADDRESS not is ip addr');
        }
        $data = "address=/$domain/$ip";
        RpcClient::RPC('sys', 'call', [sprintf('echo "%s" > /tmp/dnsmasq.d/%s.conf', $data, $domain)]);
        RpcClient::RPC('sys', 'call', ['/etc/init.d/dnsmasq restart']);
    }
}
