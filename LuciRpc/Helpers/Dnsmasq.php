<?php

declare(strict_types=1);

namespace LuciRpc\Helpers;

class Dnsmasq extends Helper
{
    /**
     * 将域名解析到指定地址
     *
     * @param  string  $domain
     * @param  string  $address
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \LuciRpc\Exceptions\RpcRequestException
     */
    public function domainToAddress(string $domain, string $address): void
    {
        $data = "address=/$domain/$address";
        $this->client->request('sys', 'call', ["echo '$data' > '/tmp/dnsmasq.d/$domain.conf'"]);
        $this->restartService();
    }

    /**
     * 重启服务
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \LuciRpc\Exceptions\RpcRequestException
     */
    public function restartService(): void
    {
        $this->client->request('sys', 'call', ['service dnsmasq restart']);
    }
}
