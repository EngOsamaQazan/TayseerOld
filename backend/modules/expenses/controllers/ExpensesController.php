<?php

namespace backend\modules\expenses\controllers;

use backend\modules\financialTransaction\models\FinancialTransaction;
use Yii;
use backend\modules\expenses\models\Expenses;
use backend\modules\expenses\models\ExpensesSearch;
use yii\filters\AccessControl;
use common\helper\Permissions;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use backend\helpers\ExportTrait;

/**
 * ExpensesController implements the CRUD actions for Expenses model.
 */
class ExpensesController extends Controller
{
    use ExportTrait;
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    ['actions' => ['login', 'error'], 'allow' => true],
                    /* ═══ عرض + تصدير ═══ */
                    [
                        'actions' => ['index', 'view', 'export-excel', 'export-pdf'],
                        'allow'   => true,
                        'roles'   => [Permissions::EXP_VIEW],
                    ],
                    /* ═══ إضافة ═══ */
                    [
                        'actions' => ['create'],
                        'allow'   => true,
                        'roles'   => [Permissions::EXP_CREATE],
                    ],
                    /* ═══ تعديل ═══ */
                    [
                        'actions' => ['update'],
                        'allow'   => true,
                        'roles'   => [Permissions::EXP_EDIT],
                    ],
                    /* ═══ حذف ═══ */
                    [
                        'actions' => ['delete', 'bulk-delete'],
                        'allow'   => true,
                        'roles'   => [Permissions::EXP_DELETE],
                    ],
                    /* ═══ إرجاع للحركات المالية ═══ */
                    [
                        'actions' => ['back-to-financial-transaction'],
                        'allow'   => true,
                        'roles'   => [Permissions::EXP_REVERT],
                    ],
                    /* ═══ تسجيل خروج ═══ */
                    ['actions' => ['logout'], 'allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                    'delete' => ['post'],
                    'bulk-delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Expenses models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel  = new ExpensesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        /* ═══ Eager loading للعلاقات — تجنب N+1 queries ═══ */
        $dataProvider->query->with(['category', 'createdBy']);

        /* ═══ حساب الإجماليات من الاستعلام المفلتر ═══ */
        $summaryQuery = (clone $dataProvider->query)
            ->select([
                'COALESCE(SUM(amount), 0) AS total_amount',
                'COUNT(id) AS total_count',
            ])
            ->asArray()
            ->createCommand()
            ->queryOne();

        $totalAmount = (float)($summaryQuery['total_amount'] ?? 0);
        $totalCount  = (int)($summaryQuery['total_count'] ?? 0);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider'  => $dataProvider,
            'totalAmount'   => $totalAmount,
            'totalCount'    => $totalCount,
        ]);
    }


    /**
     * Displays a single Expenses model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "Expenses #" . $id,
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
     * Creates a new Expenses model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new Expenses();


        if ($model->load($request->post()) && $model->save()) {
            Yii::$app->cache->set(Yii::$app->params['key_expenses_contract'],Yii::$app->db->createCommand(Yii::$app->params['expenses_contract_query'])->queryAll(), Yii::$app->params['time_duration']);
            $this->redirect('index');
        } else {

            return $this->render('create', [
                'model' => $model,
            ]);
        }


    }

    /**
     * Updates an existing Expenses model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);

            if ($model->load($request->post()) && $model->save()) {
               Yii::$app->cache->set(Yii::$app->params['key_expenses_contract'],Yii::$app->db->createCommand(Yii::$app->params['expenses_contract_query'])->queryAll(), Yii::$app->params['time_duration']);

                $this->redirect('index');
            } else {
                return $this->render('update', [
                    'model' => $model,
                ]);
            }

    }

    /**
     * Delete an existing Expenses model.
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
     * Delete multiple existing Expenses model.
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
     * Finds the Expenses model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Expenses the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionExportExcel()
    {
        $searchModel  = new ExpensesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with(['category', 'createdBy']);

        return $this->exportData($dataProvider, [
            'title'    => 'المصاريف',
            'filename' => 'expenses',
            'headers'  => ['#', 'التاريخ', 'الوصف', 'التصنيف', 'المبلغ', 'رقم العقد', 'رقم المستند', 'بواسطة', 'ملاحظات'],
            'keys'     => ['id', 'expenses_date', 'description', 'category.name', 'amount', 'contract_id', 'document_number', 'createdBy.username', 'notes'],
            'widths'   => [8, 14, 28, 16, 14, 12, 14, 14, 25],
        ]);
    }

    public function actionExportPdf()
    {
        $searchModel  = new ExpensesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with(['category', 'createdBy']);

        return $this->exportData($dataProvider, [
            'title'    => 'المصاريف',
            'filename' => 'expenses',
            'headers'  => ['#', 'التاريخ', 'الوصف', 'التصنيف', 'المبلغ', 'رقم العقد', 'رقم المستند', 'بواسطة', 'ملاحظات'],
            'keys'     => ['id', 'expenses_date', 'description', 'category.name', 'amount', 'contract_id', 'document_number', 'createdBy.username', 'notes'],
        ], 'pdf');
    }

    protected function findModel($id)
    {
        if (($model = Expenses::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionBackToFinancialTransaction($id, $financial)
    {
        if (!empty($id) && !empty($financial)) {
            Expenses::deleteAll(['id' => $id]);
            FinancialTransaction::updateAll(['is_transfer' => 0], ['id' => $financial]);
        }
        $this->redirect('index');

    }
}
