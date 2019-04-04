<?php

namespace AliDDNS;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Luci_RefreshRule
{
    /** @var string $error_msg 最后一次错误信息 */
    public $error_msg;
    /** @var bool $OK 是否执行成功 */
    public $OK;
    /** @var array $firewall_rule 防火墙规则 */
    private $firewall_rule;
    private $log;
    private $rpc_client;
    /** @var array 防火墙规则可用键名 */
    private $rule_uci_key = [
        "name",
        "family",
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
        "family" => "",
        "dest" => "lan",
        "dest_ip" => "",
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
        $this->log = new Log();
        $this->rpc_client = new Client([
            "base_uri" => CONFIG_LUCI_RPC_URL,
            "cookies" => true
        ]);
        if (!$this->RPC_Login()) {
            return;
        }
        $this->update_dnsmasq_resolv($dest_ip, $domain);
        if (!$this->firewall_rule_format($dest_ip, $family)) {
            return;
        }
        $this->delete_firewall_rules();
        $this->OK = $this->update_firewall_rules();
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
        $response = $this->RPC_Action("auth", "login", $params);
        if ($response) {
            $json = json_decode($response->getBody(), true);
            if (empty($json) || empty($json["result"])) {
                // 登陆失败
                $rpc_error_msg = empty($json["error"]) ? "null" : $json["error"];
                $error_msg = "Luci RPC Auth failed: " . $rpc_error_msg;
                $this->error_msg = $error_msg;
                $this->log->send($error_msg, 3);
                return false;
            }
        } else {
            // 登陆失败
            $error_msg = "Luci RPC Auth failed";
            $this->error_msg = $error_msg;
            $this->log->send($error_msg, 3);
            return false;
        }
        return true;
    }

    /**
     * RPC 快速调用函数
     * @param string $uri RPC 方法类型
     * @param string $method RPC 请求方法
     * @param array $params RPC 请求参数
     * @return bool|\Psr\Http\Message\ResponseInterface
     */
    private function RPC_Action(string $uri, string $method, array $params)
    {
        try {
            $response = $this->rpc_client->post($uri, [
                "json" => [
                    "method" => $method,
                    "params" => $params
                ]
            ]);
            return $response;
        } catch (RequestException $e) {
            $this->log->send($e->getMessage(), 3);
            return false;
        }
    }

