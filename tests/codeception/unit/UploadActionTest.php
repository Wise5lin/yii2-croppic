<?php

namespace tests\codeception\unit;

/**
 *          _)             __|  | _)
 * \ \  \ / | (_-<   -_) __ \  |  |    \
 *  \_/\_/ _| ___/ \___| ___/ _| _| _| _|
 *
 * @author Двуреченский Сергей
 * @link   <wise5lin@yandex.ru>
 */

use Yii;
use yii\helpers\Json;
use Codeception\Specify;
use yii\helpers\FileHelper;
use org\bovigo\vfs\vfsStream;

class UploadActionTest extends TestCase
{
    use Specify;

    protected function _after()
    {
        unset($_SERVER['REQUEST_METHOD']);
        unset($_FILES);
    }

    /**
     * @expectedException        yii\base\InvalidConfigException
     * @expectedExceptionMessage Атрибут "tempPath" не может быть пустым
     */
    public function testEmptyPath()
    {
        Yii::$app->runAction('test/upload-empty-path');
    }

    /**
     * @expectedException        yii\base\InvalidConfigException
     * @expectedExceptionMessage Атрибут "tempUrl" не может быть пустым
     */
    public function testEmptyUrl()
    {
        Yii::$app->runAction('test/upload-empty-url');
    }

    /**
     * @expectedException        yii\base\InvalidConfigException
     * @expectedExceptionMessage Атрибут "model" не является экземпляром
     *                           класса "yii\base\Model"
     */
    public function testModelInstanceofClass()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->runAction('test/upload-model-instanceof-class');
    }

    public function testUploadImage()
    {
        $path = vfsStream::url(parent::ROOT_DIR . '/' . parent::IMG_DIR . '/img.jpeg');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_FILES = [
            'img' => [
                'name' => 'img.jpeg',
                'type' => FileHelper::getMimeType($path),
                'size' => filesize($path),
                'tmp_name' => $path,
                'error' => UPLOAD_ERR_OK
            ]
        ];

        $this->specify('Неуспешная загрузка изображения (неверное расширение файла)', function () {
            $json = [
                'status' => 'error',
                'message' => 'Разрешена загрузка файлов только со следующими расширениями: png.',
            ];

            expect('Не удалось сохранить изображение', Yii::$app->runAction('test/upload-error'))
                ->equals(Json::encode($json));
        });

        $this->specify('Успешная загрузка изображения', function () {
            $json = [
                'status' => 'success',
                'url' => '/img/temp/img.jpeg',
                'width' => 100,
                'height' => 100
            ];

            expect('Изображение успешно сохранено', Yii::$app->runAction('test/upload'))
                ->equals(Json::encode($json));
        });
    }
}
