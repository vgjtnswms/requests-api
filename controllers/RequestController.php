<?php

namespace app\controllers;

use app\models\Request;
use app\models\RequestFilterForm;
use app\services\MailService;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\AccessControl;
use yii\filters\Cors;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;
use yii\web\Response;

class RequestController extends Controller
{
    /**
     * @var MailService
     */
    private $mailService;

    /**
     * @param mixed $id
     * @param mixed $module
     * @param MailService $mailService
     * @param mixed $config
     */
    public function __construct($id, $module, MailService $mailService, $config = [])
    {
        $this->mailService = $mailService;
        parent::__construct($id, $module, $config);
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['http://requests-api'],
                'Access-Control-Request-Method' => ['POST', 'GET', 'PUT'],
                'Access-Control-Allow-Headers' => ['Content-Type', 'Authorization'],
                'Access-Control-Allow-Credentials' => true,
            ],
        ];

        $behaviors['access'] = [
            'class' => AccessControl::class,
            'only' => ['index', 'update'],
            'rules' => [
                [
                    'allow' => true,
                    'roles' => ['@'],
                    'actions' => ['index', 'update'],
                ]
            ],
        ];

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'only' => ['index', 'update'],
        ];

        return $behaviors;
    }

    /**
     * POST /requests
     *
     * Создание новой заяви.
     *
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Request();
        $model->load(Yii::$app->request->post(), '');
        $model->status = Request::STATUS_ACTIVE;
        $model->created_at = date('Y-m-d H:i:s');
        $model->updated_at = $model->created_at;

        if ($model->save()) {
            return $model;
        }

        return $model->getErrors();
    }

    /**
     * GET /requests
     *
     * Получение заявки с опциональными фильтрами.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $filterForm = new RequestFilterForm();
        if (!$filterForm->load(Yii::$app->request->queryParams, '')) {
            throw new BadRequestHttpException('Invalid filter parameters.');
        }
        if (!$filterForm->validate()) {
            return $filterForm->getErrors();
        }

        return $filterForm->getRequests();
    }

    /**
     * PUT /requests/{id}
     *
     * Обновление заявки.
     *
     * @param mixed $id
     *
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionUpdate($id)
    {
        $model = Request::findOne($id);
        if (!$model) {
            throw new BadRequestHttpException('Request not found.');
        }
        if ($model->status === Request::STATUS_RESOLVED) {
            throw new ConflictHttpException('Request has already been processed.');
        }

        $model->comment = Yii::$app->request->getBodyParam("comment");
        $model->status = Request::STATUS_RESOLVED;
        $model->updated_at = date('Y-m-d H:i:s');

        if ($model->save()) {
            $this->mailService->send(
                $model->email,
                'Your request has been updated',
                "Status: {$model->status}\nComment: {$model->comment}"
            );
            return $model;
        }

        return $model->getErrors();
    }

    /**
     * @return mixed
     */
    public function actionError()
    {
        if (($exception = Yii::$app->errorHandler->exception) !== null) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            return [
                'error' => true,
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ];
        }
    }
}