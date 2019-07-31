<?php

namespace AliDDNS;

require __DIR__ . "/define.php";
$first_parameter = $argv[1] ?? "";
if (strtolower($first_parameter) === "version") {
    exit(VERSION . PHP_EOL);
}
if (file_exists(__DIR__ . "/config.php")) {
    require __DIR__ . "/config.php";
    compatible_old_config();
} else {
    exit("config.php not exist.");
}
if (!empty(CONFIG_DEBUG)) {
    error_reporting(E_ERROR);
}
require __DIR__ . "/vendor/autoload.php";

/**
 * 主函数
 * 将核心功能封装成主函数是为了防止执行国成中需要终止执行，导致防止重复运行的文件无法删除。
 */
function main()
{
    // 解析记录配置
    $config = [
        "name" => "example",
        "type" => "A",
        "value" => "127.0.0.1",
        "ttl" => empty(CONFIG_AliDNS_TTL) ? 600 : CONFIG_AliDNS_TTL,
        "line" => empty(CONFIG_AliDNS_LINE) ? "default" : CONFIG_AliDNS_LINE
    ];
    // 接受的参数
    $arg_key = [
        "name",
        "value",
        "update-rule"
    ];

    $logger = new Logger();
    // 如果是命令行执行则接受命令行传参 否则接受 GET 方法传参
    if (php_sapi_name() === "cli") {
        // 处理接受的参数名称，以便接受值。
        foreach ($arg_key as &$value) {
            $value .= ":";
        }
        unset($value);
        $args = getopt(null, $arg_key); // 读取命令行参数
    } else {
        foreach ($arg_key as $key) { // 遍历 GET 参数
            $args[$key] = $_GET[$key] ?? "";
        }
    }

    if (empty($args) || empty($args["name"]) || empty($args["value"])) {
        echo "Incomplete parameters" . PHP_EOL;
        return;
    }
    // 将传参更新到配置参数
    foreach ($config as $key => &$value) {
        $value = empty($args[$key]) ? $value : $args[$key];
    }
    unset($value);
    // 自动获取 IP
    $ip = $config["value"];
    if ($ip === "ipv4" || $ip === "ipv6") {
        $ip_type = $ip === "ipv4" ? IP_TYPE_V4 : IP_TYPE_V6;
        $logger->send(Logger::INFO, "Auto Get IP: $ip");
        $GetIP = new GetIP($ip_type);
        if (!$ip = $GetIP->IP) {
            // 自动获取 IP 失败
            $logger->send(Logger::ERROR, "Auto Get IP failed: $GetIP->error_msg", true);
            return;
        }
    }
    // 判断 IP 地址类型
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === $ip) {
        $config["type"] = "A";
        $ip_type = IP_TYPE_V4;
    } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === $ip) {
        $config["type"] = "AAAA";
        $ip_type = IP_TYPE_V6;
    } else {
        $logger->send(Logger::ERROR, "IP format is incorrect.", true);
        return;
    }
    $config["value"] = $ip;
    $domain = sprintf("%s.%s", $config["name"], CONFIG_DOMAIN);
    if ($args["update-rule"] ?? "" !== "true") {
        $_text = $config["value"] . " -> " . $domain;
        $logger->send(Logger::INFO, "AliDDNS Start: $_text");
        $AliDDNS = new AliDDNS($config);
        if ($AliDDNS->OK) {
            $logger->send(Logger::INFO, "AliDDNS success: $_text");
        } else {
            $logger->send(Logger::ERROR, "AliDDNS failed: $_text $AliDDNS->error_msg", true);
        }
    }
    if (!CONFIG_UPDATE_ROUTER) {
        return;
    }
    $logger->send(Logger::INFO, "Luci Refresh Rule Start.");
    $family = $ip_type === IP_TYPE_V4 ? "ipv4" : "ipv6";
    $luci_refresh_rule = new LuciRefreshRule($ip, $domain, $family);
    if ($luci_refresh_rule->OK) {
        $logger->send(Logger::INFO, "Luci Refresh Rule success.");
    } else {
        $logger->send(Logger::ERROR, "Luci Refresh Rule failed: $luci_refresh_rule->error_msg", true);
    }
}
$running_filename = RUNNING_DIR . "/running";
if (file_exists($running_filename)) {
    exit("Running" . PHP_EOL);
} else {
    file_put_contents($running_filename, $running_filename);
}
main();
unlink($running_filename);