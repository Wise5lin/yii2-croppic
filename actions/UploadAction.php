<?php

namespace wise5lin\croppic\actions;

/**
 *          _)             __|  | _)
 * \ \  \ / | (_-<   -_) __ \  |  |    \
 *  \_/\_/ _| ___/ \___| ___/ _| _| _| _|
 *
 * @author Двуреченский Сергей
 * @link   <wise5lin@yandex.ru>
 */

use Yii;
use yii\base\Action;
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
 * Использование:
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
class UploadAction extends Action
{
    /**
     * Абсолютный путь к директории в
     * которую будет загружено изображение.
     *
     * @var string
     */
    public $tempPath;
    /**
     * URL указывающий путь к директории в
     * которую будет загружено изображение.
     *
     * @var string
     */
    public $tempUrl;
    /**
     * Указывает, генерировать ли уникальное
     * название для загружаемого изображения.
     *
     * @var boolean
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

    /**
     * @inheritdoc
     */
    public function init()
    {
        // Если атрибут 'tempPath' не заполнен.
        if ($this->tempPath === null) {
            throw new InvalidConfigException(
                'Атрибут "tempPath" не может быть пустым.'
            );
        }
        $this->tempPath = rtrim(Yii::getAlias($this->tempPath), '/') . '/';

        // Если атрибут 'tempUrl' не заполнен.
        if ($this->tempUrl === null) {
            throw new InvalidConfigException(
                'Атрибут "tempUrl" не может быть пустым.'
            );
        }
        $this->tempUrl = rtrim($this->tempUrl, '/') . '/';

        // Если директория не существует или не удается её создать.
        if (!FileHelper::createDirectory($this->tempPath)) {
            throw new InvalidCallException(
                'Директория указанная в атрибуте "tempPath" не существует или не может быть создана.'
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        // Если атрибут 'model' заполнен.
        if ($this->model !== null) {
            // Проверяем чтобы он являлся экземпляром класса "yii\base\Model".
            if (!($this->model instanceof \yii\base\Model)) {
                throw new InvalidConfigException(
                    'Атрибут "model" не является экземпляром класса "yii\base\Model".'
                );
            }
            // Если атрибуты 'permissionRBAC' и 'parameterRBAC' заполнены.
            if ($this->permissionRBAC !== null && $this->parameterRBAC !== null) {
                // Проверяем доступ пользователя к странице.
                if (!Yii::$app->user->can($this->permissionRBAC, [$this->parameterRBAC => $this->model])) {
                    throw new ForbiddenHttpException(
                        'У вас нет доступа к этой странице.'
                    );
                }
            }
        }

        // Получаем изображение.
        $image = UploadedFile::getInstanceByName('img');

        // Создаем экземпляр класса DynamicModel,
        // определяем атрибуты, проводим проверку.
        $model = new DynamicModel(compact('image'));
        $model->addRule('image', 'required')
            ->addRule('image', 'image', $this->validatorOptions)
            ->validate();

        // Если нет ошибок валидации и
        // изображение успешно сохранено.
        if (!$model->hasErrors() && $this->saveTempImage($model->image)) {
            // Получаем высоту и ширину изображения.
            list($width, $height) = getimagesize(
                $this->tempPath . Yii::$app->getSession()->get('tempImage')
            );
            // Формируем удачный ответ.
            $response = [
                'status' => 'success',
                'url' => $this->savedImage,
                'width' => $width,
                'height' => $height,
            ];

            goto success;
        }

        $response = [
            'status' => 'error',
            'message' => $model->getFirstError('image') !== null ?
                $model->getFirstError('image') :
                'Не удалось загрузить изображение.',
        ];

        success:

        // Возвращаем JSON стоку.
        return Json::encode($response);
    }

    /**
     * Сохраняет загруженное изображение в папку и
     * в сессию пользователя записывает название изображения.
     *
     * @method saveTempImage
     * @param  UploadedFile  $image экземпляр загруженного файла
     * @return bool                 true если изображение успешно сохранено
     */
    private function saveTempImage($image)
    {
        // Если необходимо сгенерировать
        // уникальное название изображения.
        if ($this->uniqueName && $image->extension) {
            $image->name = uniqid('i-' . time()) . '.' . $image->extension;
        }

        // Если в сессии пользователя существует запись.
        if (Yii::$app->getSession()->get('tempImage')) {
            // Удаляем запись и изображение если существет.
            $this->removeTempImage();
        }
        // Сохраняем в сессии пользователя
        // название изображения.
        Yii::$app->getSession()->set('tempImage', $image->name);

        if (!$image->saveAs($this->tempPath . $image->name)) {
            return false;
        }

        $this->savedImage = $this->tempUrl . $image->name;

        return true;
    }

    /**
     * Удаляет изображение из папки и запись из сессии пользователя.
     *
     * @method removeTempImage
     */
    private function removeTempImage()
    {
        $path = $this->tempPath . Yii::$app->getSession()->get('tempImage');
        // Если изображение существует.
        if (is_file($path)) {
            // Удаляем изображение.
            unlink($path);
        }
        // Удаляем запись из сессии пользователя.
        Yii::$app->getSession()->remove('tempImage');
    }
}
