<?php
define("CONFIG_DOMAIN", "example.com"); // 解析操作的域名
define("CONFIG_AccessKeyID", "Example_ID"); // 阿里云 AccessKeyID，可前往控制台获取。
define("CONFIG_AccessKeySecret", "Example_KEY"); // 阿里云 AccessKeySecret，可前往控制台获取。

define("CONFIG_AliDNS_TTL", 600); // 解析生效时间 可选值：https://help.aliyun.com/document_detail/29806.html
define("CONFIG_AliDNS_LINE", "default"); // 解析线路 可选值：https://help.aliyun.com/document_detail/29807.html

define("CONFIG_LUCI_RPC_URL", "http://192.168.1.1/luci/rpc/"); // Luci RPC 调用地址
define("CONFIG_LUCI_USER", "root"); // Luci 登陆用户名
define("CONFIG_LUCI_PASSWORD", "password"); // Luci 登陆密码

define("CONFIG_DNSMASQ_RESOLV_ADDRESS", ""); // 指定 Dnsmasq 解析 IP 地址

define("CONFIG_DEBUG", false); // 调试模式 输出所有异常信息
define("CONFIG_UPDATE_ROUTER", false); // 是否启用自动更新 OpenWrt 的防火墙以及 DNS 解析

define("CONFIG_LOG_SAVE", true); // 是否启用保存日志
define("CONFIG_LOG_EMAIL", false); // 是否启用通过电子邮件发送日志
define("CONFIG_LOG_TIMEZONE", "Asia/Shanghai"); // 日志记录时间默认时区 可选值：https://www.php.net/manual/timezones.php

define("CONFIG_EMAIL_SMTP", ""); // 电子邮件 SMTP 服务器地址
define("CONFIG_EMAIL_SMTP_PORT", 25); // 电子邮箱 SMTP 服务器端口
define("CONFIG_EMAIL_SMTP_SSL", ""); // 电子邮箱 SMTP 服务器加密类型 可选值：ssl、tls
define("CONFIG_EMAIL_SMTP_VERIFY", true); // 电子邮箱 SMTP 服务器验证
define("CONFIG_EMAIL_USER", ""); // 电子邮件 SMTP 服务器登陆用户名
define("CONFIG_EMAIL_PASSWORD", ""); // 电子邮箱 SMTP 服务器登陆密码
define("CONFIG_EMAIL_SENDER", ""); // 电子邮件发件人
define("CONFIG_EMAIL_ADDRESSEE", ""); // 电子邮件收件人