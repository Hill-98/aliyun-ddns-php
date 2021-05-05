<?php
const CONFIG_DOMAIN = 'example.com'; // 解析操作的域名
const CONFIG_AccessKeyID = 'Example_ID'; // 阿里云 AccessKeyID，可前往控制台获取。
const CONFIG_AccessKeySecret = 'Example_KEY'; // 阿里云 AccessKeySecret，可前往控制台获取。

const CONFIG_AliDNS_TTL = 600; // 解析生效时间 可选值：https://help.aliyun.com/document_detail/29806.html
const CONFIG_AliDNS_LINE = 'default'; // 解析线路 可选值：https://help.aliyun.com/document_detail/29807.html

const CONFIG_LUCI_RPC_URL = 'http://192.168.1.1/cgi-bin/luci/rpc/'; // Luci RPC 调用地址
const CONFIG_LUCI_USER = 'root'; // Luci 登陆用户名
const CONFIG_LUCI_PASSWORD = 'password'; // Luci 登陆密码
const CONFIG_DNS_RESOLVE_ADDRESS = ''; // OpenWrt DNS 解析 IP 地址
const CONFIG_UPDATE_FIREWALL = false; // 是否自动更新 OpenWrt 防火墙
const CONFIG_UPDATE_DNSMASQ = false; // 是否自动更新 OpenWrt DNS 解析

// 电子邮件设置
const CONFIG_EMAIL_SMTP = ''; // SMTP 服务器地址
const CONFIG_EMAIL_SMTP_PORT = 25; // SMTP 服务器端口
const CONFIG_EMAIL_SMTP_SSL = ''; // SMTP 服务器加密类型，留空则不加密。 可选值：ssl、tls
const CONFIG_EMAIL_SMTP_VERIFY = true; // SMTP 服务器验证
const CONFIG_EMAIL_USER = ''; // SMTP 服务器登陆用户名
const CONFIG_EMAIL_PASSWORD = ''; // SMTP 服务器登陆密码
const CONFIG_EMAIL_SENDER = ''; // 电子邮件发件人
const CONFIG_EMAIL_ADDRESSEE = ''; // 电子邮件收件人

const CONFIG_DEBUG = false; // 调试模式 输出所有异常信息

const CONFIG_LOG_SAVE = true; // 是否保存日志
const CONFIG_LOG_EMAIL = false; // 是否通过电子邮件发送日志
const CONFIG_LOG_TIMEZONE = 'Asia/Shanghai'; // 日志记录时间时区 可选值：https://www.php.net/manual/timezones.php

