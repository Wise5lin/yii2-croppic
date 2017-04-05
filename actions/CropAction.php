<?php

namespace wise5lin\croppic\actions;

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
use yii\base\DynamicModel;
use yii\helpers\FileHelper;
use yii\base\InvalidCallException;
use yii\web\ForbiddenHttpException;
use yii\base\InvalidConfigException;

/**
 * Класс действия для обрезки изображения.
 *
 * ИСПОЛЬЗОВАНИЕ:
 *
 * public function behaviors()
 * {
 *     return [
 *         'verbs' => [
 *             'class' => VerbFilter::className(),
 *             'actions' => [
 *                 'crop' => ['post'],
 *             ],
 *         ],
 *     ];
 * }
 *
 * public function actions()
 * {
 *     return [
 *         'crop' => [
 *             'class' => 'wise5lin\croppic\actions\CropAction',
 *             'path' => '@frontend/web/img/user/avatar',
 *             'url' => 'img/user/avatar/',
 *             'modelAttribute' => 'avatar',
 *             'modelScenario' => 'saveAvatar',
 *             'permissionRBAC' => 'updateProfile',
 *             'parameterRBAC' => 'profile',
 *         ],
 *     ];
 * }
 *
 * public function beforeAction($action)
 * {
 *     if ($action->id === 'upload' || $action->id === 'crop') {
 *         if ($action->hasProperty('model')) {
 *             $action->model = $this->findModel(Yii::$app->request->get('id'));
 *         }
 *     }
 *
 *     if (!parent::beforeAction($action)) {
 *         return false;
 *     }
 *
 *     return true;
 * }
 */
class CropAction extends \yii\base\Action
{
    /**
     * Абсолютный путь к директории в которую будет загружено изображение.
     *
     * @var string
     */
    public $path;
    /**
     * URL указывающий путь к директории в которую будет загружено изображение.
     *
     * @var string
     */
    public $url;
    /**
     * Экземпляр класса который будет использоваться для проверки доступа к
     * странице и сохранения пути или имени изображения в базу данных.
     *
     * @var string
     */
    public $model;
    /**
     * Сценарий используемый моделью для проверки входящих данных.
     *
     * @var string
     */
    public $modelScenario;
    /**
     * Название атрибута модели который будет использоваться для
     * сохранения пути или имени изображения в базу данных.
     *
     * @var string
     */
    public $modelAttribute;
    /**
     * Указывает, сохранять в базу данных полный путь к изображению
     * 'true' или только название изображения 'false'.
     * По умолчанию сохраняется полный путь к изображению.
     *
     * ПРИМЕР:
     * Берется значение из атрибута 'url' и к нему прибавляется
     * название изображения 'img/user/avatar/img.jpeg'.
     *
     * @var bool
     */
    public $modelAttributeSavePath = true;
    /**
     * RBAC разрешение для проверки доступа, например 'updateProfile'.
     *
     * ПРИМЕР:
     * Yii::$app->user->can('updateProfile', ['profile' => $this->model])
     *
     * @var string
     */
    public $permissionRBAC;
    /**
     * RBAC параметр для проверки доступа, например 'profile'.
     *
     * ПРИМЕР:
     * Yii::$app->user->can('updateProfile', ['profile' => $this->model])
     *
     * @var string
     */
    public $parameterRBAC;

    /**
     * Путь и название сохраненного изображения.
     *
     * @var string
     */
    private $croppedImage;

    //   _ \ _)   _| _|                     |       __|            |
    //   |  | |   _| _| -_)   _| -_)    \    _|    (      _ \   _` |   -_)
    //  ___/ _| _| _| \___| _| \___| _| _| \__|   \___| \___/ \__,_| \___|

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        /** @var \yii\web\Request */
        $request = Yii::$app->request;

        // Если в 'POST' существует параметр 'path'.
        if (($path = $request->post('path')) !== null) {
            $this->path = $path;
        }

        // Если атрибут 'path' не заполнен или не является строкой.
        if (empty($this->path) || is_string($this->path) === false) {
            throw new InvalidConfigException(
                'Атрибут "path" пуст или не является строкой.'
            );
        }

