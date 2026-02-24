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
use backend\helpers\ExportHelper;
use backend\helpers\ExportTrait;
use Yii;

class JudiciaryActionsController extends Controller
{
    use ExportTrait;
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['actions' => ['login', 'error'], 'allow' => true],
                    [
                        'actions' => ['logout', 'index', 'update', 'create', 'delete', 'confirm-delete', 'usage-details', 'view', 'bulk-delete', 'export-excel', 'export-pdf', 'quick-relink'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['post'],
                    'confirm-delete' => ['get', 'post'],
                    'usage-details' => ['get'],
                    'bulk-delete' => ['post'],
                    'quick-relink' => ['post'],
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
     * Confirm Delete — shows migration dialog if records exist
     */
    public function actionConfirmDelete($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);

        $usageCount = (int)(new \yii\db\Query())
            ->from('os_judiciary_customers_actions')
            ->where(['judiciary_actions_id' => $id])
            ->andWhere(['or', ['is_deleted' => 0], ['is_deleted' => null]])
            ->count();

        if ($request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $migrateToId = $request->post('migrate_to_id');

            if ($usageCount > 0 && empty($migrateToId)) {
                $otherActions = \yii\helpers\ArrayHelper::map(
                    JudiciaryActions::find()
                        ->where(['is_deleted' => 0])
                        ->andWhere(['!=', 'id', $id])
                        ->orderBy(['name' => SORT_ASC])
                        ->all(),
                    'id', 'name'
                );
                return [
                    'title' => '<i class="fa fa-trash"></i> حذف الإجراء: ' . Html::encode($model->name),
                    'content' => $this->renderAjax('_confirm_delete', [
                        'model' => $model,
                        'usageCount' => $usageCount,
                        'otherActions' => $otherActions,
                        'error' => 'يجب اختيار إجراء بديل لترحيل السجلات إليه',
                    ]),
                    'footer' => Html::button('إلغاء', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                        Html::button('<i class="fa fa-trash"></i> تأكيد الحذف', [
                            'class' => 'btn btn-danger',
                            'type' => 'submit',
                        ]),
                ];
            }

            if ($usageCount > 0 && !empty($migrateToId) && $migrateToId != $id) {
                Yii::$app->db->createCommand()->update(
                    'os_judiciary_customers_actions',
                    ['judiciary_actions_id' => (int)$migrateToId],
                    ['and',
                        ['judiciary_actions_id' => $id],
                        ['or', ['is_deleted' => 0], ['is_deleted' => null]],
                    ]
                )->execute();
            }

            $model->is_deleted = 1;
            $model->save(false, ['is_deleted']);

            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }

        $otherActions = \yii\helpers\ArrayHelper::map(
            JudiciaryActions::find()
                ->where(['is_deleted' => 0])
                ->andWhere(['!=', 'id', $id])
                ->orderBy(['name' => SORT_ASC])
                ->all(),
            'id', 'name'
        );

        Yii::$app->response->format = Response::FORMAT_JSON;
        return [
            'title' => '<i class="fa fa-trash"></i> حذف الإجراء: ' . Html::encode($model->name),
            'content' => $this->renderAjax('_confirm_delete', [
                'model' => $model,
                'usageCount' => $usageCount,
                'otherActions' => $otherActions,
            ]),
            'footer' => Html::button('إلغاء', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                Html::button('<i class="fa fa-trash"></i> تأكيد الحذف', [
                    'class' => 'btn btn-danger',
                    'type' => 'submit',
                ]),
        ];
    }

    /**
     * Usage Details — shows cases using this judiciary action
     */
    public function actionUsageDetails($id)
    {
        $model = $this->findModel($id);

        $rows = (new \yii\db\Query())
            ->select([
                'jca.id as jca_id',
                'jca.judiciary_id',
                'jca.customers_id',
                'jca.action_date',
                'jca.note',
                'j.judiciary_number',
                'j.contract_id',
                'j.year',
                'c.name as customer_name',
                'ct.type as contract_type',
                'court.name as court_name',
            ])
            ->from('os_judiciary_customers_actions jca')
            ->leftJoin('os_judiciary j', 'j.id = jca.judiciary_id')
            ->leftJoin('os_customers c', 'c.id = jca.customers_id')
            ->leftJoin('os_contracts ct', 'ct.id = j.contract_id')
            ->leftJoin('os_court court', 'court.id = j.court_id')
            ->where(['jca.judiciary_actions_id' => $id])
            ->andWhere(['or', ['jca.is_deleted' => 0], ['jca.is_deleted' => null]])
            ->orderBy(['jca.action_date' => SORT_DESC])
            ->all();

        Yii::$app->response->format = Response::FORMAT_JSON;
        return [
            'title' => '<i class="fa fa-list"></i> استخدامات: ' . Html::encode($model->name) . ' <span style="color:#94A3B8;font-size:13px">(' . count($rows) . ')</span>',
            'content' => $this->renderAjax('_usage_details', [
                'model' => $model,
                'rows' => $rows,
            ]),
            'footer' => Html::button('إغلاق', ['class' => 'btn btn-default', 'data-dismiss' => 'modal']),
        ];
    }

    /**
     * Delete (soft) — direct POST
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
     * Quick Relink — move a document/status to a new parent without opening the full edit form
     */
    public function actionQuickRelink()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        $itemId      = (int)$request->post('item_id');
        $newParentId = (int)$request->post('new_parent_id');
        $oldParentId = (int)$request->post('old_parent_id', 0);

        if (!$itemId || !$newParentId) {
            return ['success' => false, 'message' => 'بيانات ناقصة'];
        }

        $item = $this->findModel($itemId);

        if ($item->action_nature === 'document') {
            if ($oldParentId) {
                $oldParent = $this->findModel($oldParentId);
                $oldDocs = array_filter(array_map('intval', explode(',', $oldParent->allowed_documents ?: '')));
                $oldDocs = array_values(array_diff($oldDocs, [$itemId]));
                $oldParent->allowed_documents = !empty($oldDocs) ? implode(',', $oldDocs) : null;
                $oldParent->save(false, ['allowed_documents']);
            }

            $newParent = $this->findModel($newParentId);
            $newDocs = array_filter(array_map('intval', explode(',', $newParent->allowed_documents ?: '')));
            if (!in_array($itemId, $newDocs)) {
                $newDocs[] = $itemId;
            }
            $newParent->allowed_documents = implode(',', $newDocs);
            $newParent->save(false, ['allowed_documents']);

            $parentIds = array_filter(array_map('intval', explode(',', $item->parent_request_ids ?: '')));
            if ($oldParentId) $parentIds = array_values(array_diff($parentIds, [$oldParentId]));
            if (!in_array($newParentId, $parentIds)) $parentIds[] = $newParentId;
            $item->parent_request_ids = implode(',', $parentIds) ?: null;
            $item->save(false, ['parent_request_ids']);

            return ['success' => true];
        }

        if ($item->action_nature === 'doc_status') {
            $parentIds = array_filter(array_map('intval', explode(',', $item->parent_request_ids ?: '')));
            if ($oldParentId) $parentIds = array_values(array_diff($parentIds, [$oldParentId]));
            if (!in_array($newParentId, $parentIds)) $parentIds[] = $newParentId;
            $item->parent_request_ids = implode(',', $parentIds) ?: null;
            $item->save(false, ['parent_request_ids']);

            return ['success' => true];
        }

        return ['success' => false, 'message' => 'طبيعة الإجراء لا تدعم النقل'];
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

    public function actionExportExcel()
    {
        $searchModel = new JudiciaryActionsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, $this->getExportConfig());
    }

    public function actionExportPdf()
    {
        $searchModel = new JudiciaryActionsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->exportData($dataProvider, $this->getExportConfig(), 'pdf');
    }

    protected function getExportConfig()
    {
        $natureLabels = JudiciaryActions::getNatureList();
        $stageLabels  = JudiciaryActions::getActionTypeList();
        $allNames = \yii\helpers\ArrayHelper::map(
            (new \yii\db\Query())->select(['id', 'name'])->from('os_judiciary_actions')->all(),
            'id', 'name'
        );

        return [
            'title' => 'الإجراءات القضائية',
            'filename' => 'judiciary-actions',
            'headers' => ['#', 'اسم الإجراء', 'الطبيعة', 'المرحلة', 'الكتب المسموحة', 'الحالات المسموحة', 'يتبع لطلبات'],
            'keys' => [
                '#',
                'name',
                function ($model) use ($natureLabels) {
                    $key = $model->action_nature ?? '';
                    return $natureLabels[$key] ?? ($model->action_nature ?? '—');
                },
                function ($model) use ($stageLabels) {
                    return $model->getActionTypeLabel();
                },
                function ($model) use ($allNames) {
                    $ids = $model->getAllowedDocumentIds();
                    if (empty($ids)) return '—';
                    $names = [];
                    foreach ($ids as $id) {
                        $names[] = $allNames[$id] ?? '#' . $id;
                    }
                    return implode('، ', $names);
                },
                function ($model) use ($allNames) {
                    $ids = $model->getAllowedStatusIds();
                    if (empty($ids)) return '—';
                    $names = [];
                    foreach ($ids as $id) {
                        $names[] = $allNames[$id] ?? '#' . $id;
                    }
                    return implode('، ', $names);
                },
                function ($model) use ($allNames) {
                    $ids = $model->getParentRequestIdList();
                    if (empty($ids)) return '—';
                    $names = [];
                    foreach ($ids as $id) {
                        $names[] = $allNames[$id] ?? '#' . $id;
                    }
                    return implode('، ', $names);
                },
            ],
            'widths' => [6, 30, 16, 16, 30, 30, 30],
        ];
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
