<?php

error_reporting(E_ALL);

defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', false);
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

defined('YII_APP_BASE_PATH') or define(
    'YII_APP_BASE_PATH',
    dirname(dirname(dirname(dirname(dirname(__DIR__)))))
);

require_once(YII_APP_BASE_PATH . '/vendor/autoload.php');
require_once(YII_APP_BASE_PATH . '/vendor/yiisoft/yii2/Yii.php');

Yii::setAlias('@tests', dirname(__DIR__));
