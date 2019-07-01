<?php
define("VERSION", "v6");
define("RUNNING_DIR", __DIR__);
define("LOG_FILENAME", RUNNING_DIR . "/AliDDNS.log");
define("FIREWALL_RULE_FILENAME", RUNNING_DIR . "/firewall_rule.json");

function compatible_old_config()
{
    // 兼容旧的配置文件
    print_r(get_defined_constants(true)["user"]);
    $config_value = get_defined_constants(true)["user"]; // 获取用户定义的常量
    $config_old_name = [ // 新配置项对应的旧配置项名称
        "CONFIG_DNS_RESOLVE_ADDRESS" => [
            "CONFIG_DNSMASQ_RESOLV_ADDRESS"
        ]
    ];
    $config_new_name = array_keys($config_old_name);
    foreach ($config_new_name as $CONFIG) { // 循环新配置项
        if (!isset($config_value[$CONFIG]) && !empty($config_old_name[$CONFIG])) { // 判断新配置项是否存在旧配置项
            foreach ($config_old_name[$CONFIG] as $OLD_CONFIG) { // 循环新配置项的旧配置项名称
                if (isset($config_value[$OLD_CONFIG])) { // 旧配置项是否存在
                    define($CONFIG, $config_value[$OLD_CONFIG]);
                    break;
                }
            }
        }
    }
    print_r(get_defined_constants(true)["user"]);
}
