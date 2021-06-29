防火墙规则文件
--
本文档介绍 AliDDNS 的防火墙规则文件 `firewall_rules.json` 的配置和用法

防火墙规则文件示例：

```json
{
    "example": {
        "proto": "tcp",
        "dest": "lan",
        "src": "wan",
        "src_ip": [],
        "src_mac": [],
        "src_port": "",
        "target": "ACCEPT",
        "extra": "",
        "rules": {
            "HTTP": {
                "proto": "tcp",
                "dest": "lan",
                "dest_port": "80",
                "src": "wan",
                "src_ip": [],
                "src_mac": [],
                "src_port": "",
                "target": "ACCEPT",
                "extra": ""
            }
        }
    }
}
```

防火墙规则文件精简版:

```json
{
    "example": {
        "rules": {
            "HTTP": {
                "dest_port": "80"
            }
        }
    }
}
```

上面大部分字段你都可以在 [官方文档](https://openwrt.org/docs/guide-user/firewall/firewall_configuration#rules) 找到说明

`example` 属性定义了一个规则集，规则集目前可接受的属性如完整版所示，规则集下除了 `rules` 属性，其余属性为规则的默认值。这些属性都可以省略，其中 `dest`, `src`, `target`
有默认值，分别是: `lan`, `wan`, `ACCEPT`.

`rules` 属性定义了规则集的规则，这些规则可以接受 `dest_port` 属性，这个属性不允许有默认值，当然也可以不设置，不设置的情况下，这个规则将允许所有端口。

这些规则到最后会创建为 OpenWrt 防火墙的通信规则，并且目标 IP 会被设置为解析的 IP。

### 如何使用？

只需要向 AliDDNS 的 `rule-name` 参数传入规则集名称，规则集必须存在于 `firewall_rules.json`。

### 防火墙规则有什么用？

如果你是 IPv4 公网用户，防火墙规则可能没什么用，因为 IPv4 公网一般使用端口转发，并且一个局域网也只有一个公网 IP。

但是对于 IPv6 公网用户来说，端口转发不合适，也不太适用。所以通信规则是 IPv6 允许外部访问的方法之一。但是如果创建了个通用的通信规则，比如允许 IPv6 协议 80 端口访问，不设置目标 IP 的情况下，局域网设备的所有 80
端口都会暴露，这是不安全的。

AliDDNS 可以帮助你自动设置防火墙规则并设置目标 IP，使你在开放指定设备端口的同时，无需担心其他设备的安全性。
