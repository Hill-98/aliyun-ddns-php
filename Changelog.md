# 更新日志

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