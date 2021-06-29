<?php

declare(strict_types=1);

use AlibabaCloud\Alidns\Alidns;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException as AlibabaCloudClientException;
use LuciRpc\Client;
use LuciRpc\Helpers\Dnsmasq;
use LuciRpc\Helpers\Firewall;
use Monolog\ErrorHandler;
use Monolog\Handler\DeduplicationHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SwiftMailerHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;

#[JetBrains\PhpStorm\NoReturn]
function exitMessage(
    string $message,
    int $httpCode = 200,
    int $exitCode = 0,
    int $loglevel = null,
): void {
    global $logger;
    if ($loglevel !== null && !empty($message)) {
        $logger->log($loglevel, $message);
    }
    echo $message.PHP_EOL;
    http_response_code($httpCode);
    exit($exitCode);
}

if (!file_exists(__DIR__.'/config.php')) {
    exit(__DIR__.'/config.php not exist.');
}

require __DIR__.'/vendor/autoload.php';
require __DIR__.'/config.php';

$logger = new Logger(
    'AliDDNS',
    [
        new RotatingFileHandler(__DIR__.'/log/AliDDNS.log', 7), // 滚动日志记录
    ],
    [
        new IntrospectionProcessor(), // 记录调用的行/文件/类/方法
    ],
);
// 注册为全局错误处理程序
(new ErrorHandler($logger))->registerErrorHandler()->registerExceptionHandler()->registerFatalHandler();

if (!empty(CONFIG_ERROR_MAIL)) {
    $transport = new Swift_SmtpTransport(CONFIG_MAIL_SMTP, CONFIG_MAIL_SMTP_PORT, CONFIG_MAIL_SMTP_SSL);
    if (!empty(CONFIG_MAIL_USERNAME)) {
        $transport = $transport->setUsername(CONFIG_MAIL_USERNAME);
        if (!empty(CONFIG_MAIL_PASSWORD)) {
            $transport = $transport->setPassword(CONFIG_MAIL_PASSWORD);
        }
    }
    $mailer = new Swift_Mailer($transport);
    $message = (new Swift_Message('AliDDNS ERROR'))
        ->setFrom([CONFIG_MAIL_FROM => 'AliDDNS'])
        ->setTo(CONFIG_MAIL_TO);
    // 自动发送使用邮件发送错误信息
    $logger->pushHandler(new DeduplicationHandler(new SwiftMailerHandler($mailer, $message)));
}

if (!empty(CONFIG_DEBUG)) {
    $logger->pushHandler(new StreamHandler('php://stdout')); // 日志输出到 stdout
    error_reporting(E_ALL);
}

$params = [];
$requiredParams = ['domain', 'ip', 'name'];
$paramsKey = [
    ...$requiredParams,
    'local-ip',
    'rule-name',
];
// 如果是命令行执行则接受命令行传参 否则接受 GET/POST 方法传参
if (PHP_SAPI === 'cli') {
    $paramsKey = array_map(static fn (string $value) => "$value:", $paramsKey);
    $params = getopt('', $paramsKey); // 读取命令行参数
} else {
    if (!empty(CONFIG_SECURITY_KEY) && ($_SERVER['HTTP_X_SECURITY_KEY'] ?? '') !== CONFIG_SECURITY_KEY) {
        http_response_code(403);
        exit();
    }
    foreach ($paramsKey as $key) {
        $params[$key] = $_REQUEST[$key] ?? null;
    }
}
$params = array_map('strtolower', array_filter($params));

$missParam = array_filter($requiredParams, static fn (string $key) => !isset($params[$key]));

if (count($missParam) !== 0) {
    exitMessage('missing param: '.implode(', ', $missParam), 400, 128);
}

$domain = $params['domain'];
$ip = $params['ip'];
$subDomain = $params['name'];

$fullDomain = "$subDomain.$domain";

if (filter_var($fullDomain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== $fullDomain) {
    $logger->error("Invalid domain: '$fullDomain'");
    exitMessage('Invalid domain or name', 400, 128);
}

if ($ip === 'ipv4' || $ip === 'ipv6') {
    try {
        $params['value'] = $ip = getPublicIP($ip === 'ipv4');
    } catch (Net_DNS2_Exception $e) {
        exitMessage('Unable get public IP', 500, 1, Logger::ERROR);
    }
    $logger->info('Get public IP: '.$ip);
}

$isIPv4 = match ($ip) {
    filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) => true,
    filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) => false,
    default => null,
};

if ($isIPv4 === null) {
    $logger->error("Invalid IP: '$ip'");
    exitMessage('Invalid IP', 400, 128);
}

try {
    AlibabaCloud::accessKeyClient(CONFIG_ACCESS_KEY_ID, CONFIG_ACCESS_KEY_SECRET)
        ->debug(CONFIG_DEBUG)
        ->regionId('cn-hangzhou')
        ->timeout(5)
        ->asDefaultClient();
} catch (AlibabaCloudClientException $ex) {
    $logger->error('Alibaba Cloud client init error: '.$ex->getMessage());
    exitMessage('Alibaba Cloud client init error', 500, 1);
}

