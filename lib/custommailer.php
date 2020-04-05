<?php

namespace Local\Custommail;

use Bitrix\Main\Config\Option;
use \PHPMailer\PHPMailer\PHPMailer;

class CustomMailer
{
    /** @var static */
    private static $instance;

    private $config;
    private $host;
    private $userName;
    private $password;
    private $smtpSecure;
    private $smtpPort;

    private function __construct()
    {
        $this->config = include __DIR__ . '/../mod_conf.php';

        $this->host = Option::get($this->config['id'], 'CUSTOM_MAIL_HOST');
        $this->userName = Option::get($this->config['id'], 'CUSTOM_MAIL_USERNAME');
        $this->password = Option::get($this->config['id'], 'CUSTOM_MAIL_PASSWORD');
        $this->smtpSecure = Option::get($this->config['id'], 'CUSTOM_MAIL_SMTP_SECURE');
        $this->smtpPort = Option::get($this->config['id'], 'CUSTOM_MAIL_SMTP_PORT');
    }

    public static function getInstance()
    {
        if (!(static::$instance instanceof static)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Обработчик события OnPageStart
     * нужен для подключения модуля и его файла include.php,
     * который содержет ф-ю custom_mail для отправки почты
     */
    public static function onPageStart()
    {
        if (constant('CUSTOM_MAILER_PAGE_STARTED'))
        {
            return;
        }

        self::defineConstants();
    }

    public static function defineConstants()
    {
        define('CUSTOM_MAILER_PAGE_STARTED', true);
    }

    public function send($to, $subject, $message, $additional_headers='', $additional_parameters='')
    {
        $mail = new PHPMailer();

        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->SMTPAuth = true;

        $mail->addAddress($to);

        $mail->Host = $this->host;
        $mail->Username = $this->userName;
        $mail->Password = $this->password;
        $mail->SMTPSecure = $this->smtpSecure;
        $mail->Port = $this->smtpPort;
        $mail->setFrom($this->userName);

        $arRows = preg_split("/((\r?\n)|(\r\n?))/", $additional_headers);
        foreach ($arRows as $header) {
            $mail->addCustomHeader($header);
        }

        $mail->ContentType = $mail::CONTENT_TYPE_MULTIPART_ALTERNATIVE;
        $mail->Subject = $subject;
        $mail->Body = $message;

        if (!$mail->send()) {
            return false;
        }

        return true;
    }
}