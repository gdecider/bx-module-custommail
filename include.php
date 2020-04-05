<?php

include __DIR__ . '/vendor/autoload.php';

function custom_mail($to, $subject, $message, $additional_headers='', $additional_parameters='')
{
    return \Local\Custommail\CustomMailer::getInstance()->send($to, $subject, $message, $additional_headers, $additional_parameters);
}
