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

class CropActionTest extends TestCase
{
    use Specify;

    protected function _after()
    {
        if (is_file('tests/codeception/unit/data/img/cropped/img.jpeg')) {
            unlink('tests/codeception/unit/data/img/cropped/img.jpeg');
        }
    }

    /**
     * @expectedException        yii\base\InvalidConfigException
     * @expectedExceptionMessage Атрибут "path" не может быть пустым
     */
    public function testEmptyPath()
    {
        Yii::$app->runAction('test/crop-empty-path');
    }

    /**
     * @expectedException        yii\base\InvalidConfigException
     * @expectedExceptionMessage Атрибут "url" не может быть пустым
     */
    public function testEmptyUrl()
    {
        Yii::$app->runAction('test/crop-empty-url');
    }

    /**
     * @expectedException        yii\base\InvalidConfigException
     * @expectedExceptionMessage Атрибут "model" не является экземпляром
     *                           класса "yii\db\BaseActiveRecord"
     */
    public function testModelInstanceofClass()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->runAction('test/crop-model-instanceof-class');
    }

    public function testCropImage()
    {
        Yii::$app->getSession()->set('tempImage', 'img.jpeg');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'imgUrl' => parent::IMG_DIR . '/img.jpeg',
            'imgW' => 100,
            'imgH' => 100,
            'imgY1' => 25,
            'imgX1' => 25,
            'cropW' => 50,
            'cropH' => 50,
            'rotation' => 0
        ];

        $json = [
            'status' => 'success',
            'url' => '/img/cropped/img.jpeg',
        ];

        expect('Изображение успешно сохранено', Yii::$app->runAction('test/crop'))
            ->equals(Json::encode($json));
    }
}
