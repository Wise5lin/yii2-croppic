<?php

namespace wise5lin\croppic;

/*
 *          _)             __|  | _)
 * \ \  \ / | (_-<   -_) __ \  |  |    \
 *  \_/\_/ _| ___/ \___| ___/ _| _| _| _|
 *
 * @author Двуреченский Сергей
 * @link   <wise5lin@yandex.ru>
 */

use yii\helpers\Html;
use yii\helpers\Json;
use yii\base\InvalidConfigException;

/**
 * Виджет для Croppic - jquery плагин для обрезки изображения.
 *
 * @see http://www.croppic.net/
 *
 * @example https://github.com/sconsult/croppic
 *
 * ИСПОЛЬЗОВАНИЕ:
 *
 * use wise5lin\croppic\Croppic;
 *
 * <?= Croppic::widget([
 *    'options' => [
 *       'class' => 'croppic',
 *    ],
 *    'pluginOptions' => [
 *       'uploadUrl' => $model->urlUpload,
 *       'cropUrl' => $model->urlCrop,
 *       'modal' => false,
 *       'doubleZoomControls' => false,
 *       'enableMousescroll' => true,
 *       'loaderHtml' => '<div class="loader bubblingG">
 *          <span id="bubblingG_1"></span>
 *          <span id="bubblingG_2"></span>
 *          <span id="bubblingG_3"></span>
 *       </div> ',
 *    ],
 * ]) ?>
 */
class Croppic extends \yii\base\Widget
{
    /**
     * HTML атрибуты для тега div.
     *
     * @var array
     */
    public $options = [];
    /**
     * Js опции плагина Croppic, все возможные опции
     * смотрите на официальном сайте - "http://www.croppic.net/".
     *
     * @var array
     */
    public $pluginOptions = [];

    //   _ \ _)   _| _|                     |       __|            |
    //   |  | |   _| _| -_)   _| -_)    \    _|    (      _ \   _` |   -_)
    //  ___/ _| _| _| \___| _| \___| _| _| \__|   \___| \___/ \__,_| \___|

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        // Если не установлен 'id' виджета.
        if (empty($this->options['id'])) {
            // Используем автоматически сгенерированный id.
            $this->options['id'] = $this->getId();
        }

        // Присваиваем 'id' виджету.
        $this->id = $this->options['id'];

        // Если параметр 'uploadUrl' не заполнен.
        if (empty($this->pluginOptions['uploadUrl'])) {
            throw new InvalidConfigException('Параметр "uploadUrl" не может быть пустым');
        }

        // Если параметр 'cropUrl' не заполнен.
        if (empty($this->pluginOptions['cropUrl'])) {
            throw new InvalidConfigException('Параметр "cropUrl" не может быть пустым');
        }

        parent::init();
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        echo Html::tag('div', '', $this->options);

        $this->registerClientScript();
    }

    /**
     * Регистрирует CSS и JS файлы на странице.
     *
     * @method registerClientScript
     */
    public function registerClientScript()
    {
        $view = $this->getView();
        CroppicAsset::register($view);

        $pluginOptions = Json::encode($this->pluginOptions);
        $registerJs = "var {$this->id} = new Croppic('{$this->id}', {$pluginOptions});";

        $view->registerJs($registerJs);
    }
}
