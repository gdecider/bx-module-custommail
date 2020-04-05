<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Config\Option;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;

Loc::loadMessages(__FILE__);

class local_custommail extends CModule
{
    /** @var string */
    public $MODULE_ID;

    /** @var string */
    public $MODULE_VERSION;

    /** @var string */
    public $MODULE_VERSION_DATE;

    /** @var string */
    public $MODULE_NAME;

    /** @var string */
    public $MODULE_DESCRIPTION;

    /** @var string */
    public $MODULE_GROUP_RIGHTS;

    /** @var string */
    public $PARTNER_NAME;

    /** @var string */
    public $PARTNER_URI;

    /** @var string */
    public $SHOW_SUPER_ADMIN_GROUP_RIGHTS;

    /** @var string */
    public $MODULE_NAMESPACE;

    protected $exclAdminFiles;
    protected $arModConf;

    protected $PARTNER_CODE;
    protected $MODULE_CODE;

    private $siteId;

    protected $eventHandlers = [];

    public function __construct()
    {

        $arModuleVersion = [];
        include __DIR__.'/version.php';

        $this->arModConf = include __DIR__ . '/../mod_conf.php';

        $this->exclAdminFiles = [
            '..',
            '.',
            'menu.php',
            'operation_description.php',
            'task_description.php',
        ];

        if ($this->arModConf['arEventHandlers']) {
            $this->eventHandlers = $this->arModConf['arEventHandlers'];
        }

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_ID = strtolower($this->arModConf['name']);
        $this->MODULE_NAME = Loc::getMessage($this->arModConf['name'].'_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage($this->arModConf['name'].'_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage($this->arModConf['name'].'_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage($this->arModConf['name'].'_PARTNER_URI');
        $this->MODULE_NAMESPACE = $this->arModConf['ns'];

        $this->MODULE_GROUP_RIGHTS = 'Y';
        $this->SHOW_SUPER_ADMIN_GROUP_RIGHTS = 'Y';

        $this->PARTNER_CODE = $this->getPartnerCodeByModuleID();
        $this->MODULE_CODE = $this->getModuleCodeByModuleID();

        $rsSites = CSite::GetList($by="sort", $order="desc", ['ACTIVE' => 'Y']);
        $arSite = $rsSites->Fetch();
        $this->siteId = $arSite['ID'];
    }

    /**
     * Получение актуального пути к модулю с учетом многосайтовости
     * Как вариант можно использовать более производительную функцию str_pos
     * Недостатком данного метода является возможность "ложных срабатываний".
     * В том случае если в пути встретится два раза последовательность
     * local/modules или bitrix/modules.
     *
     * @param bool $notDocumentRoot
     * @return mixed|string
     */
    protected function getPath($notDocumentRoot = false)
    {
        return  ($notDocumentRoot)
            ? preg_replace('#^(.*)\/(local|bitrix)\/modules#','/$2/modules',dirname(__DIR__))
            : dirname(__DIR__);
    }

    /**
     * Получение кода партнера из ID модуля
     * @return string
     */
    protected function getPartnerCodeByModuleID()
    {
        return explode('.', $this->MODULE_ID)[0];
    }

    /**
     * Получение кода модуля из ID модуля
     * @return string
     */
    protected function getModuleCodeByModuleID()
    {
        $parts = explode('.', $this->MODULE_ID);

        return !empty($parts[1]) ? $parts[1] : $parts[0];
    }

    /**
     * Проверка версии ядра системы
     *
     * @return bool
     */
    protected function isVersionD7()
    {
        return CheckVersion(ModuleManager::getVersion('main'), '18.00.00');
    }

    /**
     * Установка модуля
     */
    public function DoInstall() {
        global $APPLICATION;

        if (!$this->isVersionD7()) {
            $APPLICATION->ThrowException(Loc::getMessage($this->arModConf['name']."_INSTALL_ERROR_WRONG_VERSION"));
            return false;
        }

        ModuleManager::registerModule($this->MODULE_ID);

        try {
            $this->InstallDB();
            $this->InstallEvents();
            $this->InstallFiles();
            $this->InstallTasks();

            $APPLICATION->IncludeAdminFile(Loc::getMessage($this->arModConf['name'].'_INSTALL_TITLE'), $this->getPath() . "/install/step.php");

        } catch (Exception $e) {
            ModuleManager::unRegisterModule($this->MODULE_ID);
            $APPLICATION->ThrowException('Произошла ошибка при установке ');
            return false;
        }

        return true;
    }

    /**
     * Удаление модуля
     */
    public function DoUnInstall()
    {
        global $APPLICATION;

        $this->UnInstallEvents();
        $this->UnInstallFiles();
        $this->UnInstallTasks();
        $this->UnInstallDB();

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(Loc::getMessage($this->arModConf['name']."_UNINSTALL_TITLE"), $this->getPath()."/install/unstep.php");

    }

    /**
     * Работа с базой данных при установке модуля
     * @return bool
     */
    public function InstallDB()
    {
        return true;
    }

    /**
     * Работа с базой данных при удалении модуля
     * @return bool
     */
    public function UnInstallDB()
    {
        return true;
    }

    /**
     * Работа с файлами при установке модуля
     */
    public function InstallFiles()
    {
        // Копируем компоненты в папки ядра, переименовывая их по шаблону КОД_МОДУЛЯ.ИМЯ_КОМПОНЕНТА
        if (Directory::isDirectoryExists($path = $this->GetPath() . '/install/components')) {
            if ($dir = opendir($path)) {
                while(false !== ($item = readdir($dir))) {

                    $compPath = $path .'/'. $item;

                    if(in_array($item, ['.', '..']) || !is_dir($compPath)) {
                        continue;
                    }
                    $newPath = $_SERVER['DOCUMENT_ROOT'] . "/bitrix/components/" . $this->PARTNER_CODE . '/' . $this->MODULE_CODE  . '.' . $item;
                    CopyDirFiles($compPath, $newPath, true, true);
                }
                closedir($dir);
            }
        }

        // Копируем и создаем файлы с включениями административных страниц в ядро
        if (Directory::isDirectoryExists($path = $this->GetPath() . '/admin')) {
            CopyDirFiles($this->GetPath() . "/install/admin", $_SERVER['DOCUMENT_ROOT'] . "/bitrix/admin");

            if ($dir = opendir($path)) {

                while(false !== $item = readdir($dir)) {

                    $filePathRelative = $this->GetPath(true).'/admin/'.$item;
                    $filePathFull = $_SERVER["DOCUMENT_ROOT"] . $filePathRelative;

                    if (in_array($item, $this->exclAdminFiles) || !is_file($filePathFull)) {
                        continue;
                    }

                    $subName = str_replace('.','_',$this->MODULE_ID);
                    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/'.$subName.'_'.$item,
                        '<'.'? require_once($_SERVER[\'DOCUMENT_ROOT\'] . "'.$filePathRelative.'");?'.'>');
                }
                closedir($dir);
            }
        }
    }

    /**
     * Работа с файлами при удалении модуля
     * @return bool
     */
    public function UnInstallFiles()
    {

        // Удалим файлы компонентов модуля, основываясь на принцепе их именования по шаблону КОД_МОДУЛЯ.ИМЯ_КОМПОНЕНТА
        if($this->PARTNER_CODE && $this->MODULE_CODE) {

            if (Directory::isDirectoryExists($partnerPath = $_SERVER['DOCUMENT_ROOT']. '/bitrix/components/' . $this->PARTNER_CODE)) {
                if ($dir = opendir($partnerPath)) {

                    while (false !== ($item = readdir($dir))) {
                        // имя папки компонента начитается с кода нашего модуля?
                        $isModuleComponent = (0 === strpos($item, $this->MODULE_CODE . '.'));
                        $compPath = $partnerPath . '/' . $item;

                        if (!$isModuleComponent || in_array($item, ['.', '..']) || !is_dir($compPath)) {
                            continue;
                        }

                        Directory::deleteDirectory($compPath);
                    }
                }
            }
        }

        // Удалим файлы подключений административных страниц
        if (Directory::isDirectoryExists($path = $this->GetPath() . '/admin')) {
            DeleteDirFiles($_SERVER["DOCUMENT_ROOT"] . $this->getPath() . '/install/admin/', $_SERVER["DOCUMENT_ROOT"] . '/bitrix/admin');

            if ($dir = opendir($path)) {
                while (false !== $item = readdir($dir)) {
                    if (in_array($item, $this->exclAdminFiles)) {
                        continue;
                    }

                    $subName = str_replace('.','_',$this->MODULE_ID);
                    File::deleteFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/admin/'.$subName.'_'.$item);
                }
                closedir($dir);
            }
        }

        return true;

    }

    /**
     * Работа с событиями при установке модуля
     * @return bool
     */
    public function InstallEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();

        foreach ($this->eventHandlers as $handler) {
            $eventManager->registerEventHandler($handler[0], $handler[1], $this->MODULE_ID, $handler[2], $handler[3]);
        }

        return true;
    }

    /**
     * Работа с событиями при удалении модуля
     * @return bool
     */
    public function UnInstallEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();

        foreach ($this->eventHandlers as $handler) {
            $eventManager->unRegisterEventHandler($handler[0], $handler[1], $this->MODULE_ID, $handler[2], $handler[3]);
        }

        return true;
    }

    /**
     * Работа со списками задач при установке модуля
     * @return bool
     */
    public function InstallTasks()
    {
        return true;
    }

    /**
     * Работа со списками задач при удалении модуля
     * @return bool
     */
    public function UnInstallTasks()
    {
        return true;
    }
}
