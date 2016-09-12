<?php

namespace wise5lin\croppic;

/**
 *          _)             __|  | _)
 * \ \  \ / | (_-<   -_) __ \  |  |    \
 *  \_/\_/ _| ___/ \___| ___/ _| _| _| _|
 *
 * @author Двуреченский Сергей
 * @link   <wise5lin@yandex.ru>
 */

use yii\web\AssetBundle;

/**
 * Класс комплекта ресурсов для виджета Croppic.
 */
class CroppicAsset extends AssetBundle
{
    public $sourcePath = '@wise5lin/croppic/assets';
    public $depends = [
        'yii\web\JqueryAsset',
    ];

    /**
     * Регистрирует CSS и JS файлы.
     *
     * @param \yii\web\View $view вид в котором файлы
     *                            должны быть зарегистрированы
     */
    public function registerAssetFiles($view)
    {
        $this->css[] = 'croppic' . (!YII_ENV_DEV ? '.min' : '') . '.css';
        $this->js[] = 'croppic' . (!YII_ENV_DEV ? '.min' : '') . '.js';
        $this->js[] = 'jquery.mousewheel.min.js';

        parent::registerAssetFiles($view);
    }
}
