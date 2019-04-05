<?php

namespace AliDDNS;
define("VERSION", "v2");
if (!empty($argv[1]) && $argv[1] === "version") {
    echo VERSION . PHP_EOL;
    exit();
}
$running_filename = "running";
if (file_exists($running_filename)) {
    exit();
} else {
    file_put_contents($running_filename, $running_filename);
}
if (file_exists(__DIR__ . "/config.php")) {
    require_once __DIR__ . "/config.php";
} else {
    echo "config.php not exist";
    exit();
}
if (!empty(CONFIG_DEBUG)) {
    error_reporting(E_ERROR);
}
require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/define.php";
spl_autoload_register(function ($class_name) {
    require_once __DIR__ . "/$class_name.php";
});

/**
 * 更新 OpenWrt 规则 快速调用函数
 * @param string $dest_ip 防火墙规则目标 IP
 * @param string $domain DNS 解析域名
 * @param string $family IP 类型
 */
function Luci_RefreshRule(string $dest_ip, string $domain, string $family)
{
    if (empty(CONFIG_UPDATE_ROUTER)) {
        return;
    }
    global $log;
    $log->send("Luci Refresh Rule Start.", 1);
    $lrr = new Luci_RefreshRule($dest_ip, $domain, $family);
    if ($lrr->OK) {
        $log->send("Luci Refresh Rule success.", 1);
    } else {
        $log->send("Luci Refresh Rule failed: " . $lrr->error_msg, 3, true);
    }
}

/**
 * 删除运行状态文件并退出脚本
 * @param int $status 退出状态代码或文本
 */
function _exit($status = 0)
{
    global $running_filename;
    while (file_exists($running_filename)) {
        unlink($running_filename);
    }
    exit($status);
}

// 解析记录配置
$config = [
    "name" => "example",
    "type" => "A",
    "value" => "127.0.0.1",
    "ttl" => empty(CONFIG_AliDNS_TTL) ? 600 : CONFIG_AliDNS_TTL,
    "line" => empty(CONFIG_AliDNS_LINE) ? "default" : CONFIG_AliDNS_LINE
];
// 接受的参数
$_arg_key = [
    "name",
    "value",
    "update-rule"
];

$log = new Log();
// 如果是命令行执行则接受命令行传参 否则接受 GET 方法传参
if (php_sapi_name() === "cli") {
    // 处理接受的参数名称，以便接受值。
    foreach ($_arg_key as &$value) {
        $value .= ":";
    }
    unset($value);
    $_arg = getopt(null, $_arg_key); // 读取命令行参数
} else {
    if (!empty($_GET)) {
        foreach ($_arg_key as $key) { // 遍历 GET 参数
            if (!empty($_GET[$key])) {
                $_arg[$key] = $_GET[$key];
            }
        }
    }
}
if (empty($_arg) || empty($_arg["name"]) || empty($_arg["value"])) {
    // 参数不完整
    $log->send("Incomplete parameters", 2);
    _exit();
}
// 将传参更新到配置参数
foreach ($config as $key => &$value) {
    $value = empty($_arg[$key]) ? $value : $_arg[$key];
}
unset($value);
// 自动获取 IP
if ($config["value"] === "ipv4" || $config["value"] === "ipv6") {
    $log->send("Auto Get IP: " . $config["value"], 1);
    $GetIP = new GetIP($config["value"]);
    if ($GetIP->ip) {
        $config["value"] = $GetIP->ip;
    } else {
        // 自动获取 IP 失败
        $log->send("Auto Get IP failed: " . $GetIP->error_msg, 3, true);
        _exit();
    }
}
// 判断 IP 地址类型
if (filter_var($config["value"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === $config["value"]) {
    $config["type"] = "A";
    $family = "ipv4";
} elseif (filter_var($config["value"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === $config["value"]) {
    $config["type"] = "AAAA";
    $family = "ipv6";
} else {
    // IP 验证失败
    $log->send("IP format is incorrect.", 3, true);
    _exit();
}
$dest_ip = $config["value"];
$domain = sprintf("%s.%s", $config["name"], CONFIG_DOMAIN);
// 仅更新防火墙规则
if (!empty($_arg["update-rule"]) && $_arg["update-rule"] === "true") {
    Luci_RefreshRule($dest_ip, $domain, $family);
    _exit();
}
// DDNS Start
$_text = $config["value"] . " -> " . $domain;
$log->send("AliDDNS Start: $_text", 1);
$AliDDNS = new AliDDNS($config);
// DDNS 是否成功
if ($AliDDNS->OK) {
    $log->send("AliDDNS success: $_text", 1);
    Luci_RefreshRule($dest_ip, $domain, $family);
} else {
    $log->send("AliDDNS failed: $_text " . $AliDDNS->error_msg, 3, true);
}
_exit();