        $this->path = rtrim(Yii::getAlias($this->path), '/').'/';

        // Если в 'POST' существует параметр 'url'.
        if (($url = $request->post('url')) !== null) {
            $this->url = $url;
        }

        // Если атрибут 'url' не заполнен или не является строкой.
        if (empty($this->url) || is_string($this->url) === false) {
            throw new InvalidConfigException(
                'Атрибут "url" пуст или не является строкой.'
            );
        }

        $this->url = rtrim($this->url, '/').'/';

        // Если директория не существует или не удается её создать.
        if (FileHelper::createDirectory($this->path) === false) {
            throw new InvalidCallException(
                'Директория указанная в атрибуте "path" не существует или не может быть создана.'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        // Работаем с дополнительными возможностями.
        $this->workWithAdditionalFeatures();

        // Получаем данные и правила валидации.
        list($data, $rules) = $this->getValidateData();

        // Проводим проверку атрибутов пришедших по средствам 'POST'.
        $model = DynamicModel::validateData($data, $rules);

        /** @var \yii\web\Session */
        $session = Yii::$app->session;

        // Если нет ошибок валидации и изображение успешно обработано.
        if ($model->hasErrors() === false && $this->isCroppedImage($model, $session)) {
            // Сохраняем данные в базу.
            $this->saveModel($session);

            // Удаляем изображение из папки темп.
            $this->removeTempImage($model->imgUrl, $session);

            // Формируем удачный ответ.
            $response = [
                'status' => 'success',
                'url' => $this->croppedImage,
            ];

            // Выходим.
            goto leave;
        }

        // Формируем неудачный ответ.
        $response = [
            'status' => 'error',
            'message' => 'Не удалось обработать изображение.',
        ];

        leave:

        // Возвращаем JSON стоку.
        return Json::encode($response);
    }

    /**
     * Работает с дополнительными возможностями.
     *
     * @method workWithAdditionalFeatures
     */
    private function workWithAdditionalFeatures()
    {
        // Если атрибут 'model' заполнен.
        if (empty($this->model) === false) {
            // Если атрибут 'model' не является экземпляром
            // класса 'yii\db\BaseActiveRecord'.
            if (($this->model instanceof \yii\db\BaseActiveRecord) === false) {
                throw new InvalidConfigException(
                    'Атрибут "model" не является экземпляром класса "yii\db\BaseActiveRecord".'
                );
            }

            // Если атрибут 'modelScenario' заполнен.
            if (empty($this->modelScenario) === false) {
                $this->model->setScenario($this->modelScenario);
            }

            // Если атрибуты 'permissionRBAC' и 'parameterRBAC' заполнены.
            if (empty($this->permissionRBAC) === false && empty($this->parameterRBAC) === false) {
                // Проверяем доступ пользователя к странице.
                if (!Yii::$app->user->can($this->permissionRBAC, [$this->parameterRBAC => $this->model])) {
                    throw new ForbiddenHttpException('У вас нет доступа к этой странице.');
                }
            }
        }
    }

    /**
     * Формирует и возвращает массив с данными пришедшими по
     * средствам POST и правилами валидации.
     *
     * @method getValidateData
     *
     * @return array
     */
    private function getValidateData()
    {
        /** @var \yii\web\Request */
        $request = Yii::$app->request;

        return [
            [
                // Путь к загруженному изображению.
                'imgUrl' => $request->post('imgUrl'),
                // Измененные разметы изображения.
                'imgW' => $request->post('imgW'),
                'imgH' => $request->post('imgH'),
                // Смещение изображения.
                'imgY1' => $request->post('imgY1'),
                'imgX1' => $request->post('imgX1'),
                // Размеры области обрезки.
                'cropW' => $request->post('cropW'),
                'cropH' => $request->post('cropH'),
                // Угол поворота изображения.
                'rotation' => $request->post('rotation'),
            ],
            [
                [['imgUrl', 'imgW', 'imgH', 'imgX1', 'imgY1', 'cropW', 'cropH', 'rotation'], 'required'],
                ['imgUrl', 'string'],
                ['imgUrl', 'filter', 'filter' => 'strip_tags'],
                [['imgW', 'imgH', 'imgX1', 'imgY1', 'cropW', 'cropH', 'rotation'], 'double'],
            ],
        ];
    }

    /**
     * Получает загруженное изображение, обрабатывает и сохраняет в указанную папку.
     * Если необходимо, сохраняет путь или имя изображения в базу данных.
     *
     * @method isCroppedImage
     *
     * @param DynamicModel     $model
     * @param \yii\web\Session $session
     *
     * @return bool true если изображение успешно обработано
     */
    private function isCroppedImage(DynamicModel $model, \yii\web\Session $session)
    {
        // Если изображение не существует или не может быть прочитано.
        if (is_readable(Yii::getAlias('@webroot/'.$model->imgUrl)) === false) {
            throw new InvalidCallException(
                'Изображение не существует или не может быть прочитано.'
            );
        }

        \yii\imagine\Image::$driver = [
            \yii\imagine\Image::DRIVER_IMAGICK,
            \yii\imagine\Image::DRIVER_GMAGICK,
            \yii\imagine\Image::DRIVER_GD2,
        ];

        /** @var \yii\imagine\Image */
        $imagine = new \yii\imagine\Image();

        // Если атрибуты 'model' и 'modelAttribute' заполнены.
        if (empty($this->model) === false && empty($this->modelAttribute) === false) {
            // Удаляем предыдущее изображение.
            $this->removeImage();
        }

        // Обрабатываем и сохраняем изображение.
        $image = $imagine->getImagine()
        ->open(Yii::getAlias('@webroot/'.$model->imgUrl))
        ->resize(new \Imagine\Image\Box($model->imgW, $model->imgH))
        ->rotate($model->rotation)
        ->crop(
            new \Imagine\Image\Point($model->imgX1, $model->imgY1),
            new \Imagine\Image\Box($model->cropW, $model->cropH)
        )
        ->save($this->path.$session->get('tempImage'));

        // Если не удалось сохранить изображение.
        if ($image === false) {
            return false;
        }

        // Получаем url по которому сохранено изображение.
        $this->croppedImage = $this->url.$session->get('tempImage');

        return true;
    }

    /**
     * Удаляет предыдущее изображение, перед тем как сохранить новое.
     *
     * @method removeImage
     */
    private function removeImage()
    {
        // Получаем название атрибута модели.
        $modelAttribute = $this->modelAttribute;

        // Получаем путь до изображния.
        $path = $this->modelAttributeSavePath ?
            Yii::getAlias('@webroot/'.$this->model->$modelAttribute) :
            $this->path.$this->model->$modelAttribute;

        // Если изображение существует.
        if (is_file($path)) {
            // Удаляем изображение.
            unlink($path);
        }
    }

    /**
     * Сохраняет путь или только имя изображения в базу.
     *
     * @method saveModel
     *
     * @param \yii\web\Session $session
     */
    private function saveModel(\yii\web\Session $session)
    {
        // Если атрибуты 'model' и 'modelAttribute' заполнены.
        if (empty($this->model) === false && empty($this->modelAttribute) === false) {
            // Получаем название атрибута модели.
            $modelAttribute = $this->modelAttribute;

            // Присваиваем указанному атрибуту путь или только имя изображения.
            $this->model->$modelAttribute = $this->modelAttributeSavePath ?
                $this->croppedImage : $session->get('tempImage');

            // Если сохранение не удалось.
            if ($this->model->save() === false) {
                // Пишем лог сообщение.
                Yii::error([
                    'extension' => 'wise5lin/croppic',
                    'message' => $this->model->getErrors(),
                ]);
            }
        }
    }

    /**
     * Удаляет изображение из папки и запись из сессии.
     *
     * @method removeTempImage
     *
     * @param string           $imgUrl  url до изображения
     * @param \yii\web\Session $session
     */
    private function removeTempImage($imgUrl, \yii\web\Session $session)
    {
        // Получаем путь до изображния.
        $path = Yii::getAlias('@webroot/'.$imgUrl);

        // Если изображение существует.
        if (is_file($path)) {
            // Удаляем изображение.
            unlink($path);
        }

        // Удаляем запись из сессии.
        $session->remove('tempImage');
    }
}
