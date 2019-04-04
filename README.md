# aliyun-ddns-php

## 简介 

这是一个基于 [Aliyun OpenAPI SDK](https://github.com/aliyun/aliyun-openapi-php-sdk) 的 PHP 程序  

它能做的事就是帮你把指定 IP 更新到阿里云解析，你可以指定 IP，也可以让它自动获取 IPv4 或 IPv6。  

鉴于在 IPv6 环境下的使用以及安全性，它还支持自动配置 OpenWrt 的防火墙规则，在开放指定端口的同时保证其他 IPv6 设备的安全性。

它还可以自动帮你配置 OpenWrt 的 DNS 解析，确保能在第一时间使你局域网的解析生效。

在解析出错或 OpenWrt 防火墙规则更新出错的时候，你可以通过配置 SMTP 来让它发送邮件提醒你。

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
git clone https://github.com/Hill-98/aliyun-ddns-php.git AliDDNS
cd AliDDNS
composer install
```

如果你没有安装 Composer，请[点击这里](https://github.com/Hill-98/aliyun-ddns-php/releases/download/latest/AliDDNS.zip)下载最新版本。

## 配置

将`config.example.php`重命名为`config.php`

编辑`config.php`

**基本配置**
```
define("CONFIG_DOMAIN", "example.com"); // 解析操作的域名
define("CONFIG_AccessKeyID", "Example_ID"); // 阿里云 AccessKeyID，可前往控制台获取。
define("CONFIG_AccessKeySecret", "Example_KEY"); // 阿里云 AccessKeySecret，可前往控制台获取。
```

以上配置项为必改项  

**OpenWrt 自动更新**

如果你希望启用自动更新 OpenWrt 防火墙规则以及 DNS 解析，你需要编辑以下配置项：
```
define("CONFIG_LUCI_RPC_URL", "http://192.168.1.1/luci/rpc/"); // Luci RPC 调用地址
define("CONFIG_LUCI_USER", "root"); // Luci 登陆用户
define("CONFIG_LUCI_PASSWORD", "password"); // Luci 登陆密码
define("CONFIG_UPDATE_ROUTER", false); // 是否更新路由器的防火墙以及 DNS 解析
define("CONFIG_DNSMASQ_RESOLV_ADDRESS", ""); // 指定 Dnsmasq 解析地址
```

使用此项功能，你的 OpenWrt 必须安装`luci-mod-rpc`和`dnsmasq-full`软件包。

你还需要创建`firewall_rule.json`防火墙规则文件，规则文件格式见此 Wiki。

`CONFIG_DNSMASQ_RESOLV_ADDRESS`可以让你将解析的域名指定到任意 IP，比如你可以将它指定到本地的 IPv4 地址。

只有当`CONFIG_UPDATE_ROUTER`为`true`时，才会启用 OpenWrt 自动更新。

**电子邮件**

如果你希望启用电子邮件发送功能，你需要编辑以下配置项：
```
define("CONFIG_LOG_EAMIL", false); // 是否可以通过电子邮件发送日志
define("CONFIG_EMAIL_SMTP", ""); // 电子邮件 SMTP 服务器
define("CONFIG_EMAIL_SMTP_PORT", 25); // 电子邮箱 SMTP 服务器端口
define("CONFIG_EMAIL_SMTP_SSL", ""); // 电子邮箱 SMTP 服务器加密类型 可选值：ssl、tls
define("CONFIG_EMAIL_SMTP_VERIFY", true); // 电子邮箱 SMTP 服务器验证
define("CONFIG_EMAIL_USER", ""); // 电子邮件 SMTP 服务器用户名
define("CONFIG_EMAIL_PASSWORD", ""); // 电子邮箱 SMTP 服务器密码
define("CONFIG_EMAIL_SENDER", ""); // 电子邮件发件人
define("CONFIG_EMAIL_ADDRESSEE", ""); // 电子邮件收件人
``` 
只有当`CONFIG_LOG_EAMIL`为`true`时，才会启用电子邮件发送功能，默认只会在 DDNS 解析失败 或 OpenWrt 更新失败时发送电子邮件。

## 使用

使用的方法非常简单，且支持 GET 和 CLI 两种调用方式。

可用参数如下：
* **name**: 域名解析的主机名
* **value**: 域名解析的记录值 可选值:ipv4、ipv6 （自动获取指定地址）
* **update-rule**: 仅更新 OpenWrt 规则 接受值：true

#### 示例：

**GET:** `http://localhost/AliDDNS/index.php?name=example&value=ipv6`  
**GET:** `http://localhost/AliDDNS/index.php?name=example&value=ipv6&update-rule=true`

**CLI:** `php /opt/AliDDNS/index.php --name example --value ipv6`  
**CLI:** `php /opt/AliDDNS/index.php --name example --value ipv6 --update-rule true`

如果你想当 IP 地址变化时重新解析，以下方法可供参考：  
1. 使用 Linux 的 crontab 定时执行本程序
2. 如果你使用的是 OpenWrt 路由器，可以使用触发时间，当路由器重新拨号时自动执行本程序，具体方法见此 Wiki。

Docker 运行方法见此 Wiki

---

本项目是我想要让家里设备的 IPv6 能解析到阿里云解析的域名，但是很多 Aliyun DDNS 又不支持 IPv6，而且 IPv6 的地址每次拨号或开机都会变化，如果在路由器开放所有设备的指定端口，安全性稍有不足，所以才有了本项目。

由于我没有公网的 IPv4，在 IPv4 这块某些地方可能考虑不周，如果你有任何想法和建议，尽情提交 [Issues](https://github.com/Hill-98/aliyun-ddns-php/issues)。

Developer: 小山

Donate: [https://www.mivm.cn/donate/](https://www.mivm.cn/donate/)