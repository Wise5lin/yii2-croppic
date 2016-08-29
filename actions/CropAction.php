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
use yii\base\DynamicModel;
use yii\helpers\FileHelper;
use yii\base\InvalidCallException;
use yii\web\ForbiddenHttpException;
use yii\base\InvalidConfigException;

/**
 * Класс действия для обрезки изображения.
 *
 * Использование:
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
class CropAction extends Action
{
    /**
     * Абсолютный путь к директории в которую
     * будет загружено изображение.
     *
     * @var string
     */
    public $path;
    /**
     * URL указывающий путь к директории в которую
     * будет загружено изображение.
     *
     * @var string
     */
    public $url;
    /**
     * Экземпляр класса который будет использоваться для
     * проверки доступа к странице и сохранения пути или
     * имени изображения в базу данных.
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
     * Указывает, сохранять в базу данных полный путь к
     * изображению 'true' или только название изображения 'false'.
     * По умолчанию сохраняется полный путь к изображению.
     *
     * ПРИМЕР:
     * Берется значение из атрибута 'url' и к нему прибавляется
     * название изображения 'img/user/avatar/img.jpeg'.
     *
     * @var boolean
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

    /**
     * @inheritdoc
     */
    public function init()
    {
        // Если атрибут 'path' не заполнен.
        if ($this->path === null) {
            throw new InvalidConfigException(
                'Атрибут "path" не может быть пустым.'
            );
        }
        $this->path = rtrim(Yii::getAlias($this->path), '/') . '/';

        // Если атрибут 'url' не заполнен.
        if ($this->url === null) {
            throw new InvalidConfigException(
                'Атрибут "url" не может быть пустым.'
            );
        }
        $this->url = rtrim($this->url, '/') . '/';

        // Если директория не существует или не удается её создать.
        if (!FileHelper::createDirectory($this->path)) {
            throw new InvalidCallException(
                'Директория указанная в атрибуте "path" не существует или не может быть создана.'
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->modelAttribute();
        $attributes = $this->getPostData();

        // Создаем экземпляр класса DynamicModel,
        // определяем атрибуты, проводим проверку.
        $model = DynamicModel::validateData(
            $attributes,
            [
                [['imgUrl', 'imgW', 'imgH', 'imgX1', 'imgY1', 'cropW', 'cropH', 'rotation'], 'required'],
                ['imgUrl', 'string'],
                ['imgUrl', 'filter', 'filter' => 'strip_tags'],
                [['imgW', 'imgH', 'imgX1', 'imgY1', 'cropW', 'cropH', 'rotation'], 'double']
            ]
        );

        // Если нет ошибок валидации и
        // изображение успешно сохранено.
        if (!$model->hasErrors() && $this->cropImage($model)) {
            // Если атрибуты 'model' и 'modelAttribute' заполнены.
            if ($this->model !== null && $this->modelAttribute !== null) {
                $modelAttribute = $this->modelAttribute;
                // Присваиваем указанному атрибуту путь или только имя изображения.
                $this->model->$modelAttribute = $this->modelAttributeSavePath ?
                    $this->croppedImage :
                    Yii::$app->getSession()->get('tempImage');

                $this->model->save();
            }

            // Удаляем изображение из папки темп.
            $this->removeTempImage($model->imgUrl);

            // Формируем удачный ответ.
            $response = [
                'status' => 'success',
                'url' => $this->croppedImage,
            ];

            goto success;
        }

        $response = [
            'status' => 'error',
            'message' => 'Не удалось обработать изображение.',
        ];

        success:

        // Возвращаем JSON стоку.
        return Json::encode($response);
    }

    /**
     * Используется для разгрузки метода 'run'.
     *
     * @method modelAttribute
     */
    private function modelAttribute()
    {
        // Если атрибут 'model' заполнен.
        if ($this->model !== null) {
            // Проверяем чтобы он являлся
            // экземпляром класса 'yii\db\BaseActiveRecord'.
            if (!($this->model instanceof \yii\db\BaseActiveRecord)) {
                throw new InvalidConfigException(
                    'Атрибут "model" не является экземпляром класса "yii\db\BaseActiveRecord".'
                );
            }
            // Если атрибут 'modelScenario' заполнен.
            if ($this->modelScenario !== null) {
                $this->model->scenario = $this->modelScenario;
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
    }

    /**
     * Формирует и возварщает массив с параметрами
     * пришедшими по средствам POST.
     *
     * @method getPostData
     * @return array       массив с параметрами
     */
    private function getPostData()
    {
        $request = Yii::$app->request;

        return [
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
            'rotation' => $request->post('rotation')
        ];
    }

    /**
     * Получает загруженное изображение, обрабатывает и сохраняет
     * в указанную папку. Если необходимо, сохраняет путь или
     * имя изображения в базу данных.
     *
     * @method cropImage
     * @param  DynamicModel $model экземпляр класса
     * @return bool                true если изображение успешно сохранено
     */
    private function cropImage(\yii\base\DynamicModel $model)
    {
        // Проверяем что изображение существует
        // и можеты быть прочитано.
        if (!is_readable(Yii::getAlias('@webroot/' . $model->imgUrl))) {
            throw new InvalidCallException(
                'Изображение не существует или не может быть прочитано.'
            );
        }

        \yii\imagine\Image::$driver = [
            \yii\imagine\Image::DRIVER_IMAGICK,
            \yii\imagine\Image::DRIVER_GMAGICK,
            \yii\imagine\Image::DRIVER_GD2,
        ];

        $imagine = new \yii\imagine\Image;

        // Если атрибуты 'model' и 'modelAttribute' заполнены.
        if ($this->model !== null && $this->modelAttribute !== null) {
            // Удаляем предыдущее изображение.
            $this->removeImage();
        }

        // Обрабатываем и сохраняем изображение.
        $image = $imagine->getImagine()->open(
            Yii::getAlias('@webroot/' . $model->imgUrl)
        )
        ->resize(new \Imagine\Image\Box($model->imgW, $model->imgH))
        ->rotate($model->rotation)
        ->crop(
            new \Imagine\Image\Point($model->imgX1, $model->imgY1),
            new \Imagine\Image\Box($model->cropW, $model->cropH)
        )
        ->save($this->path . Yii::$app->getSession()->get('tempImage'));

        if (!$image) {
            return false;
        }

        $this->croppedImage = $this->url . Yii::$app->getSession()->get('tempImage');

        return true;
    }

    /**
     * Удаляет предыдущее изображение,
     * перед тем как сохранить новое.
     *
     * @method removeImage
     */
    private function removeImage()
    {
        $modelAttribute = $this->modelAttribute;
        $path = $this->modelAttributeSavePath ?
            Yii::getAlias('@webroot/' . $this->model->$modelAttribute) :
            $this->path . $this->model->$modelAttribute;

        // Если изображение существует.
        if (is_file($path)) {
            // Удаляем изображение.
            unlink($path);
        }
    }

    /**
     * Удаляет изображение из папки и запись из сессии пользователя.
     *
     * @method removeTempImage
     */
    private function removeTempImage($imgUrl)
    {
        $path = Yii::getAlias('@webroot/' . $imgUrl);
        // Если изображение существует.
        if (is_file($path)) {
            // Удаляем изображение.
            unlink($path);
        }
        // Удаляем запись из сессии пользователя.
        Yii::$app->getSession()->remove('tempImage');
    }
}