    /**
     * 更新 Dnsmasq 解析
     * @param string $dest_ip 解析指向 IP
     * @param string $domain 解析域名
     */
    private function update_dnsmasq_resolv(string $dest_ip, string $domain)
    {
        $dest_ip = empty(CONFIG_DNSMASQ_RESOLV_ADDRESS) ? $dest_ip : CONFIG_DNSMASQ_RESOLV_ADDRESS;
        if (!filter_var($dest_ip, FILTER_VALIDATE_IP)) {
            return;
        }
        $data = "address=/$domain/$dest_ip";
        $response = $this->RPC_Action("sys", "call", [sprintf("echo \"%s\" > /tmp/dnsmasq.d/%s.conf", $data, $this->firewall_rule["mark"])]);
        if ($response) {
            $this->RPC_Action("sys", "call", ["/etc/init.d/dnsmasq restart"]);
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
        $rule_filename = __DIR__ . "/firewall_rule.json";
        // 防火墙规则文件是否存在
        if (file_exists($rule_filename)) {
            $this->firewall_rule = json_decode(file_get_contents($rule_filename), true);
            if (empty($this->firewall_rule) || empty($this->firewall_rule["name"]) || empty($this->firewall_rule["rules"])) {
                // 防火墙规则文件不正确
                $error_msg = "$rule_filename Firewall rule file is incorrect.";
                $this->error_msg = $error_msg;
                $this->log->send($error_msg, 3);
                return false;
            }
            if (empty($this->firewall_rule["mark"])) {
                // 防火墙规则标记不存在
                $this->firewall_rule["mark"] = substr(sha1($this->firewall_rule["name"]), -6); // 用防火墙规则名称的 SHA1 HASH 前六位作为标记
                file_put_contents($rule_filename, json_encode($this->firewall_rule, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        } else {
            // 防火墙规则文件不存在
            $error_msg = "$rule_filename Firewall rule file not exist.";
            $this->error_msg = $error_msg;
            $this->log->send($error_msg, 2);
            return false;
        }
        $this->rule_uci_value["family"] = $family;
        $this->rule_uci_value["dest_ip"] = $dest_ip;
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
        $response = $this->RPC_Action("uci", "get_all", ["firewall"]);
        if (!$response) {
            // 获取防火墙规则失败
            $this->log->send("Failed to get firewall rules.", 2);
            return;
        }
        $json = json_decode($response->getBody(), true);
        if (empty($json) || empty($json["result"])) {
            // 获取防火墙规则失败
            $rpc_error_msg = empty($json["error"]) ? "null" : $json["error"];
            $this->log->send("Failed to get firewall rules: $rpc_error_msg.", 2);
            return;
        }
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
            if (strstr($item["name"], sprintf("[%s]", $this->firewall_rule["mark"]))) {
                $response = $this->RPC_Action("uci", "delete", $params);
                if ($response) {
                    $json = json_decode($response->getBody(), true);
                    if (empty($json) || empty($json["result"])) {
                        // 删除防火墙规则失败
                        $rpc_error_msg = empty($json["error"]) ? "null" : $json["error"];
                        $this->log->send("Delete firewall rule failed: " . $item["name"] . " - $rpc_error_msg.", 2);
                    }
                } else {
                    // 删除防火墙规则失败
                    $this->log->send("Delete firewall rule failed: " . $item["name"], 2);
                }
            }
        }
        $this->RPC_Action("uci", "commit", ["firewall"]);
    }

    /**
     * 更新防火墙规则
     * @return bool 是否更新成功
     */
    private function update_firewall_rules()
    {
        $result = true;
        $failed_rules = [];
        $this->error_msg = "Firewall rule failed: ";
        // 遍历添加防火墙规则
        foreach ($this->firewall_rule["rules"] as $item) {
            $params = [
                "firewall",
                "rule"
            ];
            $response = $this->RPC_Action("uci", "add", $params);
            if (!$response) {
                // 添加防火墙规则失败
                $result = false;
                if (!in_array($item["name"], $failed_rules)) $failed_rules[] = $item["name"];
                $this->log->send("Add firewall rule section failed: " . $item["name"], 3);
                continue;
            }
            $json = json_decode($response->getBody(), true);
            if (empty($json) || empty($json["result"])) {
                // 添加防火墙规则失败
                $result = false;
                if (!in_array($item["name"], $failed_rules)) $failed_rules[] = $item["name"];
                $rpc_error_msg = empty($json["error"]) ? "null" : $json["error"];
                $this->log->send("Add firewall rule section failed: " . $item["name"] . " - $rpc_error_msg.", 3);
                continue;
            }
            $uci_section = $json["result"];
            // 遍历设置防火墙规则值
            foreach ($this->rule_uci_key as $key) {
                $value = empty($item[$key]) ? $this->firewall_rule[$key] : $item[$key];
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
                $response = $this->RPC_Action("uci", "set", $params);
                if ($response) {
                    $json = json_decode($response->getBody(), true);
                    if (empty($json) || empty($json["result"])) {
                        // 设置防火墙规则值失败
                        $result = false;
                        if (!in_array($item["name"], $failed_rules)) $failed_rules[] = $item["name"];
                        $rpc_error_msg = empty($json["error"]) ? "null" : $json["error"];
                        $this->log->send("Set firewall rule value failed: " . $item["name"] . " - $uci_section.$key.$value - $rpc_error_msg", 3);
                    }
                } else {
                    // 设置防火墙规则值失败
                    $result = false;
                    if (!in_array($item["name"], $failed_rules)) $failed_rules[] = $item["name"];
                    $this->log->send("Set firewall rule value failed: " . $item["name"] . " - $uci_section.$key.$value", 3);
                }
            }
        }
        $response = $this->RPC_Action("uci", "commit", ["firewall"]);
        if ($response) {
            $json = json_decode($response->getBody(), true);
            if (empty($json) || empty($json["result"])) {
                // 提交防火墙规则失败
                $result = false;
            }
        }
        $this->error_msg .= implode(" ", $failed_rules);
        return $result;
    }
}