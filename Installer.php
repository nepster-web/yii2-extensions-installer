<?php

namespace nepster\modules\installer;

use yii\helpers\Console;
use yii\log\Logger;
use Yii;

/**
 * Install yii2 base modules
 */
class Installer extends \yii\console\Controller
{
    /**
     * @var string
     */
    public $template = null;

    /**
     * @var string
     */
    public $from = "@vendor/nepster-web/yii2-module-{module}/demo";

    /**
     * @var string
     */
    public $to = "@common/modules/{module}";

    /**
     * @var string
     */
    public $namespace = "common\\modules\\{module}";

    /**
     * @var string
     */
    public $controller = "yii\\base\Controller";

    /**
     * Установка модуля
     */
    public function actionIndex()
    {
        $module = $this->prompt('Enter module name:');

        $this->from = str_replace('{module}', $module, $this->from);
        $this->to = str_replace('{module}', $module, $this->to);
        $this->namespace = str_replace('{module}', $module, $this->namespace);

        $from = $this->prompt('Module path [' . $this->from . ']:');
        if ($from) {
            $this->from = $from;
        }

        $to = $this->prompt('Copy to [' . $this->to . ']:');
        if ($to) {
            $this->to = $to;
        }

        $namespace = $this->prompt('Use namespace [' . $this->namespace . ']:');
        if ($namespace) {
            $this->namespace = $namespace;
        }

        $controller = $this->prompt('Base controller [' . $this->controller . ']:');
        if ($controller) {
            $this->controller = $controller;
        }

        // Сообщение для подтверждения
        $confirmMsg = PHP_EOL;
        $confirmMsg .= "Install module. Please confirm:" . PHP_EOL;
        $confirmMsg .= PHP_EOL;
        $confirmMsg .= " From [ $this->from ]" . PHP_EOL;
        $confirmMsg .= " To [ $this->to ]" . PHP_EOL;
        $confirmMsg .= " Namespace [ $this->namespace ]" . PHP_EOL;
        $confirmMsg .= " Base Controller [ $this->controller ]" . PHP_EOL;
        $confirmMsg .= PHP_EOL;
        $confirmMsg .= "(yes|no)";

        // Подтверждение
        $confirm = $this->prompt($confirmMsg, [
            "required" => true,
            "default" => "no",
        ]);

        echo PHP_EOL;

        // Копирование файлов
        if (strncasecmp($confirm, "y", 1) === 0) {
            $this->stdout("Install" . PHP_EOL, Console::FG_GREEN);
            $fromPath = Yii::getAlias($this->from);
            $toPath = Yii::getAlias($this->to);
            $this->copyFiles($fromPath, $toPath);
        } else {
            $this->stdout("Install cancelled" . PHP_EOL, Console::FG_RED);
        }
    }

    /**
     *  Копировать файлы из $fromPath в $toPath
     *
     * @param string $fromPath
     * @param string $toPath
     */
    protected function copyFiles($fromPath, $toPath)
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
            $content = str_replace("common\\modules\\users", $this->namespace, $content);
            $content = str_replace("yii\\base\\Controller", $this->controller, $content);

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