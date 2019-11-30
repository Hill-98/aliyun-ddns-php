<?php

namespace AliDDNS;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class GetIP
{
    /** @var string $error_msg 最后一次错误信息 */
    public $error_msg;
    /** @var mixed $IP 获取到的 IP */
    public $IP;

    /**
     * GetIP constructor.
     * @param int $ip_type 获取的 IP 类型
     */
    public function __construct(int $ip_type)
    {
        if ($ip_type === IP_TYPE_V4) {
            $api_get_url = "http://v4.ip.zxinc.org";
            $ip_filter = FILTER_FLAG_IPV4;
        } elseif ($ip_type === IP_TYPE_V6) {
            $api_get_url = "http://v6.ip.zxinc.org";
            $ip_filter = FILTER_FLAG_IPV6;
        } else {
            return;
        }
        $http_client = new Client([
            "base_uri" => $api_get_url
        ]);
        try {
            $response = $http_client->get("/getip");
            $_ip = str_replace(["\r\n", "\n"], "", $response->getBody());
            // 验证获取到的 IP 是否正确
            if (filter_var($_ip, FILTER_VALIDATE_IP, $ip_filter) === $_ip) {
                $this->IP = $_ip;
            } else {
                $error_msg = "IP format is incorrect.";
                $this->error_msg = $error_msg;
            }
        } catch (RequestException $e) {
            $this->error_msg = $e->getMessage();
        }
    }
}
