<?php

namespace AliDDNS\LuciRPC;

use AliDDNS\Logger;
use Exception;
use RuntimeException;
use UnexpectedValueException;

class Firewall
{
    /** @var array 防火墙规则可用键名 */
    private const ruleUciKey = [
        'name',
        'family',
        'proto',
        'dest',
        'dest_ip',
        'dest_port',
        'src',
        'src_ip',
        'src_mac',
        'src_port',
        'target',
        'extra'
    ];
    /** @var array 防火墙规则可用默认值 */
    private const ruleUciDefaultValue = [
        'proto' => 'tcp udp',
        'dest' => 'lan',
        'src' => 'wan',
        'target' => 'ACCEPT'
    ];

    /**
     * 刷新防火墙规则
     *
     * @param string $destIP 防火墙规则目标 IP
     * @param string $family IP 类型
     * @throws Exception
     */
    public static function refreshRule(string $destIP, string $family): void
    {
        $firewallRule = self::firewallRuleCheck($destIP, $family);
        self::deleteOldRule($firewallRule['mark']);
        self::updateRules($firewallRule);
    }

    /**
     * 检查及格式化防火墙规则
     *
     * @param string $destIP 防火墙目标 IP
     * @param string $family 防火墙目标 IP 版本
     * @return null|array 防火墙规则
     * @throws Exception
     */
    private static function firewallRuleCheck(string $destIP, string $family): ?array
    {
        $firewallRule = null;
        if (!file_exists(FIREWALL_RULE_FILENAME)) {
            throw new RuntimeException('FIREWALL_RULE_FILENAME not exists');
        }
        $firewallRule = json_decode(file_get_contents(FIREWALL_RULE_FILENAME), true, 512, JSON_THROW_ON_ERROR);
        // 防火墙规则文件不正确
        if (empty($firewallRule['name']) || !isset($firewallRule['rules'])) {
            throw new UnexpectedValueException(FIREWALL_RULE_FILENAME . ' Firewall rule file is incorrect');
        }
        // 防火墙规则标记不存在
        if (empty($firewallRule['mark'])) {
            // 用防火墙规则名称的 SHA1 HASH 前六位作为标记
            $firewallRule['mark'] = substr(sha1($firewallRule['name']), -6);
            file_put_contents(FIREWALL_RULE_FILENAME, json_encode($firewallRule, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE, 512));
        }
        $firewallRule['family'] = $family;
        $firewallRule['dest_ip'] = $destIP;
        // 补充缺省值
        foreach (self::ruleUciKey as $key) {
            if (!isset($firewallRule[$key])) {
                $firewallRule[$key] = self::ruleUciDefaultValue[$key] ?? null;
            }
        }
        // 删除没有名称的规则和重命名规则
        foreach ($firewallRule['rules'] as $key => &$item) {
            if (empty($item['name'])) {
                unset($firewallRule['rules'][$key]);
            } else {
                $item['name'] = sprintf('[%s] %s - %s', $firewallRule['mark'], $firewallRule['name'], $item['name']);
            }
        }
        return $firewallRule;
    }

    /**
     * 删除旧的防火墙规则
     *
     * @param string $mark
     * @throws Exception
     */
    private static function deleteOldRule(string $mark): void
    {
        // 获取所有防火墙规则
        $response = RpcClient::RPC('uci', 'get_all', ['firewall']);
        $json = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        // 遍历防火墙规则，匹配规则特征值，以删除旧的规则。
        foreach ($json['result'] as $key => $item) {
            if (empty($item['name'])) {
                continue;
            }
            $params = [
                'firewall',
                $key
            ];
            // 查询规则名称是否存在特征值
            if (strpos($item['name'], "[$mark]") !== false) {
                try {
                    RpcClient::RPC('uci', 'delete', $params);
                } catch (Exception $e) {
                    Logger::send(Logger::ERROR, "Delete firewall rule failed: {$item['name']}");
                }
            }
        }
        RpcClient::RPC('uci', 'commit', ['firewall']);
    }

    /**
     * 更新防火墙规则
     * @param array $firewallRule
     * @throws Exception
     */
    private static function updateRules(array $firewallRule): void
    {
        $failedRule = [];
        // 遍历添加防火墙规则
        foreach ($firewallRule['rules'] as $rule) {
            $params = [
                'firewall',
                'rule'
            ];
            $name = $rule['name'];
            try {
                $response = RpcClient::RPC('uci', 'add', $params);
            } catch (Exception $e) {
                if (!in_array($name, $failedRule, true)) {
                    $failedRule[] = $name;
                    Logger::send(Logger::ERROR, "Add firewall rule section failed: $name");
                }
                continue;
            }
            $json = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            $uciSection = $json['result'];
            // 遍历设置防火墙规则值
            foreach (self::ruleUciKey as $key) {
                // 如果是限制地址或目标地址，直接使用指定值。
                if ($key === 'family' || $key === 'dest_ip') {
                    $value = $firewallRule[$key];
                } else {
                    // 如果是目标端口，必须使用规则设置的值。
                    if ($key === 'dest_port' && empty($rule[$key])) {
                        continue;
                    }
                    $value = $rule[$key] ?? $firewallRule[$key];
                }
                if (empty($value)) {
                    continue;
                }
                $params = [
                    'firewall',
                    $uciSection,
                    $key,
                    $value
                ];
                try {
                    RpcClient::RPC('uci', 'set', $params);
                } catch (Exception $e) {
                    if (!in_array($name, $failedRule, true)) {
                        $failedRule[] = $name;
                        Logger::send(Logger::ERROR, "Set firewall rule value failed: $name - $uciSection.$key.$value");
                    }
                }
            }
        }
        RpcClient::RPC('uci', 'commit', ['firewall']);
        if (!empty($failedRule)) {
            Logger::send(Logger::ERROR, 'Firewall rule failed: ' . implode(' ', $failedRule));
            throw new RuntimeException('some rules failed');
        }
    }
}
