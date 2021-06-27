<?php

declare(strict_types=1);

namespace LuciRpc\Helpers;

use GuzzleHttp\Exception\GuzzleException;
use LuciRpc\Exceptions\RpcRequestException;

class Firewall extends Helper
{
    /** @var array 防火墙规则键名 */
    private const UCI_KEY = [
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
        'extra',
    ];

    /** @var array 防火墙规则默认值 */
    private const DEFAULT_VALUE = [
        'dest' => 'lan',
        'src' => 'wan',
        'target' => 'ACCEPT',
    ];

    /**
     * @param  string  $name
     * @param  array   $config
     * @throws GuzzleException
     * @throws RpcRequestException
     */
    public function addRules(string $name, array $config): void
    {
        $rules = array_map(fn (array $item) => $this->getRuleConfig($item, $config), $config['rules']);
        unset($config['rules']);
        $namePrefix = $this->getNamePrefix($name);
        foreach ($rules as $n => $rule) {
            $section = $this->client->request('uci', 'add', ['firewall', 'rule']);
            $rule['name'] = "$namePrefix $n";
            foreach ($rule as $key => $value) {
                $this->client->request('uci', 'set', ['firewall', $section, $key, $value]);
            }
        }
        $this->commit();
    }

    /**
     * uci commit
     *
     * @throws GuzzleException
     * @throws RpcRequestException
     */
    private function commit(): void
    {
        $this->client->request('uci', 'commit', ['firewall']);
    }

    /**
     * 删除指定名称的防火墙规则集
     *
     * @param  string  $namePrefix
     * @throws GuzzleException
     * @throws RpcRequestException
     */
    public function delRules(string $namePrefix): void
    {
        $namePrefix = $this->getNamePrefix($namePrefix);
        $rules = array_filter(
            $this->client->request('uci', 'get_all', ['firewall']),
            static fn (array $item) => $item['.type'] === 'rule',
        );
        foreach ($rules as $section => $rule) {
            $n = $rule['name'] ?? '';
            if (str_starts_with($n, $namePrefix)) {
                $this->client->request('uci', 'delete', ['firewall', $section]);
            }
        }
        $this->commit();
    }

    private function getRuleConfig(array $config, array $defaults = []): array
    {
        $result = [];
        $defaults = array_merge(static::DEFAULT_VALUE, $defaults);
        foreach (static::UCI_KEY as $key) {
            $result[$key] = empty($config[$key]) ? $defaults[$key] ?? null : $config[$key];
        }
        return array_filter($result);
    }

    private function getNamePrefix(string $name): string
    {
        return "[$name]";
    }
}
