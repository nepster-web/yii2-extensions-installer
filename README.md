# Yii2 EXTENSIONS INSTALLER
Инсталлер расширений для yii2
Расширение позволяет установить определенные пакеты с наличием файла ***install.json***. 

**Внимание**

Данный пакет был создан в личных целях для облегчения установки персональных модулей и расширений на базе Yii2.

## Установка

Предпочтительный способ установки этого виджета через [composer](http://getcomposer.org/download/).

Запустите в консоле

```
php composer.phar require --prefer-dist nepster-web/yii2-extensions-installer "*"
```

или добавьте

```
"nepster-web/yii2-extensions-installer": "*"
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

## Инсталляционный файл

Для работы инсталлера потребуется ***install.json*** примерно следующего содержания:

```
{
  "name": "users",
  "type": "module",
  "copy": {
    "@vendor/nepster-web/yii2-module-users/demo": "@common/modules/users"
  },
  "settings": {
    "Module namespace": "common\\modules\\users",
    "Path to module": "@common/modules/users",
    "Web controller": "yii\\web\\Controller"
  }
}
```

**Описание**

`name` - Название расширения.

`type` - Тип расширения (например module, component или др.).

`copy` - Массив директорий, ключ-значение, откуда и куда будут скопированны файлы.

`settings` - Массив настроек, ключ-значение, название и конфигурация по умолчанию.
