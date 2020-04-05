<?php defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

$arModConf = include __DIR__ . '/../../../mod_conf.php';

$MESS[$arModConf['name'] . '_MODULE_NAME'] = 'Кастомная отправка почты';
$MESS[$arModConf['name'] . '_MODULE_DESCRIPTION'] = 'Кастомная отправка почты';
$MESS[$arModConf['name'] . '_PARTNER_NAME'] = 'decider';
$MESS[$arModConf['name'] . '_PARTNER_URI'] = 'https://github.com/gdecider';
$MESS[$arModConf['name'] . '_INSTALL_TITLE'] = 'Установка модуля "'.$MESS[$arModConf['name'] . '_MODULE_NAME'].'"';
$MESS[$arModConf['name'] . '_UNINSTALL_TITLE'] = 'Удаление модуля "'.$MESS[$arModConf['name'] . '_MODULE_NAME'].'"';
$MESS[$arModConf['name'] . '_INSTALL_ERROR_WRONG_VERSION'] = 'Версия ядра системы не соответствует требованиям модуля, обновите систему и попробуйте установить модуль еще раз';
