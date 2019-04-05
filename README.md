# aliyun-ddns-php

![](https://img.shields.io/badge/version-v3-blue.svg)

## 简介 

这是一个基于 [Aliyun OpenAPI SDK](https://github.com/aliyun/aliyun-openapi-php-sdk) 的 PHP 程序  

它能做的事就是帮你把指定 IP 更新到阿里云解析，你可以指定 IP，也可以让它自动获取 IPv4 或 IPv6。  

鉴于在 IPv6 环境下的使用以及安全性，它还支持自动配置 OpenWrt 的防火墙规则，在开放指定端口的同时保证其他 IPv6 设备的安全性。

它还可以自动帮你配置 OpenWrt 的 DNS 解析，确保能在第一时间使你局域网的解析生效。

在解析出错或 OpenWrt 防火墙规则更新出错的时候，你可以通过配置 SMTP 来让它发送邮件提醒你。

[更新日志](https://github.com/Hill-98/aliyun-ddns-php/blob/master/Changelog.md)

## 运行环境

* [PHP](https://php.net) 5.6 +
* [Composer](https://getcomposer.org) （可选）
* PHP 扩展：`hash`、`json`、`openssl`

#### Debian / Ubuntu
```
sudo apt install php
```

#### OpenWrt
```
opkg install php7-cli php7-mod-hash php7-mod-json php7-mod-openssl
opkg install zoneinfo-core zoneinfo-asia # 安装时区数据库，如果你不是使用的 Asia 时区，请安装对应的时区。
```

## 安装

如果你安装了 Composer，直接克隆本存储库到本地，使用 Composer 安装依赖。
```
git clone https://github.com/Hill-98/aliyun-ddns-php.git /opt/AliDDNS
cd /opt/AliDDNS
composer install
```

如果你没有安装 Composer，请[点击这里](https://github.com/Hill-98/aliyun-ddns-php/releases/latest/download/aliyun-ddns-php.zip)下载最新版本。

## 配置

复制`config.example.php`并重命名为`config.php`

编辑`config.php`

具体配置选项参考：[Wiki](https://github.com/Hill-98/aliyun-ddns-php/wiki/%E9%85%8D%E7%BD%AE%E9%80%89%E9%A1%B9)

## 使用

使用的方法非常简单，支持 GET 和 CLI 两种调用方式。

参数名       |必须  |可选值      |说明             |备注
------------|:---:|-----------|-----------------|---
name        |√    |           |解析域名的主机名   |
value       |√    |ipv4 / ipv6|解析域名的记录值   |如果传递 ipv4 或 ipv6 则自动获取对应 IP
update-rule |×    |true       |仅更新 OpenWrt规则|

#### 示例：

**GET:** `http://localhost/AliDDNS/index.php?name=test&value=ipv6`  
**GET:** `http://localhost/AliDDNS/index.php?name=test&value=ipv6&update-rule=true`

**CLI:** `php /opt/AliDDNS/index.php --name test --value ipv6`  
**CLI:** `php /opt/AliDDNS/index.php --name test --value ipv6 --update-rule true`

>假如`CONFIG_DOMAIN`的值是`example.com`，以上示例将把设备的 IPv6 地址解析到`test.example.com`。

具体的使用方法可以参考：[Wiki](https://github.com/Hill-98/aliyun-ddns-php/wiki/%E4%BD%BF%E7%94%A8%E6%96%B9%E6%B3%95)

---

本项目是我想要让家里设备的 IPv6 能解析到阿里云解析的域名，但是很多 Aliyun DDNS 又不支持 IPv6，而且 IPv6 的地址每次拨号或开机都会变化，如果在路由器开放所有设备的指定端口，安全性稍有不足，所以才有了本项目。

由于我没有公网的 IPv4，在 IPv4 这块某些地方可能考虑不周，如果你有任何想法和建议，尽情提交 [Issues](https://github.com/Hill-98/aliyun-ddns-php/issues)。

Developer: 小山

QQ Group: [493736074](https://jq.qq.com/?_wv=1027&k=5f7KCIY)

Telegram Group: [@mivm.cn](https://t.me/mivm_cn)

Donate: [https://www.mivm.cn/donate/](https://www.mivm.cn/donate/)