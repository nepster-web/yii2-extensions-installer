<?php

namespace nepster\modules\installer;

use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\helpers\Console;
use yii\helpers\Json;
use yii\log\Logger;
use Yii;

/**
 * Yii2 extensions installer
 */
class Installer extends \yii\console\Controller
{
    /**
     * @var array
     */
    private $_install = [];

    /**
     * Verify install
     */
    public function actionIndex()
    {
        try {
            $installFile = 'install.json';
            $path = $this->prompt('Enter path to module install file:');
            $path = Yii::getAlias($path);

            if (!file_exists($path . '/' . $installFile)) {
                throw new InvalidParamException($path . '/' . $installFile . ' file not exist');
            }

            $install = @file_get_contents($path . '/' . $installFile);
            $install = Json::decode($install);

            if (!isset($install['name'])) {
                throw new InvalidConfigException('Install config `name` not found');
            }

            if (!isset($install['type'])) {
                throw new InvalidConfigException('Install config `type` not found');
            }

            if (!isset($install['copy'])) {
                throw new InvalidConfigException('Install config `copy` not found');
            }

            if (!is_array($install['copy'])) {
                throw new InvalidConfigException('Install config `copy` must be array');
            }

            if (!isset($install['settings'])) {
                throw new InvalidConfigException('Install config `settings` not found');
            }

            if (!is_array($install['settings'])) {
                throw new InvalidConfigException('Install config `settings` must be array');
            }

            $this->_install = $install;

            $this->set();

        } catch (\Exception $e) {
            $this->stdout($e->getMessage() . PHP_EOL, Console::FG_RED);
        }
    }

    /**
     * Установка конфигурации
     */
    protected function set()
    {
        // Копирование файлов
        $copyArray = [];

        foreach ($this->_install['copy'] as $from => $to) {
            $_to = $this->prompt('Copy from ' . $from . ' to [' . $to . ']:');
            if ($_to) {
                $to = $_to;
            }
            $copyArray[Yii::getAlias($from)] = Yii::getAlias($to);
        }

        // Замена настроек
        $settingsArray = [];

        foreach ($this->_install['settings'] as $name => $setting) {
            $_setting = $this->prompt($name . ' [' . $setting . ']:');
            if (!$_setting) {
                $_setting = $setting;
            }
            $settingsArray[$name] = [
                $setting => $_setting
            ];
        }

        // Подтверждение установки
        $this->stdout(PHP_EOL . ' Confirm install ' . $this->_install['type'] . ' [' . $this->_install['name'] . ']  ' . PHP_EOL, Console::BG_BLUE);

        foreach ($copyArray as $from => $to) {
            $this->stdout(PHP_EOL . 'Copy:' . PHP_EOL);
            $this->stdout(' - from ' . $from . PHP_EOL . ' - to ' . $to);
        }

        $this->stdout(PHP_EOL);

        foreach ($settingsArray as $name => $setting) {
            $setting = $setting[key($setting)];
            $this->stdout(PHP_EOL . $name . ': ' . $setting);
        }

        // Подтверждение
        $confirm = $this->prompt(PHP_EOL . PHP_EOL . 'Confirm (yes|no)', [
            "required" => true,
            "default" => "no",
        ]);

        if (strncasecmp($confirm, "y", 1) === 0) {
            $this->stdout(PHP_EOL . '  Install ' . $this->_install['type'] . ' [' . $this->_install['name'] . ']  ' . PHP_EOL, Console::BG_GREEN);
            $this->stdout(PHP_EOL);
            $this->install($copyArray, $settingsArray);
        } else {
            $this->cancel();
        }
    }

    /**
     * Установка
     * @param $copyArray
     * @param $settingsArray
     */
    protected function install(array $copyArray, array $settingsArray)
    {
        $copyArray = array_unique($copyArray, SORT_REGULAR);
        $settingsArray = array_unique($settingsArray, SORT_REGULAR);
        foreach ($copyArray as $from => $to) {
            $this->copyFiles($from, $to, $settingsArray);
        }
    }

    /**
     * Отменено пользователем
     */
    protected function cancel()
    {
        throw new InvalidParamException('Canceled by the user');
    }

    /**
     *  Копировать файлы из $fromPath в $toPath
     *
     * @param string $fromPath
     * @param string $toPath
     */
    protected function copyFiles($fromPath, $toPath, $settingsArray)
    {
        // trim paths
        $fromPath = rtrim($fromPath, "/\\");
        $toPath = rtrim($toPath, "/\\");
        // get files recursively
        $filePaths = $this->glob_recursive($fromPath . "/*");
        // generate new files
        $results = [];
        foreach ($filePaths as $file) {
            // skip directories
            if (is_dir($file)) {
                continue;
            }
            // calculate new file path and relative file
            $newFilePath = str_replace($fromPath, $toPath, $file);
            $relativeFile = str_replace($fromPath, "", $file);
            // get file content and replace namespace
            $content = file_get_contents($file);

            foreach ($settingsArray as $setting) {
                $from = key($setting);
                $to = $setting[$from];
                $content = str_replace($from, $to, $content);
            }

            // save and store result
            if (file_exists($newFilePath)) {
                $results[$relativeFile] = "File already exists ... skipping";
            } else {
                $result = $this->save($newFilePath, $content);
                $results[$relativeFile] = ($result === true ? "success" : $result);
            }
        }
        print_r($results);
    }

    /**
     * Recursive glob
     * Does not support flag GLOB_BRACE
     *
     * @link http://php.net/glob#106595
     *
     * @param string $pattern
     * @param int $flags
     * @return array
     */
    protected function glob_recursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this->glob_recursive($dir . '/' . basename($pattern), $flags));
        }
        return $files;
    }

    /**
     * Saves the code into the file specified by [[path]].
     * Taken/modified from yii\gii\CodeFile
     *
     * @param string $path
     * @param string $content
     * @return string|boolean the error occurred while saving the code file, or true if no error.
     */
    protected function save($path, $content)
    {
        $newDirMode = 0755;
        $newFileMode = 0644;
        $dir = dirname($path);
        if (!is_dir($dir)) {
            $mask = @umask(0);
            $result = @mkdir($dir, $newDirMode, true);
            @umask($mask);
            if (!$result) {
                return "Unable to create the directory '$dir'.";
            }
        }
        if (@file_put_contents($path, $content) === false) {
            return "Unable to write the file '{$path}'.";
        } else {
            $mask = @umask(0);
            @chmod($path, $newFileMode);
            @umask($mask);
        }
        return true;
    }
}