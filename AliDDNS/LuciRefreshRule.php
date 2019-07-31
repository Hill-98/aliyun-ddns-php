<?php

namespace AliDDNS;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class LuciRefreshRule
{
    /** @var string $error_msg 最后一次错误信息 */
    public $error_msg;
    /** @var bool $OK 是否执行成功 */
    public $OK;
    /** @var array $firewall_rule 防火墙规则 */
    private $firewall_rule;
    private $logger;
    private $rpc_client;
    /** @var array 防火墙规则可用键名 */
    private $rule_uci_key = [
        "name",
        "family",
        "proto",
        "dest",
        "dest_ip",
        "dest_port",
        "src",
        "src_ip",
        "src_mac",
        "src_port",
        "target",
        "extra"
    ];
    /** @var array 防火墙规则可用默认值 */
    private $rule_uci_value = [
        "proto" => "tcp udp",
        "dest" => "lan",
        "src" => "wan",
        "target" => "ACCEPT"
    ];

    /**
     * Luci_RefreshRule constructor.
     * @param string $dest_ip 防火墙规则目标 IP
     * @param string $domain DNS 解析域名
     * @param string $family IP 类型
     */
    function __construct(string $dest_ip, string $domain, string $family)
    {
        $this->logger = new Logger();
        $this->rpc_client = new Client([
            "base_uri" => CONFIG_LUCI_RPC_URL,
            "cookies" => true
        ]);
        if (!$this->RPC_Login()) {
            return;
        }
        if ($this->firewall_rule_format($dest_ip, $family)) {
            $this->delete_firewall_rules();
            $this->OK = $this->update_firewall_rules();
        }
        $this->update_dns_resolve($dest_ip, $domain);

    }

    /**
     * RPC 登陆
     * @return bool 是否登陆成功
     */
    private function RPC_Login()
    {
        $params = [
            CONFIG_LUCI_USER,
            CONFIG_LUCI_PASSWORD
        ];
        $response = $this->Luci_RPC("auth", "login", $params);
        $error_text = "Luci RPC Auth failed";
        return $this->rpc_is_success($response, $error_text);
    }

    /**
     * LUCI RPC 调用函数
     * @param string $uri RPC 方法类型
     * @param string $method RPC 请求方法
     * @param array $params RPC 请求参数
     * @return bool|ResponseInterface
     */
    private function Luci_RPC(string $uri, string $method, array $params)
    {
        $result = false;
        try {
            $result = $this->rpc_client->post($uri, [
                "json" => [
                    "method" => $method,
                    "params" => $params
                ]
            ]);
        } catch (RequestException $e) {
            $this->logger->send(Logger::ERROR, $e->getMessage());
        }
        return $result;
    }

    /**
     * 更新 DNS 解析
     * @param string $dest_ip 解析指向 IP
     * @param string $domain 解析域名
     */
    private function update_dns_resolve(string $dest_ip, string $domain)
    {
        $dest_ip = empty(CONFIG_DNS_RESOLVE_ADDRESS) ? $dest_ip : CONFIG_DNS_RESOLVE_ADDRESS;
        if (!filter_var($dest_ip, FILTER_VALIDATE_IP)) {
            return;
        }
        $filename = empty($this->firewall_rule["mark"]) ? $domain : $this->firewall_rule["mark"];
        $data = "address=/$domain/$dest_ip";
        $response = $this->Luci_RPC("sys", "call", [sprintf("echo \"%s\" > /tmp/dnsmasq.d/%s.conf", $data, $filename)]);
        if ($response) {
            $this->Luci_RPC("sys", "call", ["/etc/init.d/dnsmasq restart"]);
        }
    }

