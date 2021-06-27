<?php

const CONFIG_SECURITY_KEY = ''; // 安全密钥，如果不为空，HTTP 请求时需在 HTTP 头 X-SECURITY-KEY 设置密钥。

const CONFIG_ACCESS_KEY_ID = 'AccessKeyID'; // 阿里云 AccessKeyID，可前往控制台获取。
const CONFIG_ACCESS_KEY_SECRET = 'AccessKeySecret'; // 阿里云 AccessKeySecret，可前往控制台获取。

const CONFIG_DNS_TTL = 600; // DNS 解析生效时间 可选值：https://help.aliyun.com/document_detail/29806.html
const CONFIG_DNS_LINE = 'default'; // DNS 解析线路 可选值：https://help.aliyun.com/document_detail/29807.html

const CONFIG_LUCI_RPC_URL = 'http://192.168.1.1/cgi-bin/luci/rpc'; // Luci RPC 调用地址
const CONFIG_LUCI_USERNAME = 'root'; // Luci 登陆用户名
const CONFIG_LUCI_PASSWORD = 'password'; // Luci 登陆密码

// 电子邮件设置
const CONFIG_MAIL_SMTP = ''; // SMTP 服务器地址
const CONFIG_MAIL_SMTP_PORT = 25; // SMTP 服务器端口
const CONFIG_MAIL_SMTP_SSL = null; // SMTP 服务器加密类型，留空则不加密。可选值：ssl、tls
const CONFIG_MAIL_USERNAME = ''; // SMTP 服务器登陆用户名 留空则不使用 SMTP 验证
const CONFIG_MAIL_PASSWORD = ''; // SMTP 服务器登陆密码
const CONFIG_MAIL_FROM = ''; // 电子邮件发件人
const CONFIG_MAIL_TO = ''; // 电子邮件收件人
const CONFIG_ERROR_MAIL = false; // 是否使用电子邮件发送错误日志

const CONFIG_DEBUG = false; // 调试模式 输出所有异常信息
