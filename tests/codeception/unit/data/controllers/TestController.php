<?php

namespace tests\codeception\unit\data\controllers;

/**
 *          _)             __|  | _)
 * \ \  \ / | (_-<   -_) __ \  |  |    \
 *  \_/\_/ _| ___/ \___| ___/ _| _| _| _|
 *
 * @author Двуреченский Сергей
 * @link   <wise5lin@yandex.ru>
 */

use org\bovigo\vfs\vfsStream;
use tests\codeception\unit\TestCase;
use wise5lin\croppic\actions\CropAction;
use wise5lin\croppic\actions\UploadAction;

class TestController extends \yii\web\Controller
{
    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'upload-empty-path' => [
                'class' => UploadAction::className(),
                'tempPath' => null,
            ],
            'upload-empty-url' => [
                'class' => UploadAction::className(),
                'tempPath' => vfsStream::url(
                    TestCase::ROOT_DIR . '/' .
                    TestCase::IMG_DIR . '/' .
                    TestCase::SAVE_TEMP_DIR
                ),
                'tempUrl' => null,
            ],
            'upload-model-instanceof-class' => [
                'class' => UploadAction::className(),
                'tempPath' => vfsStream::url(
                    TestCase::ROOT_DIR . '/' .
                    TestCase::IMG_DIR . '/' .
                    TestCase::SAVE_TEMP_DIR
                ),
                'tempUrl' => '/img/temp',
            ],
            'upload-error' => [
                'class' => UploadAction::className(),
                'tempPath' => vfsStream::url(
                    TestCase::ROOT_DIR . '/' .
                    TestCase::IMG_DIR . '/' .
                    TestCase::SAVE_TEMP_DIR
                ),
                'tempUrl' => '/img/temp',
                'validatorOptions' => [
                    'checkExtensionByMimeType' => true,
                    'extensions' => ['png']
                ]
            ],
            'upload' => [
                'class' => UploadAction::className(),
                'tempPath' => vfsStream::url(
                    TestCase::ROOT_DIR . '/' .
                    TestCase::IMG_DIR . '/' .
                    TestCase::SAVE_TEMP_DIR
                ),
                'tempUrl' => '/img/temp',
                'uniqueName' => false,
                'validatorOptions' => [
                    'checkExtensionByMimeType' => true,
                    'extensions' => ['jpeg', 'png']
                ]
            ],

            'crop-empty-path' => [
                'class' => CropAction::className(),
                'path' => null,
            ],
            'crop-empty-url' => [
                'class' => CropAction::className(),
                'path' => vfsStream::url(
                    TestCase::ROOT_DIR . '/' .
                    TestCase::IMG_DIR . '/' .
                    TestCase::SAVE_CROPPED_DIR
                ),
                'url' => null,
            ],
            'crop-model-instanceof-class' => [
                'class' => CropAction::className(),
                'path' => vfsStream::url(
                    TestCase::ROOT_DIR . '/' .
                    TestCase::IMG_DIR . '/' .
                    TestCase::SAVE_CROPPED_DIR
                ),
                'url' => '/img/cropped',
            ],
            'crop' => [
                'class' => CropAction::className(),
                'path' => 'tests/codeception/unit/data/img/cropped',
                'url' => '/img/cropped',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'upload-empty-path' => ['post'],
                    'upload-empty-url' => ['post'],
                    'upload-model-instanceof-class' => ['post'],
                    'upload-error' => ['post'],
                    'upload' => ['post'],
                    'crop-empty-path' => ['post'],
                    'crop-empty-url' => ['post'],
                    'crop-model-instanceof-class' => ['post'],
                    'crop' => ['post'],
                ]
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if ($action->id === 'upload-model-instanceof-class' || $action->id === 'crop-model-instanceof-class') {
            if ($action->hasProperty('model')) {
                $action->model = new \yii\helpers\Html;
            }
        }

        if (!parent::beforeAction($action)) {
            return false;
        }

        return true;
    }
}
