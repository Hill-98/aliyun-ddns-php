<?php
if (file_exists(RUNNING_DIR . "/config.example.php")) {
    $config_value = get_defined_constants(true)["user"];
    $config_old_name = [
        "CONFIG_DNS_RESOLVE_ADDRESS" => [
            "CONFIG_DNSMASQ_RESOLV_ADDRESS"
        ]
    ];
    if (preg_match_all('/(?<=")CONFIG_.*?(?=")/', file_get_contents(RUNNING_DIR . "/config.example.php"), $config_curr_name) !== 0) {
        foreach ($config_curr_name[0] as $CONFIG) {
            if (!isset($config_value[$CONFIG]) && !empty($config_old_name[$CONFIG])) {
                foreach ($config_old_name[$CONFIG] as $OLD_CONFIG) {
                    if (isset($config_value[$OLD_CONFIG])) {
                        define($CONFIG, $config_value[$OLD_CONFIG]);
                        break;
                    }
                }
            }
        }
    }
}

