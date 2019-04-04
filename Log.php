<?php

namespace AliDDNS;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Log
{
    /**
     * @param string $log 日志信息
     * @param int $type 日志类型：1 = 信息、2 = 警告、3 = 错误
     * @param bool $email 是否使用电子邮件发送
     */
    function send(string $log, int $type, bool $email = false)
    {
        $debug_info = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $log = sprintf("[%s:%s] %s", $debug_info[0]["file"], $debug_info[0]["line"], $log);
        switch ($type) {
            case 1:
                $log_pre = "Info";
                break;
            case 2:
                $log_pre = "Warning";
                break;
            case 3:
                $log_pre = "Error";
                break;
            default:
                $log_pre = "Unknown";
        }
        $config_timezone = empty(CONFIG_LOG_TIMEZONE) ? "UTC" : CONFIG_LOG_TIMEZONE;
        $default_timezone = date_default_timezone_get(); // 获取默认时区
        date_default_timezone_set($config_timezone); // 设置日志时间时区
        $time = date("Y-m-d H:i:s");;
        $s_log = sprintf("[%s] - %s\t%s" . PHP_EOL, $log_pre, $time, $log);
        // 输出日志
        if (CONFIG_DEBUG) {
            print $log . PHP_EOL;
        }
        // 保存日志
        if (CONFIG_LOG_SAVE) {
            $this->Save($s_log);
        }
        // 电子邮件发送日志
        if (CONFIG_LOG_EAMIL && $email) {
            $this->Email($s_log);
        }
        date_default_timezone_set($default_timezone); // 还愿默认时区
    }

    /**
     * 保存日志到文件
     * @param string $log 日志信息
     */
    private function Save(string $log)
    {
        $log_filename = __DIR__ . "/AliDDNS.log";
        file_put_contents($log_filename, $log, FILE_APPEND);
    }

    /**
     * 用电子邮件发送日志信息
     * @param string $log 日志类型
     */
    private function Email(string $log)
    {
        if (empty(CONFIG_EMAIL_SMTP) || empty(CONFIG_EMAIL_SMTP_PORT)) {
            $this->send("Mail STMP server configuration error.", 2);
            return;
        }
        if (empty(CONFIG_EMAIL_ADDRESSEE) || empty(CONFIG_EMAIL_SENDER)) {
            $this->send("Mail recipient or sender is not set.", 2);
            return;
        }
        $mail = new PHPMailer(true);
        try {
            $mail->SMTPDebug = 2;
            $mail->isSMTP();
            $mail->Host = CONFIG_EMAIL_SMTP;
            $mail->Port = CONFIG_EMAIL_SMTP_PORT;
            $mail->SMTPAuth = empty(CONFIG_EMAIL_SMTP_VERIFY) ? false : true;
            if (empty(CONFIG_EMAIL_SMTP_SSL)) {
                $mail->SMTPSecure = CONFIG_EMAIL_SMTP_SSL;
            }
            if ($mail->SMTPAuth) {
                if (empty(CONFIG_EMAIL_USER) || empty(CONFIG_EMAIL_PASSWORD)) {
                    $this->send("Mail The STMP server requires authentication, but no username and password are configured.", 2);
                    return;
                } else {
                    $mail->Username = CONFIG_EMAIL_USER;
                    $mail->Password = CONFIG_EMAIL_PASSWORD;
                }
            }
            $mail->setFrom(CONFIG_EMAIL_SENDER, "AliDDNS");
            $mail->addAddress(CONFIG_EMAIL_ADDRESSEE);
            $mail->Subject = "[AliDDNS] Log message";
            $mail->AltBody = $log;
            $mail->Body = $log;
            $mail->send();
        } catch (Exception $e) {
            $this->send($e->getMessage(), 3);
        }
    }
}