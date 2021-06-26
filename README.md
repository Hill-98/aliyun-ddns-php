# aliyun-ddns-php

<a href="https://github.com/Hill-98/aliyun-ddns-php/blob/master/LICENSE"><img alt="MIT" src="https://img.shields.io/github/license/Hill-98/aliyun-ddns-php"></a>
<a href="https://packagist.org/packages/hill-98/aliyun-ddns-php"><img alt="PHP Version" src="https://img.shields.io/packagist/php-v/hill-98/aliyun-ddns-php"></a>
<a href="https://github.com/Hill-98/aliyun-ddns-php/releases/latest"><img alt="Github Releases" src="https://img.shields.io/github/v/release/Hill-98/aliyun-ddns-php"></a>
<a href="https://github.com/Hill-98/aliyun-ddns-php/releases"><img alt="Github Releases Download" src="https://img.shields.io/github/downloads/Hill-98/aliyun-ddns-php/total"></a>


[更新日志](https://github.com/Hill-98/aliyun-ddns-php/blob/master/Changelog.md)

如果你在寻找 1.0.0 版本之前的文档，请访问 [Wiki](https://github.com/Hill-98/aliyun-ddns-php/wiki) 。

## 简介

它不只是 DDNS

它可以通过路由器/网关的 dnsmasq 将域名解析到本地 IP，实现本地 0 延迟响应解析。

鉴于安全性，它支持自动设置路由器/网关的防火墙规则，特别适合 IPv6 环境使用。

如果担心执行时发生错误，它支持通过电子邮件发送错误信息。

## 安装

运行需求： [PHP](https://php.net) 8.0+

[点击这里](https://github.com/Hill-98/aliyun-ddns-php/releases/latest/download/aliyun-ddns-php.zip) 下载最新版本

## 配置

复制`config.example.php`到`config.php`

编辑`config.php`

如需用路由器/网关功能，路由器必须支持 Luci RPC，且必须正确设置 `CONFIG_LUCI_RPC_URL`, `CONFIG_LUCI_USERNAME` 和 `CONFIG_LUCI_PASSWORD`

如需使用电子邮件发送错误，必须正确设置电子邮件配置项，且 `CONFIG_ERROR_MAIL` 为 `true`

## 使用

支持 GET (POST) 和 CLI 方式运行

自动执行：[文档](https://github.com/Hill-98/aliyun-ddns-php/blob/master/docs/Automation.md)

参数名       | 必要 |        说明      |     备注
------------|:---:|------------------|---------------
domain      |  √  | 域名             | 必须存在于你的 DNS 云解析
ip          |  √  | 解析记录的 IP     | 如果是 `ipv4` 或 `ipv6` 会自动获取对应公网 IP
name        |  √  | 解析记录的主机记录 |
local-ip    |  ×  | 本地 IP          | 使用路由器/网关的 dnsmasq 把解析域名指向本地 IP
rule-name   |  ×  | 防火墙规则名称    | 自动更新路由器/网关的防火墙规则，防火墙规则配置详见 [文档](https://github.com/Hill-98/aliyun-ddns-php/blob/master/docs/FirewallRule.md)

执行成功：HTTP 响应代码为 `200` 或 CLI 退出代码为 `0` 

#### 示例：

自动获取公网 IPv6 地址并解析到 test.example.com

**GET:** `http://aliddns.localhost/index.php?domain=example.com&name=test&ip=ipv6`

**CLI:** `php /opt/AliDDNS/index.php --domain example.com --name test --ip ipv6`



## 贡献

欢迎 [Fork](https://github.com/Hill-98/aliyun-ddns-php/fork) 本项目并提交 [PR](https://github.com/Hill-98/aliyun-ddns-php/pulls) 。
