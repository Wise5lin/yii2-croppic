<?php

namespace tests\codeception\unit;

/*
 *          _)             __|  | _)
 * \ \  \ / | (_-<   -_) __ \  |  |    \
 *  \_/\_/ _| ___/ \___| ___/ _| _| _| _|
 *
 * @author Двуреченский Сергей
 * @link   <wise5lin@yandex.ru>
 */

use Yii;
use yii\helpers\Json;
use yii\helpers\FileHelper;
use org\bovigo\vfs\vfsStream;

class UploadActionTest extends TestCase
{
    /**
     * @var \frontend\tests\UnitTester
     */
    protected $tester;

    /**
     * {@inheritdoc}
     */
    protected function _after()
    {
        unset($_SERVER['REQUEST_METHOD']);
        unset($_FILES);
    }

    /**
     * Тест на незаполненный атрибут 'tempPath'.
     *
     * @expectedException        yii\base\InvalidConfigException
     * @expectedExceptionMessage Атрибут "tempPath" пуст или не является строкой.
     *
     * @method testEmptyPath
     */
    public function testEmptyPath()
    {
        Yii::$app->runAction('test/upload-empty-path');
    }

    /**
     * Тест на незаполненный атрибут 'tempUrl'.
     *
     * @expectedException        yii\base\InvalidConfigException
     * @expectedExceptionMessage Атрибут "tempUrl" пуст или не является строкой.
     *
     * @method testEmptyUrl
     */
    public function testEmptyUrl()
    {
        Yii::$app->runAction('test/upload-empty-url');
    }

    /**
     * Тест на соответствие модели экземпляру класса 'yii\base\Model'.
     *
     * @expectedException        yii\base\InvalidConfigException
     * @expectedExceptionMessage Атрибут "model" не является экземпляром
     *                           класса "yii\base\Model"
     *
     * @method testModelInstanceofClass
     */
    public function testModelInstanceofClass()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->runAction('test/upload-model-instanceof-class');
    }

    /**
     * Тест на неудачную загрузку изображения.
     *
     * @method testNotSuccessfulUploadImage
     */
    public function testNotSuccessfulUploadImage()
    {
        $path = vfsStream::url(parent::ROOT_DIR.'/'.parent::IMG_DIR.'/img.jpeg');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_FILES = [
            'img' => [
                'name' => 'img.jpeg',
                'type' => FileHelper::getMimeType($path),
                'size' => filesize($path),
                'tmp_name' => $path,
                'error' => UPLOAD_ERR_OK,
            ],
        ];

        $json = [
            'status' => 'error',
            'message' => 'Разрешена загрузка файлов только со следующими расширениями: png.',
        ];

        $this->tester->assertEquals(Json::encode($json), Yii::$app->runAction('test/upload-error'));
    }

    /**
     * Тест на удачную загрузку изображения.
     *
     * @method testSuccessfulUploadImage
     */
    public function testSuccessfulUploadImage()
    {
        $path = vfsStream::url(parent::ROOT_DIR.'/'.parent::IMG_DIR.'/img.jpeg');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_FILES = [
            'img' => [
                'name' => 'img.jpeg',
                'type' => FileHelper::getMimeType($path),
                'size' => filesize($path),
                'tmp_name' => $path,
                'error' => UPLOAD_ERR_OK,
            ],
        ];

        $json = [
            'status' => 'success',
            'url' => '/img/temp/img.jpeg',
            'width' => 100,
            'height' => 100,
        ];

        $this->tester->assertEquals(Json::encode($json), Yii::$app->runAction('test/upload'));
    }
}