    /**
     * 检查及格式化防火墙规则
     * @param string $dest_ip 防火墙目标 IP
     * @param string $family 防火墙目标 IP 版本
     * @return bool 防火墙规则是否正确
     */
    private function firewall_rule_format(string $dest_ip, string $family)
    {
        // 防火墙规则文件是否存在
        if (file_exists(FIREWALL_RULE_FILENAME)) {
            $this->firewall_rule = json_decode(file_get_contents(FIREWALL_RULE_FILENAME), true);
            // 防火墙规则文件不正确
            if (empty($this->firewall_rule) || empty($this->firewall_rule["name"]) || empty($this->firewall_rule["rules"])) {
                $error_msg = FIREWALL_RULE_FILENAME . " Firewall rule file is incorrect.";
                $this->error_msg = $error_msg;
                $this->logger->send(Logger::ERROR, $error_msg);
                return false;
            }
            // 防火墙规则标记不存在
            if (empty($this->firewall_rule["mark"])) {
                // 用防火墙规则名称的 SHA1 HASH 前六位作为标记
                $this->firewall_rule["mark"] = substr(sha1($this->firewall_rule["name"]), -6);
                file_put_contents(FIREWALL_RULE_FILENAME, json_encode($this->firewall_rule, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        } else {
            // 防火墙规则文件不存在
            $error_msg = FIREWALL_RULE_FILENAME . " Firewall rule file not exist.";
            $this->error_msg = $error_msg;
            $this->logger->send(Logger::ERROR, $error_msg);
            return false;
        }
        $this->firewall_rule["family"] = $family;
        $this->firewall_rule["dest_ip"] = $dest_ip;
        // 补充缺省值
        foreach ($this->rule_uci_key as $key) {
            if (empty($this->firewall_rule[$key])) {
                $this->firewall_rule[$key] = empty($this->rule_uci_value[$key]) ? null : $this->rule_uci_value[$key];
            }
        }
        // 清除没有名称的规则以及重命名规则
        foreach ($this->firewall_rule["rules"] as $key => &$item) {
            if (empty($item["name"])) {
                unset($this->firewall_rule["rules"][$key]);
            } else {
                $item["name"] = sprintf("[%s] %s - %s", $this->firewall_rule["mark"], $this->firewall_rule["name"], $item["name"]);
            }
        }
        return true;
    }

    /**
     * 删除旧的防火墙规则
     */
    private function delete_firewall_rules()
    {
        // 获取所有防火墙规则
        $response = $this->Luci_RPC("uci", "get_all", ["firewall"]);
        $error_text = "Failed to get firewall rules";
        if (!$this->rpc_is_success($response, $error_text, Logger::WARNING)) {
            return;
        }
        $json = json_decode($response->getBody(), true);
        // 遍历防火墙规则，匹配规则特征值，以删除旧的规则。
        foreach ($json["result"] as $key => $item) {
            if (empty($item["name"])) {
                continue;
            }
            $params = [
                "firewall",
                $key
            ];
            // 查询规则名称是否存在特征值
            if (strstr($item["name"], "[{$this->firewall_rule["mark"]}]")) {
                $response = $this->Luci_RPC("uci", "delete", $params);
                $error_text = "Delete firewall rule failed: {$item["name"]}";
                // 删除防火墙规则是否成功
                $this->rpc_is_success($response, $error_text, Logger::WARNING);
            }
        }
        $this->Luci_RPC("uci", "commit", ["firewall"]);
    }

    /**
     * 检查 RPC 请求是否成功
     * @param ResponseInterface $response RPC 请求的相应
     * @param string $error_text 错误文本
     * @param int $log_level 错误文本写入日志时的等级
     * @return bool RPC 是否成功
     */
    private function rpc_is_success(ResponseInterface $response, string $error_text = "", int $log_level = Logger::ERROR)
    {
        $result = true;
        if ($response) {
            $json = json_decode($response->getBody(), true);
            if (empty($json) || empty($json["result"])) {
                $result = false;
                $rpc_error_msg = empty($json["error"]) ? "null" : $json["error"];
                if (!empty($error_text)) $error_text .= " - $rpc_error_msg";
            }
        } else {
            $result = false;
            // 检查已失败规则里是否存在该规则
        }
        if (!$result && !empty($error_text)) {
            $this->error_msg = $error_text;
            $this->logger->send($log_level, $error_text);
        }
        return $result;
    }

    /**
     * 更新防火墙规则
     * @return bool 是否更新成功
     */
    private function update_firewall_rules()
    {
        $result = true;
        $failed_rules = [];
        // 遍历添加防火墙规则
        foreach ($this->firewall_rule["rules"] as $rule) {
            $params = [
                "firewall",
                "rule"
            ];
            $response = $this->Luci_RPC("uci", "add", $params);
            $rule_name = $rule["name"];
            $error_text = "Add firewall rule section failed: $rule_name";
            if (!$this->rpc_is_success($response, $error_text)) {
                $result = false;
                if (!in_array($rule_name, $failed_rules)) $failed_rules[] = $rule_name;
                continue;
            }
            $json = json_decode($response->getBody(), true);
            $uci_section = $json["result"];
            // 遍历设置防火墙规则值
            foreach ($this->rule_uci_key as $key) {
                // 如果是限制地址和目标地址，直接使用指定值。
                if ($key === "family" || $key === "dest_ip") {
                    $value = $this->firewall_rule[$key];
                } else {
                    // 如果是目标端口，必须使用规则设置的值。
                    if ($key === "dest_port" && empty($rule[$key])) {
                        continue;
                    }
                    $value = empty($rule[$key]) ? $this->firewall_rule[$key] : $rule[$key];
                }
                // 空值不设置
                if (empty($value)) {
                    continue;
                }
                $params = [
                    "firewall",
                    $uci_section,
                    $key,
                    $value
                ];
                $response = $this->Luci_RPC("uci", "set", $params);
                $error_text = "Set firewall rule value failed: {$rule_name} - $uci_section.$key.$value";
                if (!$this->rpc_is_success($response, $error_text)) {
                    $result = false;
                    if (!in_array($rule_name, $failed_rules)) $failed_rules[] = $rule_name;
                }
            }
        }
        $response = $this->Luci_RPC("uci", "commit", ["firewall"]);
        if (!$this->rpc_is_success($response, "Commit firewall rule failed")) $result = false;
        if (!empty($failed_rules)) {
            $this->error_msg = "Firewall rule failed: " . implode(" ", $failed_rules);
        }
        return $result;
    }
}