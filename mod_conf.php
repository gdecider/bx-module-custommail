<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$baseDir = basename(__DIR__);
$moduleName = strtoupper($baseDir);
$baseNS = 'Local';
$parts = explode('.', $baseDir);
$moduleNS = $baseNS . '\\' . ucfirst($parts[1]);

/**
 * @var array $arEventHandlers
 * список обработчиков событий
 * Элемент массива - массив элементов ["модуль", "событие", "класс обработчика", "метод обработчика"]
 */
$arEventHandlers = [
    [
        'main',
        'OnPageStart',
        $moduleNS . '\CustomMailer',
        'onPageStart',
    ]
];

$arConfig = [
    'id' => strtolower($moduleName),
    'name' => $moduleName,
    'ns' => $moduleNS,
    'arEventHandlers' => $arEventHandlers,
];

return $arConfig;