try {
    $resolveType = $isIPv4 ? 'A' : 'AAAA';
    $result = Alidns::v20150109()
        ->describeDomainRecords()
        ->withDomainName($domain)
        ->withKeyWord($subDomain)
        ->withPageSize(500)
        ->withSearchMode('EXACT')
        ->request()
        ->jsonSerialize();
    $recordId = null;
    $recordIds = [];
    $record = $result['DomainRecords']['Record'] ?? [];
    $sameRecord = false;
    foreach ($record as $item) {
        // 如果解析记录类型或线路跟当前不一致则不处理
        if ($item['Type'] !== $resolveType || $item['Line'] !== CONFIG_DNS_LINE) {
            continue;
        }
        if ($item['Value'] === $ip) {
            $recordId = $item['RecordId'];
            $sameRecord = $item['TTL'] === CONFIG_DNS_TTL;
        } else {
            $recordIds[] = $item['RecordId'];
        }
    }
    $recordId ??= array_shift($recordIds); // 如果没有值一致的记录，就取一个值不一致的记录 ID。
    // 删除其他值不一致的记录
    foreach ($recordIds as $value) {
        try {
            Alidns::v20150109()->DeleteDomainRecord()->withRecordId($value)->request();
        } catch (Exception $ex) {
            $logger->warning('An error occurred while deleting old record: '.$ex->getMessage());
        }
    }
    if ($sameRecord) {
        $logger->info('Skip add / update record because there is a consistent record');
    } else {
        $action = $recordId === null
            ? Alidns::v20150109()->addDomainRecord()->withDomainName($domain)->withLine(CONFIG_DNS_LINE)
            : Alidns::v20150109()->updateDomainRecord()->withRecordId($recordId);
        $action->withRR($subDomain)
            ->withTTL(CONFIG_DNS_TTL)
            ->withType($resolveType)
            ->withValue($ip)
            ->request();
    }
} catch (Exception $ex) {
    $logger->error('AliDDNS resolve failed: '.$ex->getMessage());
    exitMessage('AliDDNS resolve failed', 500, 1);
}

$needLuci = false;
$needLuciArgs = ['local-ip', 'rule-name'];
foreach ($needLuciArgs as $arg) {
    if (isset($params[$arg])) {
        $needLuci = true;
    }
}
if (!$needLuci) {
    exit();
}

if (empty(CONFIG_LUCI_RPC_URL) || empty(CONFIG_LUCI_USERNAME) || empty(CONFIG_LUCI_PASSWORD)) {
    exitMessage('Luci RPC is required, but there is no configuration.', 500, 1, Logger::WARNING);
}

try {
    $luciRpc = new Client(CONFIG_LUCI_RPC_URL, CONFIG_LUCI_USERNAME, CONFIG_LUCI_PASSWORD);
} catch (Exception $ex) {
    exitMessage($ex->getMessage(), 500, 1, Logger::ERROR);
}

$localIP = $params['local-ip'] ?? null;
if ($localIP) {
    $dnsmasq = new Dnsmasq($luciRpc);
    try {
        $dnsmasq->domainToAddress($fullDomain, $ip);
    } catch (Throwable $ex) {
        $logger->error($ex->getMessage());
        exitMessage('Failed to resolve to dnsmasq', 500, 1, Logger::ERROR);
    }
}

$ruleName = $params['rule-name'] ?? null;
if ($ruleName) {
    if (!file_exists(__DIR__.'/firewall_rules.json')) {
        exitMessage('Firewall rules file not found', 500, 1, Logger::WARNING);
    }
    try {
        $firewallRules = json_decode(file_get_contents(__DIR__.'/firewall_rules.json'), true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $ex) {
        $logger->error('Firewall rules file read error: '.$ex->getMessage());
        exitMessage('Firewall rules file read error', 500, 1);
    }
    $firewallRule = $firewallRules[$ruleName] ?? null;
    if (!is_array($firewallRule)) {
        exitMessage("Cannot find firewall rules: $ruleName", 500, 1, Logger::WARNING);
    }
    try {
        checkFirewallRule($firewallRule);
    } catch (InvalidArgumentException $ex) {
        $logger->error('Invalid firewall rules: '.$ex->getMessage());
        exitMessage("Invalid firewall rules: $ruleName", 500, 1);
    }
    unset($firewallRule['dest_port']);
    $firewallRule['rules'] ??= [];
    // 为每个规则设置 dest_ip 和 family
    $firewallRule['rules'] = array_map(
        static fn (array $item) => array_merge($item, ['dest_ip' => $ip, 'family' => $isIPv4 ? 'ipv4' : 'ipv6']),
        $firewallRule['rules'],
    );

    $firewall = new Firewall($luciRpc);
    try {
        $firewall->delRules($ruleName);
        $firewall->addRules($ruleName, $firewallRule);
    } catch (Throwable $ex) {
        $logger->error($ex->getMessage());
        exitMessage('Failed to add rules to firewall', 500, 1, Logger::ERROR);
    }
}
