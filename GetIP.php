<?php

namespace AliDDNS;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class GetIP
{
    /** @var string $error_msg 最后一次错误信息 */
    public $error_msg;
    /** @var mixed $ip 获取到的 IP */
    public $ip;

    /**
     * GetIP constructor.
     * @param string $type 获取的 IP 类型
     */
    function __construct(string $type)
    {
        // 判断要获取的 IP 地址类型
        if (strtolower($type) === "ipv4") {
            $get_url = "https://ip4.seeip.org";
            $IP_FILTER = FILTER_FLAG_IPV4;
        } elseif (strtolower($type) === "ipv6") {
            $get_url = "https://ip6.seeip.org";
            $IP_FILTER = FILTER_FLAG_IPV6;
        } else {
            return;
        }
        $log = new Log();
        $client = new Client([
            "base_uri" => $get_url
        ]);
        try {
            $response = $client->get("/");
            $ip = str_replace(["\r\n", "\n"], "", $response->getBody()); // 去除换行符
            // 验证获取到的 IP 地址是否符合类型
            if (filter_var($ip, FILTER_VALIDATE_IP, $IP_FILTER) === $ip) {
                $this->ip = $ip;
            } else {
                // IP 验证失败
                $error_msg = "IP format is incorrect.";
                $this->error_msg = $error_msg;
                $log->send($error_msg, 3);
            }
        } catch (RequestException $e) {
            $this->error_msg = $e->getMessage();
            $log->send($e->getMessage(), 3);
        }
    }
}