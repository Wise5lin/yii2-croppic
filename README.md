# Croppic виджет для Yii2 Framework

**Croppic** - это JQuery плагин для обрезки изображения.

 - **Github** - https://github.com/sconsult/croppic
 - **Официальный сайт** - http://www.croppic.net/

## Установка

Желательно устанавливать расширение через [composer](http://getcomposer.org/download/).

Просто запустите в консоли команду:

```bash
php composer.phar require --prefer-dist wise5lin/yii2-croppic "*"
```

или добавьте

```json
"wise5lin/yii2-croppic": "*"
```

в `require` секцию вашего `composer.json` файла.

## Использование

Как только вы установили расширение, вы можете его использовать в своём коде:

```php
use wise5lin\croppic\Croppic;

<?= Croppic::widget([
    'options' => [
        'class' => 'croppic',
    ],
    'pluginOptions' => [
        'uploadUrl' => $model->urlUpload,
        'cropUrl' => $model->urlCrop,
        'modal' => false,
        'doubleZoomControls' => false,
        'enableMousescroll' => true,
        'loaderHtml' => '<div class="loader bubblingG">
            <span id="bubblingG_1"></span>
            <span id="bubblingG_2"></span>
            <span id="bubblingG_3"></span>
        </div> ',
    ]
]) ?>
```

## Загрузка и обрезка изображения

```php
public function behaviors()
{
    return [
        'verbs' => [
            'class' => VerbFilter::className(),
            'actions' => [
                'upload' => ['post'],
                'crop' => ['post'],
            ],
        ],
    ];
}

public function actions()
{
    return [
        /**
         * Загрузка изображения
         */
        'upload' => [
            'class' => 'wise5lin\croppic\actions\UploadAction',
            // Абсолютный путь к папке в которую будет сохранено изображение (временно).
            'tempPath' => '@frontend/web/img/temp',
            // URL адрес к папке в которую будет сохранено изображение (временно).
            'tempUrl' => 'img/temp/',
            // Правила проверки изображения.
            'validatorOptions' => [
                'checkExtensionByMimeType' => true,
                'extensions' => 'jpeg, jpg, png',
                'maxSize' => 3000000,
                'tooBig' => 'Выбранное вами изображение слишком большое (макс. 3мб)',
            ],
        ],
        /**
         * Обрезка изображения
         */
        'crop' => [
            'class' => 'wise5lin\croppic\actions\CropAction',
            // Абсолютный путь к папке в которую будет сохранено изображение.
            'path' => '@frontend/web/img/user/avatar',
            // URL адрес к папке в которую будет сохранено изображение.
            'url' => 'img/user/avatar/',
        ],
    ];
}
```

### Дополнительные возможности

Чтобы воспользоваться дополнительными возможностями вы должны передать действиям **объект класса модели**:

```php
public function beforeAction($action)
{
    if ($action->id === 'upload' || $action->id === 'crop') {
        if ($action->hasProperty('model')) {
            $action->model = $this->findModel(Yii::$app->request->get('id'));
        }
    }

    if (!parent::beforeAction($action)) {
        return false;
    }

    return true;
}
```

#### Проверка доступа пользователя к страницам с помощью RBAC ####

Передайте действиям **разрешение** и **параметр** RBAC:

```php
public function actions()
{
    return [
        /**
         * Загрузка изображения
         */
        'upload' => [
            'class' => 'wise5lin\croppic\actions\UploadAction',
            'tempPath' => '@frontend/web/img/temp',
            'tempUrl' => 'img/temp/',
            'validatorOptions' => [
                'checkExtensionByMimeType' => true,
                'extensions' => 'jpeg, jpg, png',
                'maxSize' => 3000000,
                'tooBig' => 'Выбранное вами изображение слишком большое (макс. 3мб)',
            ],
            // Разрешение RBAC
            'permissionRBAC' => 'updateProfile',
            // Параметр RBAC
            'parameterRBAC' => 'profile',
        ],
        /**
         * Обрезка изображения
         */
        'crop' => [
            'class' => 'wise5lin\croppic\actions\CropAction',
            'path' => '@frontend/web/img/user/avatar',
            'url' => 'img/user/avatar/',
            // Разрешение RBAC
            'permissionRBAC' => 'updateProfile',
            // Параметр RBAC
            'parameterRBAC' => 'profile',
        ],
    ];
}
```

Как будет производиться проверка: `Yii::$app->user->can('updateProfile', ['profile' => $this->model])`.

#### Сохранение пути или имени изображения в базу ####

```php
public function actions()
{
    return [
        /**
         * Обрезка изображения
         */
        'crop' => [
            'class' => 'wise5lin\croppic\actions\CropAction',
            'path' => '@frontend/web/img/user/avatar',
            'url' => 'img/user/avatar/',
            'modelAttribute' => 'avatar', // <--- пример №1
            'modelScenario' => 'saveAvatar', // <--- пример №2
            'modelAttributeSavePath' => false, // <--- пример №3
        ],
    ];
}
```

Передайте действию **crop**:
 - **Название атрибута модели** который будет использоваться для сохранения (**пример №1**).
 - **Сценарий модели** используемый для проверки входящих данных (**пример №2**).
 - Если нужно сохранить только имя изображения, для параметра **modelAttributeSavePath**
   укажите значение **false** (**пример №3**).
