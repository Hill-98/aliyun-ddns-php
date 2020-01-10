# 更新日志

### v12
* 修复获取 IP 地址超时
* 优化获取 IP 地址逻辑

### V11
* 修复获取 IP 地址 API

### v10
* 重构代码
* 分离 `CONFIG_UPDATE_ROUTER` 选项为 `CONFIG_UPDATE_FIREWALL` 和 `CONFIG_UPDATE_DNSMASQ`，现在可以单独控制防火墙规则和 Dnsmasq 解析的自动更新了。

### v9
* 更换获取 IP 的 API [http://ip.ss.zxinc.org/](http://ip.ss.zxinc.org/)
* 修复 BUG

### v7
* 几乎重写了逻辑
* 日志系统使用`Monolog`实现（如果你使用 git 安装，请重新运行`composer update`）

### v6
* 优化自动加载
* 优化兼容旧版本配置文件

### v5
* 修复自动更新 OpenWrt DNS 解析无效。

### v4
* 优化运行逻辑
* 配置项名称变更：`CONFIG_DNSMASQ_RESOLV_ADDRESS`变更为`CONFIG_DNS_RESOLVE_ADDRESS`
* 添加自动兼容旧版本配置文件功能
> 推荐手动更改 config.php 对应配置项

### v3
* 修正类的自动加载

### v2
* `index.php`新增`version`CLI 参数，可查看版本号。
* 添加防止被重复运行机制
* 使用类自动加载

### v1:
* 初始提交
