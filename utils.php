<?php

declare(strict_types=1);

function checkFirewallRule(array $config): void
{
    static $keyToType = [
        'proto' => 'string',
        'dest' => 'string',
        'dest_port' => 'string',
        'src' => 'string',
        'src_ip' => 'array',
        'src_mac' => 'array',
        'src_port' => 'string',
        'target' => 'string',
        'extra' => 'string',
        'rules' => 'array',
    ];
    static $targets = [
        'DROP',
        'ACCEPT',
        'REJECT',
        'NOTRACK',
        'HELPER',
        'MARK_SET',
        'MARK_XOR',
        'DSCP',
    ];
    $checkValue = static function (string $key, $value) use ($targets): bool {
        if (empty($value)) {
            return true;
        }
        if ($key === 'dest_port' || $key === 'src_port') {
            $ports = explode('-', $value);
            foreach ($ports as $port) {
                if (!is_numeric($port)) {
                    return false;
                }
            }
            $port1 = (int) array_shift($ports);
            $port2 = array_shift($ports) ?? 65535;
            $port2 = (int) $port2;
            return $port1 > 0 && $port2 <= 65535 && $port1 < $port2;
        }
        return match ($key) {
            'src_ip' => !count(array_filter($value, static fn ($v) => filter_var($v, FILTER_VALIDATE_IP) !== $v)),
            'src_mac' => !count(array_filter($value, static fn ($v) => filter_var($v, FILTER_VALIDATE_MAC) !== $v)),
            'target' => in_array(strtoupper($value), $targets, true),
            default => true,
        };
    };
    foreach ($config as $key => $value) {
        $type = $keyToType[$key] ?? null;
        if ($type && !call_user_func("is_$type", $value)) {
            throw new InvalidArgumentException("$key is not $type");
        }
        if (!$checkValue($key, $value)) {
            throw new InvalidArgumentException("Invalid $key");
        }
    }
    $rules = $config['rules'] ?? [];
    foreach ($rules as $index => $item) {
        if (!is_array($item)) {
            throw new InvalidArgumentException("rules.$index is not array");
        }
        foreach ($item as $key => $value) {
            $type = $keyToType[$key] ?? null;
            if ($type && !call_user_func("is_$type", $value)) {
                throw new InvalidArgumentException("rules.$index.$key is not $type");
            }
            if (!$checkValue($key, $value)) {
                throw new InvalidArgumentException("Invalid rules.$index.$key");
            }
        }
    }
}

/**
 * 获取公共 IP
 *
 * @param  bool  $ipv4  是否获取 IPv4 地址
 * @return null|string
 * @throws Net_DNS2_Exception
 */
function getPublicIP(bool $ipv4 = false): ?string
{
    $queryType = $ipv4 ? 'A' : 'AAAA';
    $nameservers = $ipv4
        ? ['208.67.222.123', '208.67.220.123']
        : ['2620:119:35::35', '2620:119:53::53'];

    $resolver = new Net_DNS2_Resolver([
        'nameservers' => $nameservers,
        'dns_port' => 5353,
        'recurse' => false,
        'dnssec' => true,
    ]);
    $result = $resolver->query('myip.opendns.com', $queryType);
    foreach ($result->answer as $answer) {
        $ip = $answer->address ?? null;
        if (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP) === $ip) {
            // 如果是 IPv6，清理地址多余的 :0
            return $ipv4 ? $ip : inet_ntop(inet_pton($ip));
        }
    }

    return null;
}
