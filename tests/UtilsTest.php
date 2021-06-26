<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{
    private function assertCheckFirewallRuleError(array $config, string $exMessage): void
    {
        try {
            checkFirewallRule($config);
            $this->assertTrue(false);
        } catch (\InvalidArgumentException $ex) {
            $this->assertEquals($exMessage, $ex->getMessage());
        }
    }

    /**
     * 测试获取公网 IP
     *
     * @throws \Net_DNS2_Exception
     */
    public function testGetPublicIP(): void
    {
        $ip = getPublicIP(true);
        $this->assertNotNull($ip);
        $ip = getPublicIP(false);
        $this->assertNotNull($ip);
    }

    /**
     * 测试检查防火墙规则
     */
    public function testCheckFirewallRule(): void
    {
        $this->assertCheckFirewallRuleError(['proto' => 1], 'proto not string');
        $this->assertCheckFirewallRuleError(['dest' => 1], 'dest not string');
        $this->assertCheckFirewallRuleError(['dest_port' => 1], 'dest_port not string');
        $this->assertCheckFirewallRuleError(['src' => 1], 'src not string');
        $this->assertCheckFirewallRuleError(['src_ip' => 1], 'src_ip not array');
        $this->assertCheckFirewallRuleError(['src_mac' => 1], 'src_mac not array');
        $this->assertCheckFirewallRuleError(['src_port' => 1], 'src_port not string');
        $this->assertCheckFirewallRuleError(['target' => 1], 'target not string');
        $this->assertCheckFirewallRuleError(['extra' => 1], 'extra not string');
        $this->assertCheckFirewallRuleError(['rules' => 1], 'rules not array');
        // 端口格式
        $this->assertCheckFirewallRuleError(['dest_port' => '65536'], 'Invalid dest_port');
        $this->assertCheckFirewallRuleError(['dest_port' => '0-1'], 'Invalid dest_port');
        $this->assertCheckFirewallRuleError(['dest_port' => '1-65536'], 'Invalid dest_port');
        $this->assertCheckFirewallRuleError(['dest_port' => '2-1'], 'Invalid dest_port');
        $this->assertCheckFirewallRuleError(['src_port' => '65536'], 'Invalid src_port');
        $this->assertCheckFirewallRuleError(['src_port' => '0-1'], 'Invalid src_port');
        $this->assertCheckFirewallRuleError(['src_port' => '1-65536'], 'Invalid src_port');
        $this->assertCheckFirewallRuleError(['src_port' => '2-1'], 'Invalid src_port');

        $this->assertCheckFirewallRuleError(['src_ip' => ['X']], 'Invalid src_ip');

        $this->assertCheckFirewallRuleError(['src_mac' => ['X']], 'Invalid src_mac');

        $this->assertCheckFirewallRuleError(['target' => 'X'], 'Invalid target');

        $this->assertCheckFirewallRuleError(['rules' => ['test' => '']], 'rules.test not array');

        $this->assertCheckFirewallRuleError(['rules' => ['test' => ['name' => 'x', 'src_port' => 1]]], 'rules.test.src_port not string');

        $config = [
            'proto' => 'tcp',
            'dest' => 'lan',
            'src' => 'wan',
            'src_ip' => ['127.0.0.1', '::1'],
            'src_mac' => ['ff:ff:ff:ff:ff:ff'],
            'src_port' => '80-443',
            'target' => 'ACCEPT',
            'extra' => '-',
        ];
        $config['rules'] = [];
        $config['rules']['test'] = array_merge($config, ['dest_port' => '555']);
        checkFirewallRule($config);
        $this->assertTrue(true);
    }
}
