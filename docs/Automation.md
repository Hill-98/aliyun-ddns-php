自动运行
--
本文档介绍自动运行 AliDDNS 的方法

### OpenWrt 网关/路由器 自动触发

这种方法可以使 OpenWrt 设备在重新拨号或连接网络时，向局域网设备的 AliDDNS 发送执行请求。

创建触发事件脚本 `/etc/hotplug.d/iface/99-aliyun-ddns-php`

```shell
#!/bin/sh

device="192.168.1.222" # 局域网设备 IP
domain="example.com"
name="example"

# 如果你的网络接口名称不是 wan, 记得修改下面的接口名称。
[ "$ACTION" = "ifup" ] && [ "$INTERFACE" = "wan" ] && {
    # 延迟 60 秒执行防止一些奇奇怪怪的问题
    sleep 60
    # 如果设备的 AliDDNS 没有在 8080 端口运行，记得更改为端口。
    # 如果没有 curl, 可以使用 wget 代替。
    curl "http://${device}:8080/index.php?domain=${domain}name=${name}&value=ipv6"
}
```

当 $INTERFACE 重新连接时，此脚本便会自动向设备的 AliDDNS 发送请求。
