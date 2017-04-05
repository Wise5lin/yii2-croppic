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

class CropActionTest extends TestCase
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
        if (is_file('tests/codeception/unit/data/img/cropped/img.jpeg')) {
            unlink('tests/codeception/unit/data/img/cropped/img.jpeg');
        }
    }

    /**
     * Тест на незаполненный атрибут 'path'.
     *
     * @expectedException        yii\base\InvalidConfigException
     * @expectedExceptionMessage Атрибут "path" пуст или не является строкой.
     *
     * @method testEmptyPath
     */
    public function testEmptyPath()
    {
        Yii::$app->runAction('test/crop-empty-path');
    }

    /**
     * Тест на незаполненный атрибут 'url'.
     *
     * @expectedException        yii\base\InvalidConfigException
     * @expectedExceptionMessage Атрибут "url" пуст или не является строкой.
     *
     * @method testEmptyUrl
     */
    public function testEmptyUrl()
    {
        Yii::$app->runAction('test/crop-empty-url');
    }

    /**
     * Тест на соответствие модели экземпляру класса 'yii\db\BaseActiveRecord'.
     *
     * @expectedException        yii\base\InvalidConfigException
     * @expectedExceptionMessage Атрибут "model" не является экземпляром
     *                           класса "yii\db\BaseActiveRecord"
     *
     * @method testModelInstanceofClass
     */
    public function testModelInstanceofClass()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->runAction('test/crop-model-instanceof-class');
    }

    /**
     * Тест на удачную обработку изображения.
     *
     * @method testCropImage
     */
    public function testCropImage()
    {
        Yii::$app->getSession()->set('tempImage', 'img.jpeg');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'imgUrl' => parent::IMG_DIR.'/img.jpeg',
            'imgW' => 100,
            'imgH' => 100,
            'imgY1' => 25,
            'imgX1' => 25,
            'cropW' => 50,
            'cropH' => 50,
            'rotation' => 0,
        ];

        $json = [
            'status' => 'success',
            'url' => '/img/cropped/img.jpeg',
        ];

        $this->tester->assertEquals(Json::encode($json), Yii::$app->runAction('test/crop'));
    }
}
