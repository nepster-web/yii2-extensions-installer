# Yii2 BASE MODULE INSTALLER
Установщик модулей базового приложения yii2
Расширение позволяет скопировать необходимые файлы модуля из одной директории в другую, при этом заменить некоторые части конфигурации.

## Установка

Предпочтительный способ установки этого виджета через [composer](http://getcomposer.org/download/).

Запустите в консоле

```
php composer.phar require nepster-web/yii2-module-users: dev-master
```

или добавьте

```
"nepster-web/yii2-module-users": "dev-master"
```

в файл `composer.json` в секцию require.

**После успешной установки пакета:**

Необходимо добавить следующий код в конфигурацию консольного приложения:

```php
    'modules' => [
        ...
        'users' => [
            'class' => 'nepster\users\Module',
            'controllerMap' => [
                'install' => 'nepster\users\commands\InstallController',
            ],
        ],
        ...
    ],
```


## Настройка

Необходимо добавить в файл конфигурации консольного приложения следующую настройку:

```php
'controllerMap' => [
    ...
    'installer' => [
        'class' => 'nepster\modules\installer\Installer',
        'from' => "@vendor/nepster-web/yii2-module-{module}/demo",
        'to' => "@common/modules/{module}",
        'namespace' => "common\\modules\\{module}",
        'controller' => "yii\\base\\Controller",
    ]
    ...
],
```

## Запуск

```
yii installer
```