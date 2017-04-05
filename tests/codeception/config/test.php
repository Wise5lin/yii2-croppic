<?php

return [
    'id' => 'testApp',
    'basePath' => dirname(dirname(__DIR__)),
    'language' => 'ru-RU',
    'controllerNamespace' => 'tests\codeception\unit\data\controllers',
    'components' => [
        'request' => [
            'class' => 'yii\web\Request',
            'url' => '/test',
            'enableCsrfValidation' => false,
        ],
        'response' => [
            'class' => 'yii\web\Response',
        ],
    ],
];
