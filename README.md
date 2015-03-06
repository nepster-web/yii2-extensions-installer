# Yii2 EXTENSIONS INSTALLER
Инсталлер расширений для yii2
Расширение позволяет установить определенные пакеты с наличием файла ***install.json***. 

**Внимание**

Данный пакет был создан в личных целях для облегчения установки персональных модулей и расширений на базе Yii2.

## Установка

Предпочтительный способ установки этого виджета через [composer](http://getcomposer.org/download/).

Запустите в консоле

```
php composer.phar require --prefer-dist nepster-web/yii2-extensions-installer: dev-master
```

или добавьте

```
"nepster-web/yii2-extensions-installer": "dev-master"
```

в файл `composer.json` в секцию require.


## Настройка

Необходимо добавить в файл конфигурации консольного приложения следующую настройку:

```php
'controllerMap' => [
    ...
    'installer' => [
        'class' => 'nepster\modules\installer\Installer',
    ]
    ...
],
```

## Запуск

```
yii installer
```