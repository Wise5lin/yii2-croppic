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
use yii\web\UploadedFile;
use yii\base\DynamicModel;
use yii\helpers\FileHelper;
use yii\base\InvalidCallException;
use yii\web\ForbiddenHttpException;
use yii\base\InvalidConfigException;

/**
 * Класс действия для загрузки изображения.
 *
 * ИСПОЛЬЗОВАНИЕ:
 *
 * public function behaviors()
 * {
 *     return [
 *         'verbs' => [
 *             'class' => VerbFilter::className(),
 *             'actions' => [
 *                 'upload' => ['post'],
 *             ],
 *         ],
 *     ];
 * }
 *
 * public function actions()
 * {
 *     return [
 *         'upload' => [
 *             'class' => 'wise5lin\croppic\actions\UploadAction',
 *             'tempPath' => '@frontend/web/img/temp',
 *             'tempUrl' => 'img/temp/',
 *             'validatorOptions' => [
 *                 'checkExtensionByMimeType' => true,
 *                 'extensions' => 'jpeg, jpg, png',
 *                 'maxSize' => 3000000,
 *                 'tooBig' => 'Выбранное вами изображение слишком большое (макс. 3мб)',
 *             ]
 *             'permissionRBAC' => 'updateProfile',
 *             'parameterRBAC' => 'profile'
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
class UploadAction extends \yii\base\Action
{
    /**
     * Абсолютный путь к директории в которую будет загружено изображение.
     *
     * @var string
     */
    public $tempPath;
    /**
     * URL указывающий путь к директории в которую будет загружено изображение.
     *
     * @var string
     */
    public $tempUrl;
    /**
     * Указывает, генерировать уникальное название для
     * загружаемого изображения или нет.
     *
     * @var bool
     */
    public $uniqueName = true;
    /**
     * Правила для проверки загружаемого изображения.
     *
     * @var array
     */
    public $validatorOptions = [];
    /**
     * Экземпляр класса который будет использоваться
     * для проверки доступа к странице.
     *
     * @var string
     */
    public $model;
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
    private $savedImage;

    //   _ \ _)   _| _|                     |       __|            |
    //   |  | |   _| _| -_)   _| -_)    \    _|    (      _ \   _` |   -_)
    //  ___/ _| _| _| \___| _| \___| _| _| \__|   \___| \___/ \__,_| \___|

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        // Если атрибут 'tempPath' не заполнен или не является строкой.
        if (empty($this->tempPath) || is_string($this->tempPath) === false) {
            throw new InvalidConfigException(
                'Атрибут "tempPath" пуст или не является строкой.'
            );
        }

        $this->tempPath = rtrim(Yii::getAlias($this->tempPath), '/').'/';

        // Если атрибут 'tempUrl' не заполнен или не является строкой.
        if (empty($this->tempUrl) || is_string($this->tempUrl) === false) {
            throw new InvalidConfigException(
                'Атрибут "tempUrl" пуст или не является строкой.'
            );
        }

        $this->tempUrl = rtrim($this->tempUrl, '/').'/';

        // Если директория не существует или не удается её создать.
        if (FileHelper::createDirectory($this->tempPath) === false) {
            throw new InvalidCallException(
                'Директория указанная в атрибуте "tempPath" не существует или не может быть создана.'
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

        // Получаем изображение.
        $image = UploadedFile::getInstanceByName('img');

        // Проводим проверку загружаемого изображения.
        $model = new DynamicModel(compact('image'));
        $model->addRule('image', 'required')
        ->addRule('image', 'image', $this->validatorOptions)
        ->validate();

        /** @var \yii\web\Session */
        $session = Yii::$app->session;

        // Если нет ошибок валидации и изображение успешно сохранено.
        if ($model->hasErrors() === false && $this->isSavedImage($model->image, $session)) {
            // Получаем высоту и ширину изображения.
            list($width, $height) = getimagesize(
                $this->tempPath.$session->get('tempImage')
            );

            // Формируем удачный ответ.
            $response = [
                'status' => 'success',
                'url' => $this->savedImage,
                'width' => $width,
                'height' => $height,
            ];

            // Выходим.
            goto leave;
        }

        // Формируем неудачный ответ.
        $response = [
            'status' => 'error',
            'message' => $model->getFirstError('image') !== null ?
                $model->getFirstError('image') :
                'Не удалось загрузить изображение.',
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
            // Если атрибут 'model' не является экземпляром класса "yii\base\Model".
            if (($this->model instanceof \yii\base\Model) === false) {
                throw new InvalidConfigException(
                    'Атрибут "model" не является экземпляром класса "yii\base\Model".'
                );
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
     * Сохраняет загруженное изображение в папку и в
     * сессию записывает название изображения.
     *
     * @method isSavedImage
     *
     * @param UploadedFile     $image
     * @param \yii\web\Session $session
     *
     * @return bool true если изображение успешно сохранено
     */
    private function isSavedImage(UploadedFile $image, \yii\web\Session $session)
    {
        // Если необходимо сгенерировать уникальное название изображения.
        if ($this->uniqueName && $image->extension) {
            $image->name = uniqid('i-'.time()).'.'.$image->extension;
        }

        // Если в сессии существует запись.
        if ($session->get('tempImage')) {
            // Удаляем запись и изображение если существет.
            $this->removeImage($session);
        }

        // Сохраняем в сессию название изображения.
        $session->set('tempImage', $image->name);

        // Если не удалось сохранить изображение.
        if ($image->saveAs($this->tempPath.$image->name) === false) {
            return false;
        }

        // Получаем url по которому сохранено изображение.
        $this->savedImage = $this->tempUrl.$image->name;

        return true;
    }

    /**
     * Удаляет изображение из папки и запись из сессии.
     *
     * @method removeImage
     *
     * @param \yii\web\Session $session
     */
    private function removeImage(\yii\web\Session $session)
    {
        // Получаем путь до изображния.
        $path = $this->tempPath.$session->get('tempImage');

        // Если изображение существует.
        if (is_file($path)) {
            // Удаляем изображение.
            unlink($path);
        }

        // Удаляем запись из сессии.
        $session->remove('tempImage');
    }
}
