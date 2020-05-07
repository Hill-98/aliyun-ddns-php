<?php
declare(strict_types=1);

namespace AliDDNS;

use AliDDNS\LuciRPC\Dnsmasq;
use AliDDNS\LuciRPC\Firewall;
use AliDDNS\LuciRPC\RpcClient;
use Exception;

require __DIR__ . '/vendor/autoload.php';
$firstArg = $argv[1] ?? '';
if (strtolower($firstArg) === 'version') {
    exit(VERSION . PHP_EOL);
}
if (!file_exists(__DIR__ . '/config.php')) {
    exit('config.php not exist.');
}
require __DIR__ . '/config.php';
compatibleOldConfig();
if (!empty(CONFIG_DEBUG)) {
    error_reporting(E_ERROR);
}

$lockFile = BASEDIR . __NAMESPACE__ . '.lock';
if (file_exists($lockFile)) {
    exit('AliDDNS Running' . PHP_EOL);
}
file_put_contents($lockFile, $lockFile);
register_shutdown_function('unlink', $lockFile);

Logger::init();
try {
    AliDDNS::init();
} catch (Exception $e) {
    Logger::send(Logger::ERROR, $e->getMessage());
}

// 解析记录配置
$config = [
    'name' => 'example',
    'type' => 'A',
    'value' => '127.0.0.1',
    'ttl' => empty(CONFIG_AliDNS_TTL) ? 600 : CONFIG_AliDNS_TTL,
    'line' => empty(CONFIG_AliDNS_LINE) ? 'default' : CONFIG_AliDNS_LINE
];
// 接受的参数
$argKey = [
    'name',
    'value',
    'update-rule'
];

// 如果是命令行执行则接受命令行传参 否则接受 GET 方法传参
if (PHP_SAPI === 'cli') {
    // 处理接受的参数名称，以便接受值。
    foreach ($argKey as &$value) {
        $value .= ':';
    }
    unset($value);
    $args = getopt('', $argKey); // 读取命令行参数
} else {
    // 遍历 GET 参数
    foreach ($argKey as $key) {
        $args[$key] = $_GET[$key] ?? '';
    }
}

if (empty($args) || empty($args['name']) || empty($args['value'])) {
    exit('Incomplete parameters' . PHP_EOL);
}
// 将传参更新到配置参数
foreach ($config as $key => &$value) {
    $value = empty($args[$key]) ? $value : $args[$key];
}
unset($value);

// 自动获取 IP
$ip = $config['value'];
if ($ip === 'ipv4' || $ip === 'ipv6') {
    $ipType = $ip === 'ipv4' ? IP_TYPE_V4 : IP_TYPE_V6;
    Logger::send(Logger::INFO, "Auto Get IP: $ip");
    try {
        $ip = getIP($ipType);
    } catch (Exception $e) {
        Logger::send(Logger::ERROR, "Auto Get IP failed: {$e->getMessage()}", true);
        exit();
    }
}
// 判断 IP 地址类型
if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === $ip) {
    $config['type'] = 'A';
    $family = 'ipv4';
} elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === $ip) {
    $config['type'] = 'AAAA';
    $family = 'ipv6';
} else {
    Logger::send(Logger::ERROR, 'IP format is incorrect.', true);
    exit();
}

$config['value'] = $ip;
$domain = sprintf('%s.%s', $config['name'], CONFIG_DOMAIN);
if ($args['update-rule'] ?? '' !== 'true') {
    $text = $config['value'] . ' -> ' . $domain;
    Logger::send(Logger::INFO, "AliDDNS Start: $text");
    try {
        AliDDNS::resolve($config);
        Logger::send(Logger::INFO, "AliDDNS success: $text");
    } catch (Exception $e) {
        Logger::send(Logger::ERROR, "AliDDNS failed: $text", true);
    }
}
if (CONFIG_UPDATE_DNSMASQ || CONFIG_UPDATE_FIREWALL) {
    try {
        RpcClient::init();
    } catch (Exception $e) {
        Logger::send(Logger::ERROR, "Luci RpcClient init failed: {$e->getMessage()}", true);
    }
}

if (CONFIG_UPDATE_FIREWALL) {
    Logger::send(Logger::INFO, 'Luci Refresh Rule Start.');
    try {
        Firewall::refreshRule($ip, $family);
        Logger::send(Logger::INFO, 'Luci Refresh Rule success');
    } catch (Exception $e) {
        Logger::send(Logger::ERROR, "Luci RPC failed: {$e->getMessage()}", true);
    }
}

if (CONFIG_UPDATE_DNSMASQ) {
    Logger::send(Logger::INFO, 'Dnsmasq Update Resolve Start.');
    try {
        Dnsmasq::updateResolve($domain, $ip);
        Logger::send(Logger::INFO, 'Dnsmasq Update Resolve success');
    } catch (Exception $e) {
        Logger::send(Logger::ERROR, "Dnsmasq Update Resolve failed: {$e->getMessage()}", true);
    }
}

