<?php

namespace AliDDNS;

use Monolog\Handler\StreamHandler;
use Monolog\Processor\IntrospectionProcessor;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Logger extends \Monolog\Logger
{
    /**
     * Logger constructor.
     */
    public function __construct()
    {
        $name = __NAMESPACE__;
        $handlers = [];
        $processors = [
            new IntrospectionProcessor()
        ];
        if (CONFIG_LOG_SAVE) {
            try {
                $handlers[] = new StreamHandler(RUNNING_DIR . "/{$name}.log");
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }
        parent::__construct($name, $handlers, $processors);
    }

    /**
     * @param int $level MonoLog 日志等级
     * @param string $message 日志信息
     * @param bool $email 是否使用电子邮件发送
     */
    public function send(int $level, string $message, bool $email = false)
    {
        $config_timezone = empty(CONFIG_LOG_TIMEZONE) ? "UTC" : CONFIG_LOG_TIMEZONE;
        $default_timezone = date_default_timezone_get(); // 获取默认时区
        date_default_timezone_set($config_timezone); // 设置日志时间时区
        parent::log($level, $message);
        // 输出日志
        if (CONFIG_DEBUG) echo $message . PHP_EOL;
        // 电子邮件发送日志
        if (CONFIG_LOG_EMAIL && $email) {
            $this->Email($message);
        }
        date_default_timezone_set($default_timezone); // 还愿默认时区
    }

    /**
     * 用电子邮件发送日志信息
     * @param string $log 日志类型
     */
    private function Email(string $log)
    {
        if (empty(CONFIG_EMAIL_SMTP) || empty(CONFIG_EMAIL_SMTP_PORT)) {
            $this->send(parent::WARNING, "Mail STMP server configuration error.");
            return;
        }
        if (empty(CONFIG_EMAIL_ADDRESSEE) || empty(CONFIG_EMAIL_SENDER)) {
            $this->send(parent::WARNING, "Mail recipient or sender is not set.");
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
                    $this->send(parent::WARNING, "Mail The STMP server requires authentication, but no username and password are configured.");
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
            $this->send(parent::ERROR, $e->getMessage());
        }
    }
}