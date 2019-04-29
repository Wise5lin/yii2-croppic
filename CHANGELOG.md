1.0.3
-----
- Merge pull request #1 from hreitsma/patch-1

1.0.2
-----

- Небольшой рефакторинг кода
- Для действия **crop** добавлена возможность задания **path** и **url** через настройки виджета.

```php
use wise5lin\croppic\Croppic;

<?= Croppic::widget([
    'options' => [
        'class' => 'croppic',
    ],
    'pluginOptions' => [
        'uploadUrl' => $model->urlUpload,
        'cropUrl' => $model->urlCrop,
        'cropData' => [
            // Динамическое задание пути для сохранения изображения.
            'path' => '@frontend/web/img/user/avatar/'.$model->id,
            // Динамическое задание URL для сохранения изображения.
            'url' => '/img/user/avatar/'.$model->id,
        ],
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

1.0.1
-----

- Добавлен croppic.min.css

1.0.0
-----

- Первый выпуск (wise5lin)
