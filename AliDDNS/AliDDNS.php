<?php

namespace AliDDNS;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Result\Result;
use Exception;
use RuntimeException;

class AliDDNS
{
    /**
     * @throws RuntimeException
     */
    public static function init(): void
    {
        // 初始化 AliYun OpenAPI 客户端
        try {
            AlibabaCloud::accessKeyClient(CONFIG_AccessKeyID, CONFIG_AccessKeySecret)
                ->regionId('cn-hangzhou')
                ->asDefaultClient();
        } catch (Exception $e) {
            Logger::send(Logger::ERROR, $e->getMessage());
            throw new RuntimeException('Initialization Aliyun OpenAPI Client failed.');
        }
    }

    /**
     * AliDNS 动态解析
     * @param array $config 解析配置参数
     * @throws Exception
     */
    public static function resolve(array $config): void
    {
        // 查询域名解析列表参数数组
        $options = [
            'DomainName' => CONFIG_DOMAIN,
            'PageSize' => 500,
            'RRKeyWord' => $config['name'],
            'TypeKeyWord' => $config['type']
        ];
        // 查询域名解析列表
        $result = self::AliDNS_RPC('DescribeDomainRecords', $options);
        if (!$result) {
            throw new RuntimeException('Domain resolution list query returns null value.');
        }
        $RecordId = 0;
        // 遍历域名解析列表，判断当前解析是否存在。
        foreach ($result['DomainRecords']['Record'] as $item) {
            // 当 主机名称、解析类型 一致 且 记录值不一致时，才视为存在。
            if (strtolower($item['RR']) === strtolower($config['name']) && strtolower($item['Type']) === strtolower($config['type'])) {
                if (strtolower($item['Value']) === strtolower($config['value'])) {
                    // 当 主机名称、解析类型和记录值一致时，直接返回。
                    return;
                }
                $RecordId = $item['RecordId'];
                // 这里不跳出是有可能存在多个名称和类型相同，但值不相同的解析记录。
            }
        }
        // 添加/修改域名解析参数数组
        $options = [
            'DomainName' => CONFIG_DOMAIN,
            'RecordId' => $RecordId,
            'RR' => $config['name'],
            'Type' => $config['type'],
            'Value' => $config['value'],
            'TTL' => $config['ttl'],
            'Line' => $config['line']
        ];
        // 根据当前解析是否存在来决定是添加还是修改，以及删除对应的无效参数。
        if ($RecordId === 0) {
            unset($options['RecordId']);
            $action = 'AddDomainRecord';
        } else {
            unset($options['DomainName']);
            $action = 'UpdateDomainRecord';
        }
        if (!self::AliDNS_RPC($action, $options)) {
            throw new RuntimeException("$action action failed.");
        }
    }

    /**
     * AliDNS RPC 调用函数
     * @param string $action 请求方法
     * @param array $options 请求参数
     * @return Result|bool
     */
    private static function AliDNS_RPC(string $action, array $options)
    {
        $result = false;
        try {
            $result = AlibabaCloud::rpc()
                ->product('Alidns')
                ->version('2015-01-09')
                ->action($action)
                ->method('POST')
                ->options(['query' => $options])
                ->request();
        } catch (Exception $e) {
            Logger::send(Logger::ERROR, $e->getMessage());
        }
        return $result;
    }
}
