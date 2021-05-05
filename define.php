<?php
const VERSION = 17;
const BASEDIR = __DIR__;
const FIREWALL_RULE_FILENAME = __DIR__ . '/firewall_rule.json';
const IP_TYPE_V4 = FILTER_FLAG_IPV4;
const IP_TYPE_V6 = FILTER_FLAG_IPV6;

/**
 * 兼容旧的配置文件
 */
function compatibleOldConfig()
{
    // 新配置项对应的旧配置项名称
    $configOldName = [
        'CONFIG_DNS_RESOLVE_ADDRESS' => 'CONFIG_DNSMASQ_RESOLV_ADDRESS',
        'CONFIG_UPDATE_FIREWALL' => 'CONFIG_UPDATE_ROUTER',
        'CONFIG_UPDATE_DNSMASQ' => 'CONFIG_UPDATE_ROUTER'
    ];
    $configNewKeys = array_keys($configOldName);
    // 遍历新配置项
    foreach ($configNewKeys as $key) {
        // 判断当前配置项是否不存在新配置项
        if (!defined($key) && defined($configOldName[$key])) {
            define($key, constant($configOldName[$key]));
        }
    }
}
