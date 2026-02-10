<?php

namespace backend\modules\followUpReport\controllers;

use Yii;
use backend\modules\followUpReport\models\FollowUpReport;
use backend\modules\followUpReport\models\FollowUpReportSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use yii\filters\AccessControl;

/**
 * FollowUpReportController implements the CRUD actions for FollowUpReport model.
 */
class FollowUpReportController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {

        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index', 'update', 'create', 'delete'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
//             [
//            'class' => 'yii\filters\PageCache',
//            'only' => ['index'],
//            'duration' => 60,
//            'variations' => [
//                \Yii::$app->language,
//            ],
//            'dependency' => [
//                'class' => 'yii\caching\DbDependency',
//                'sql' => 'SELECT COUNT(*) FROM os_follow_up_report',
//            ],
//        ],
        ];
    }

    /**
     * Lists all FollowUpReport models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new FollowUpReportSearch();
        $searchModel->reminder = date("Y-m-d");
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $sql = "CREATE OR REPLACE VIEW os_follow_up_report AS SELECT
    c.*,
    f.date_time,
    f.promise_to_pay_at,
    f.reminder,
    IFNULL(payments.total_paid, 0) AS total_paid,
    (
        PERIOD_DIFF(
            DATE_FORMAT(CURDATE(), '%Y%m'),
            DATE_FORMAT(c.first_installment_date, '%Y%m')) + CASE WHEN DAY(CURDATE()) >= DAY(c.first_installment_date) THEN 1 ELSE 0
            END) AS due_installments,
        (
            (
                PERIOD_DIFF(
                    DATE_FORMAT(CURDATE(), '%Y%m'),
                    DATE_FORMAT(c.first_installment_date, '%Y%m')) + CASE WHEN DAY(CURDATE()) >= DAY(c.first_installment_date) THEN 1 ELSE 0
                    END) * c.monthly_installment_value - IFNULL(payments.total_paid, 0)
            ) AS due_amount
        FROM
            os_contracts c
        LEFT JOIN os_follow_up f ON
            f.contract_id = c.id AND f.id =(
            SELECT
                MAX(id)
            FROM
                os_follow_up
            WHERE
                contract_id = c.id
        )
    LEFT JOIN(
        SELECT contract_id,
            SUM(amount) AS total_paid
        FROM
            os_income
        GROUP BY
            contract_id
    ) payments
ON
    c.id = payments.contract_id
WHERE
    c.is_can_not_contact = 0 AND(
        -- عليه مبلغ مستحق حتى اليوم
        (
            (
                (
                    PERIOD_DIFF(
                        DATE_FORMAT(CURDATE(), '%Y%m'),
                        DATE_FORMAT(c.first_installment_date, '%Y%m')) + CASE WHEN DAY(CURDATE()) >= DAY(c.first_installment_date) THEN 1 ELSE 0
                        END) * c.monthly_installment_value
                ) - IFNULL(payments.total_paid, 0)
            ) > 0
            -- أو عليه reminder <= اليوم
            OR(
                f.reminder IS NOT NULL AND f.reminder <= CURDATE())
                -- أو عليه promise_to_pay_at <= اليوم
                OR(
                    f.promise_to_pay_at IS NOT NULL AND f.promise_to_pay_at <= CURDATE())
                )
            ORDER BY
                c.id
            DESC
                ";
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand($sql);
        $command->execute();
        $counter = $searchModel->searchCounter(Yii::$app->request->queryParams);
        $custamerCounter = $searchModel->searchCustamerCounter(Yii::$app->request->queryParams);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'counter'=>$counter,
            'custamerCounter'=>$custamerCounter
        ]);
    }


    /**
     * Displays a single FollowUpReport model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "FollowUpReport #" . $id,
                'content' => $this->renderAjax('view', [
                    'model' => $this->findModel($id),
                ]),
                'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                    Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
            ];
        } else {
            return $this->render('view', [
                'model' => $this->findModel($id),
            ]);
        }
    }

    /**
     * Creates a new FollowUpReport model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new FollowUpReport();

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => "Create new FollowUpReport",
                    'content' => $this->renderAjax('create', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            } else if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title' => "Create new FollowUpReport",
                    'content' => '<span class="text-success">Create FollowUpReport success</span>',
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::a('Create More', ['create'], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])

                ];
            } else {
                return [
                    'title' => "Create new FollowUpReport",
                    'content' => $this->renderAjax('create', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            }
        } else {
            /*
            *   Process for non-ajax request
            */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                return $this->render('create', [
                    'model' => $model,
                ]);
            }
        }

    }

    /**
     * Updates an existing FollowUpReport model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => "Update FollowUpReport #" . $id,
                    'content' => $this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            } else if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title' => "FollowUpReport #" . $id,
                    'content' => $this->renderAjax('view', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
                ];
            } else {
                return [
                    'title' => "Update FollowUpReport #" . $id,
                    'content' => $this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            }
        } else {
            /*
            *   Process for non-ajax request
            */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                return $this->render('update', [
                    'model' => $model,
                ]);
            }
        }
    }

    /**
     * Delete an existing FollowUpReport model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $request = Yii::$app->request;
        $this->findModel($id)->delete();

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        } else {
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }


    }

    /**
     * Delete multiple existing FollowUpReport model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionBulkDelete()
    {
        $request = Yii::$app->request;
        $pks = explode(',', $request->post('pks')); // Array or selected records primary keys
        foreach ($pks as $pk) {
            $model = $this->findModel($pk);
            $model->delete();
        }

        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        } else {
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }

    }

    /**
     * Finds the FollowUpReport model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return FollowUpReport the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = FollowUpReport::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
