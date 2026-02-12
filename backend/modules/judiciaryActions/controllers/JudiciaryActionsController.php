<?php

namespace backend\modules\judiciaryActions\controllers;

use backend\modules\judiciaryActions\models\JudiciaryActions;
use backend\modules\judiciaryActions\models\JudiciaryActionsSearch;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use Yii;

class JudiciaryActionsController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['actions' => ['login', 'error'], 'allow' => true],
                    [
                        'actions' => ['logout', 'index', 'update', 'create', 'delete', 'view', 'bulk-delete'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'bulk-delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Index — list all actions
     */
    public function actionIndex()
    {
        $searchModel = new JudiciaryActionsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $searchCounter = $searchModel->searchCounter(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'searchCounter' => $searchCounter,
        ]);
    }

    /**
     * View
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => 'عرض الإجراء #' . $id,
                'content' => $this->renderAjax('view', ['model' => $this->findModel($id)]),
                'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                    Html::a('<i class="fa fa-pencil"></i> تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
            ];
        }
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /**
     * Create
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new JudiciaryActions();

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            if ($request->isGet) {
                return [
                    'title' => '<i class="fa fa-plus"></i> إضافة إجراء قضائي جديد',
                    'content' => $this->renderAjax('create', ['model' => $model]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                        Html::button('<i class="fa fa-plus"></i> إضافة', ['class' => 'btn btn-primary', 'type' => 'submit']),
                ];
            }

            if ($model->load($request->post())) {
                // Sync relationship fields from POST
                $this->syncRelationships($model, $request);

                if ($model->save()) {
                    return [
                        'forceReload' => '#crud-datatable-pjax',
                        'title' => 'إضافة إجراء قضائي',
                        'content' => '<div style="text-align:center;padding:20px"><i class="fa fa-check-circle" style="font-size:48px;color:#10B981"></i><h4 style="margin-top:12px;color:#1E293B">تم إضافة الإجراء بنجاح</h4><p style="color:#64748B">' . Html::encode($model->name) . '</p></div>',
                        'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                            Html::a('<i class="fa fa-plus"></i> إضافة آخر', ['create'], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
                    ];
                }
            }

            return [
                'title' => '<i class="fa fa-plus"></i> إضافة إجراء قضائي جديد',
                'content' => $this->renderAjax('create', ['model' => $model]),
                'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                    Html::button('<i class="fa fa-plus"></i> إضافة', ['class' => 'btn btn-primary', 'type' => 'submit']),
            ];
        }

        // Non-AJAX
        if ($model->load($request->post())) {
            $this->syncRelationships($model, $request);
            if ($model->save()) {
                return $this->redirect(['index']);
            }
        }
        return $this->render('create', ['model' => $model]);
    }

    /**
     * Update
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            if ($request->isGet) {
                return [
                    'title' => '<i class="fa fa-pencil"></i> تعديل: ' . Html::encode($model->name),
                    'content' => $this->renderAjax('update', ['model' => $model]),
                    'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                        Html::button('<i class="fa fa-save"></i> حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
                ];
            }

            if ($model->load($request->post())) {
                $this->syncRelationships($model, $request);

                if ($model->save()) {
                    return [
                        'forceReload' => '#crud-datatable-pjax',
                        'title' => 'تعديل الإجراء',
                        'content' => '<div style="text-align:center;padding:20px"><i class="fa fa-check-circle" style="font-size:48px;color:#10B981"></i><h4 style="margin-top:12px;color:#1E293B">تم حفظ التعديلات</h4></div>',
                        'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']),
                    ];
                }
            }

            return [
                'title' => '<i class="fa fa-pencil"></i> تعديل: ' . Html::encode($model->name),
                'content' => $this->renderAjax('update', ['model' => $model]),
                'footer' => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                    Html::button('<i class="fa fa-save"></i> حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
            ];
        }

        // Non-AJAX
        if ($model->load($request->post())) {
            $this->syncRelationships($model, $request);
            if ($model->save()) {
                return $this->redirect(['index']);
            }
        }
        return $this->render('update', ['model' => $model]);
    }

    /**
     * Delete (soft)
     */
    public function actionDelete($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);
        $model->is_deleted = 1;
        $model->save(false, ['is_deleted']);

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }
        return $this->redirect(['index']);
    }

    /**
     * Bulk Delete (soft)
     */
    public function actionBulkDelete()
    {
        $request = Yii::$app->request;
        $pks = explode(',', $request->post('pks'));
        foreach ($pks as $pk) {
            $model = $this->findModel($pk);
            $model->is_deleted = 1;
            $model->save(false, ['is_deleted']);
        }

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }
        return $this->redirect(['index']);
    }

    /**
     * Sync relationship fields from POST checkboxes to model comma-separated fields
     */
    private function syncRelationships($model, $request)
    {
        $nature = $model->action_nature;

        // allowed_documents & allowed_statuses (for requests)
        if ($nature === 'request') {
            $docs = $request->post('rel_allowed_documents', []);
            $model->allowed_documents = is_array($docs) && !empty($docs) ? implode(',', $docs) : null;

            $stats = $request->post('rel_allowed_statuses', []);
            $model->allowed_statuses = is_array($stats) && !empty($stats) ? implode(',', $stats) : null;

            $model->parent_request_ids = null;
        }

        // parent_request_ids (for documents and doc_statuses)
        if ($nature === 'document' || $nature === 'doc_status') {
            $parents = $request->post('rel_parent_request_ids', []);
            $model->parent_request_ids = is_array($parents) && !empty($parents) ? implode(',', $parents) : null;

            $model->allowed_documents = null;
            $model->allowed_statuses = null;
        }

        // process — no relationships
        if ($nature === 'process') {
            $model->allowed_documents = null;
            $model->allowed_statuses = null;
            $model->parent_request_ids = null;
        }
    }

    /**
     * Find model
     */
    protected function findModel($id)
    {
        if (($model = JudiciaryActions::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('الصفحة المطلوبة غير موجودة');
    }
}
