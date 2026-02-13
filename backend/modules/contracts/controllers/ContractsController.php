<?php

namespace backend\modules\contracts\controllers;

use Yii;
use yii\helpers\Html;
use yii\web\Response;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;

use backend\models\Model;
use common\components\notificationComponent;
use backend\modules\followUp\models\FollowUp;
use backend\modules\customers\models\Customers;
use backend\modules\contracts\models\Contracts;
use backend\modules\contracts\models\ContractsSearch;
use backend\modules\customers\models\ContractsCustomers;
use backend\modules\followUp\models\FollowUpConnectionReports;
use backend\modules\inventoryItems\models\ContractInventoryItem;
use backend\modules\inventoryItems\models\InventoryItems;
use backend\modules\inventoryItems\models\InventorySerialNumber;
use backend\modules\inventoryItemQuantities\models\InventoryItemQuantities;
use backend\modules\inventoryStockLocations\models\InventoryStockLocations;
use backend\modules\contractDocumentFile\models\ContractDocumentFile;
use backend\modules\notification\models\Notification;
use backend\modules\companies\models\Companies;
use backend\modules\contracts\models\PromissoryNote;

class ContractsController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['actions' => ['login', 'error'], 'allow' => true],
                    [
                        'actions' => [
                            'logout', 'index', 'update', 'create', 'delete',
                            'is-connect', 'is-not-connect',
                            'print-first-page', 'print-second-page', 'print-preview',
                            'finish', 'finish-contract', 'cancel', 'cancel-contract',
                            'legal-department', 'to-legal-department', 'return-to-continue',
                            'view', 'index-legal-department', 'convert-to-manager',
                            'is-read', 'chang-follow-up',
                            'lookup-serial',
                        ],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['logout' => ['post'], 'delete' => ['post']],
            ],
        ];
    }

    /* ══════════════════════════════════════════════════════════════
     *  قوائم — Index
     * ══════════════════════════════════════════════════════════════ */

    public function actionIndex()
    {
        $searchModel = new ContractsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataCount = $searchModel->searchcounter(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'dataCount'    => $dataCount,
        ]);
    }

    public function actionIndexLegalDepartment()
    {
        $searchModel = new ContractsSearch();
        $dataProvider = $searchModel->searchLegalDepartment(Yii::$app->request->queryParams);
        $dataCount = $searchModel->searchLegalDepartmentCount(Yii::$app->request->queryParams);

        return $this->render('index-legal-department', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'dataCount'    => $dataCount,
        ]);
    }

    public function actionLegalDepartment()
    {
        return $this->actionIndexLegalDepartment();
    }

    /* ══════════════════════════════════════════════════════════════
     *  عرض — View
     * ══════════════════════════════════════════════════════════════ */

    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title'   => "العقد #$id",
                'content' => $this->renderAjax('view', ['model' => $this->findModel($id)]),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal'])
                           . Html::a('تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
            ];
        }
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /* ══════════════════════════════════════════════════════════════
     *  إنشاء عقد — Create
     * ══════════════════════════════════════════════════════════════ */

    public function actionCreate()
    {
        $model = new Contracts();
        $model->status    = Contracts::STATUS_ACTIVE;
        $model->seller_id = Yii::$app->user->id;
        $model->type      = 'normal';
        $model->Date_of_sale = date('Y-m-d');

        if (defined('\backend\modules\contracts\models\Contracts::DEFAUULT_TOTAL_VALUE'))
            $model->total_value = Contracts::DEFAUULT_TOTAL_VALUE;
        if (defined('\backend\modules\contracts\models\Contracts::MONTHLY_INSTALLMENT_VALE'))
            $model->monthly_installment_value = Contracts::MONTHLY_INSTALLMENT_VALE;

        if (!Yii::$app->request->isPost) {
            $customerId = Yii::$app->request->get('id');
            if ($customerId) {
                $model->customer_id = $customerId;
            }
            return $this->render('create', $this->buildFormParams($model));
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$model->load(Yii::$app->request->post()) || !$model->save(false)) {
                throw new \Exception('فشل حفظ العقد');
            }

            // ── حفظ الأجهزة (سيريال) ──
            $this->saveSerialItems($model);

            // ── حفظ الأجهزة (يدوي — بدون سيريال) ──
            $this->saveManualItems($model);

            // ── حفظ العملاء والكفلاء ──
            $this->saveContractCustomers($model);

            // ── إنشاء متابعة أولية ──
            $this->createInitialFollowUp($model);

            // ── إنشاء ملف مستندات ──
            $docFile = new ContractDocumentFile();
            $docFile->document_type = 'contract file';
            $docFile->contract_id = $model->id;
            $docFile->save(false);

            // ── إشعار ──
            Yii::$app->notifications->sendByRule(
                ['Manager'],
                'contracts/update?id=' . $model->id,
                Notification::GENERAL,
                Yii::t('app', 'إنشاء عقد رقم'),
                Yii::t('app', 'إنشاء عقد رقم') . $model->id,
                Yii::$app->user->id
            );

            // ── تحديث الكاش ──
            $this->refreshContractCaches();

            $transaction->commit();

            if (isset($_POST['print'])) {
                return $this->redirect(['print-preview', 'id' => $model->id]);
            }
            return $this->redirect(['index']);

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'حدث خطأ: ' . $e->getMessage());
            return $this->render('create', $this->buildFormParams($model));
        }
    }

    /* ══════════════════════════════════════════════════════════════
     *  تعديل عقد — Update
     * ══════════════════════════════════════════════════════════════ */

    public function actionUpdate($id, $notificationID = 0)
    {
        if ($notificationID) {
            Yii::$app->notifications->setReaded($notificationID);
        }

        $model = $this->findModel($id);

        if (!Yii::$app->request->isPost) {
            // تحميل بيانات العملاء الحالية
            if ($model->type === 'solidarity') {
                $model->customers_ids = $model->customers;
            } else {
                $model->customer_id = $model->customers;
                $model->guarantors_ids = $model->guarantor;
            }
            return $this->render('update', $this->buildFormParams($model));
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$model->load(Yii::$app->request->post()) || !$model->save(false)) {
                throw new \Exception('فشل حفظ العقد');
            }

            // ── تحديث الأجهزة (إزالة القديمة + إضافة الجديدة) ──
            $this->updateSerialItems($model);

            // ── تحديث الأجهزة اليدوية ──
            $this->updateManualItems($model);

            // ── تحديث العملاء والكفلاء ──
            ContractsCustomers::deleteAll(['contract_id' => $id]);
            $this->saveContractCustomers($model);

            // ── إشعار ──
            Yii::$app->notifications->sendByRule(
                ['Manager'],
                'contracts/update?id=' . $model->id,
                Notification::GENERAL,
                Yii::t('app', 'تم تعديل عقد رقم') . $model->id,
                Yii::t('app', 'تعديل عقد رقم') . $model->id . ' من قبل ' . Yii::$app->user->identity['username'],
                Yii::$app->user->id
            );

            $this->refreshContractCaches();
            $transaction->commit();

            if (isset($_POST['print'])) {
                return $this->redirect(['print-preview', 'id' => $model->id]);
            }
            return $this->redirect(['update', 'id' => $model->id]);

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'حدث خطأ: ' . $e->getMessage());
            if ($model->type === 'solidarity') {
                $model->customers_ids = $model->customers;
            } else {
                $model->customer_id = $model->customers;
                $model->guarantors_ids = $model->guarantor;
            }
            return $this->render('update', $this->buildFormParams($model));
        }
    }

    /* ══════════════════════════════════════════════════════════════
     *  البحث بالرقم التسلسلي — AJAX
     * ══════════════════════════════════════════════════════════════ */

    public function actionLookupSerial($serial = '')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $serial = trim($serial);

        if ($serial === '') {
            return ['success' => false, 'message' => 'أدخل الرقم التسلسلي'];
        }

        $model = InventorySerialNumber::find()
            ->where(['serial_number' => $serial])
            ->with('item')
            ->one();

        if (!$model) {
            return ['success' => false, 'message' => 'الرقم التسلسلي غير موجود في النظام'];
        }

        if ($model->status !== InventorySerialNumber::STATUS_AVAILABLE) {
            $labels = InventorySerialNumber::getStatusList();
            return ['success' => false, 'message' => 'الجهاز غير متاح — الحالة: ' . ($labels[$model->status] ?? $model->status)];
        }

        return [
            'success' => true,
            'data'    => [
                'id'            => $model->id,
                'serial_number' => $model->serial_number,
                'item_id'       => $model->item_id,
                'item_name'     => $model->item ? $model->item->item_name : 'غير معروف',
                'status'        => $model->status,
            ],
        ];
    }

    /* ══════════════════════════════════════════════════════════════
     *  حذف — Delete
     * ══════════════════════════════════════════════════════════════ */

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }
        return $this->redirect(['index']);
    }

    public function actionBulkdelete()
    {
        $pks = explode(',', Yii::$app->request->post('pks'));
        foreach ($pks as $pk) {
            $this->findModel($pk)->delete();
        }
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }
        return $this->redirect(['index']);
    }

    /* ══════════════════════════════════════════════════════════════
     *  إجراءات العقد — Status Actions
     * ══════════════════════════════════════════════════════════════ */

    public function actionFinish()
    {
        $id = Yii::$app->request->post('contract_id');
        $this->findModel($id)->finish();
        Yii::$app->session->addFlash('success', 'تم إنهاء العقد بنجاح');
        return $this->redirect(['index']);
    }

    public function actionFinishContract($contract_id)
    {
        $this->findModel($contract_id)->finish();
        Yii::$app->session->addFlash('success', 'تم إنهاء العقد بنجاح');
        return $this->redirect(['index']);
    }

    public function actionCancel()
    {
        $id = Yii::$app->request->post('contract_id');
        Contracts::updateAll(['status' => 'canceled'], ['id' => $id]);
        Yii::$app->session->addFlash('success', 'تم إلغاء العقد');
        return $this->redirect(['index']);
    }

    public function actionCancelContract($contract_id)
    {
        Contracts::updateAll(['status' => 'canceled'], ['id' => $contract_id]);
        Yii::$app->session->addFlash('success', 'تم إلغاء العقد');
        return $this->redirect(['index']);
    }

    public function actionReturnToContinue($id)
    {
        if ($id > 0) {
            Contracts::updateAll(['status' => 'active'], ['id' => $id]);
        }
        return $this->redirect(['/follow-up-report/index']);
    }

    public function actionToLegalDepartment($id)
    {
        $this->findModel($id)->legalDepartment();
        Yii::$app->session->addFlash('success', 'تم تحويل العقد إلى الدائرة القانونية');
        Yii::$app->notifications->sendByRule(
            ['Manager'], '/follow-up?contract_id=' . $id,
            Notification::GENERAL,
            Yii::t('app', 'تحويل عقد الى الدائره القانونيه'),
            Yii::t('app', 'تحويل عقد ' . $id . ' الى الدائره القانونيه'),
            Yii::$app->user->id
        );
        return $this->redirect(['index']);
    }

    public function actionConvertToManager($id)
    {
        Yii::$app->notifications->sendByRule(
            ['Manager'], '/follow-up?contract_id=' . $id,
            Notification::GENERAL,
            Yii::t('app', 'مراجعة متابعه'),
            Yii::t('app', 'مراجعة متابعه للعقد رقم') . $id,
            Yii::$app->user->id
        );
        return $this->redirect(['index']);
    }

    public function actionChangFollowUp()
    {
        $id = Yii::$app->request->post('id');
        $followedBy = Yii::$app->request->post('followedBy');
        Contracts::updateAll(['followed_by' => (int)$followedBy], ['id' => (int)$id]);
    }

    public function actionIsNotConnect($contract_id)
    {
        Yii::$app->db->createCommand()
            ->update('{{%contracts}}', ['is_can_not_contact' => 1], 'id = ' . (int)$contract_id)
            ->execute();
        return $this->redirect(['/followUp/follow-up/index', 'contract_id' => $contract_id]);
    }

    public function actionIsConnect($contract_id)
    {
        Yii::$app->db->createCommand()
            ->update('{{%contracts}}', ['is_can_not_contact' => 0], 'id = ' . (int)$contract_id)
            ->execute();
        return $this->redirect(['/followUp/follow-up/index', 'contract_id' => $contract_id]);
    }

    /* ══════════════════════════════════════════════════════════════
     *  الطباعة — Print
     * ══════════════════════════════════════════════════════════════ */

    public function actionPrintPreview($id)
    {
        $this->layout = false;
        $model = $this->findModel($id);

        /* إنشاء 3 كمبيالات للعقد تلقائياً إذا لم تكن موجودة */
        $kambAmount = ($model->total_value ?: 0) * 1.15;
        $notes = PromissoryNote::ensureNotesExist($model->id, $kambAmount, $model->due_date);

        return $this->renderPartial('_print_preview', [
            'model' => $model,
            'notes' => $notes,
        ]);
    }

    public function actionPrintFirstPage($id)
    {
        $this->layout = false;
        $model = $this->findModel($id);
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title'   => "العقد #$id",
                'content' => $this->renderAjax('_contract_print', ['model' => $model]),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal'])
                           . Html::a('تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
            ];
        }
        return $this->renderPartial('_contract_print', ['model' => $model]);
    }

    public function actionPrintSecondPage($id)
    {
        $this->layout = false;
        $model = $this->findModel($id);
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title'   => "العقد #$id",
                'content' => $this->renderAjax('_draft_print', ['model' => $model]),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal'])
                           . Html::a('تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
            ];
        }
        return $this->renderPartial('_draft_print', ['model' => $model]);
    }

    /* ══════════════════════════════════════════════════════════════
     *  دوال مساعدة — Helpers
     * ══════════════════════════════════════════════════════════════ */

    /**
     * بناء مصفوفة البيانات اللازمة للفورم
     */
    private function buildFormParams($model)
    {
        // العملاء يتم تحميلهم عبر AJAX — لا حاجة لتحميلهم هنا
        $companies = ArrayHelper::map(Companies::find()->asArray()->all(), 'id', 'name');
        $inventoryItems = ArrayHelper::map(InventoryItems::find()->asArray()->all(), 'id', 'item_name');

        // تحميل الأرقام التسلسلية المربوطة بالعقد (لوضع التعديل)
        $scannedSerials = [];
        if (!$model->isNewRecord) {
            $items = ContractInventoryItem::find()
                ->where(['contract_id' => $model->id])
                ->andWhere(['IS NOT', 'serial_number_id', null])
                ->all();
            foreach ($items as $item) {
                $serial = InventorySerialNumber::findOne($item->serial_number_id);
                if ($serial) {
                    $invItem = InventoryItems::findOne($serial->item_id);
                    $scannedSerials[] = [
                        'id'            => $serial->id,
                        'serial_number' => $serial->serial_number,
                        'item_name'     => $invItem ? $invItem->item_name : '',
                        'item_id'       => $serial->item_id,
                    ];
                }
            }
        }

        return [
            'model'          => $model,
            'customers'      => [],  // يتم تحميل العملاء عبر AJAX — نمرر مصفوفة فارغة لتجنب خطأ Undefined variable
            'companies'      => $companies,
            'inventoryItems' => $inventoryItems,
            'scannedSerials' => $scannedSerials,
        ];
    }

    /**
     * حفظ بنود السيريال عند إنشاء عقد
     */
    private function saveSerialItems($model)
    {
        $serialIds = Yii::$app->request->post('serial_ids', []);
        foreach ($serialIds as $serialId) {
            $serial = InventorySerialNumber::findOne((int)$serialId);
            if (!$serial || $serial->status !== InventorySerialNumber::STATUS_AVAILABLE) continue;

            $ci = new ContractInventoryItem();
            $ci->contract_id = $model->id;
            $ci->item_id = $serial->item_id;
            $ci->serial_number_id = $serial->id;
            $ci->code = $serial->serial_number;
            $ci->save(false);

            // تحديث حالة السيريال إلى "مباع"
            $serial->status = InventorySerialNumber::STATUS_SOLD;
            $serial->contract_id = $model->id;
            $serial->sold_at = time();
            $serial->save(false);

            // تحديث كمية المخزون
            $this->deductInventoryQuantity($model, $serial->item_id);
        }
    }

    /**
     * حفظ بنود يدوية (بدون سيريال)
     */
    private function saveManualItems($model)
    {
        $manualItemIds = Yii::$app->request->post('manual_item_ids', []);
        foreach ($manualItemIds as $itemId) {
            $ci = new ContractInventoryItem();
            $ci->contract_id = $model->id;
            $ci->item_id = (int)$itemId;
            $ci->save(false);

            $this->deductInventoryQuantity($model, (int)$itemId);
        }
    }

    /**
     * تحديث بنود السيريال عند تعديل عقد
     */
    private function updateSerialItems($model)
    {
        $newSerialIds = array_map('intval', Yii::$app->request->post('serial_ids', []));

        // الأرقام التسلسلية الحالية
        $oldItems = ContractInventoryItem::find()
            ->where(['contract_id' => $model->id])
            ->andWhere(['IS NOT', 'serial_number_id', null])
            ->all();

        $oldSerialIds = array_map(function($i) { return (int)$i->serial_number_id; }, $oldItems);

        // إزالة السيريالات اللي اتشالت
        $toRelease = array_diff($oldSerialIds, $newSerialIds);
        foreach ($toRelease as $sid) {
            $serial = InventorySerialNumber::findOne($sid);
            if ($serial) {
                $serial->status = InventorySerialNumber::STATUS_AVAILABLE;
                $serial->contract_id = null;
                $serial->sold_at = null;
                $serial->save(false);
            }
            ContractInventoryItem::deleteAll([
                'contract_id' => $model->id,
                'serial_number_id' => $sid,
            ]);
        }

        // إضافة السيريالات الجديدة
        $toAdd = array_diff($newSerialIds, $oldSerialIds);
        foreach ($toAdd as $sid) {
            $serial = InventorySerialNumber::findOne($sid);
            if (!$serial || $serial->status !== InventorySerialNumber::STATUS_AVAILABLE) continue;

            $ci = new ContractInventoryItem();
            $ci->contract_id = $model->id;
            $ci->item_id = $serial->item_id;
            $ci->serial_number_id = $serial->id;
            $ci->code = $serial->serial_number;
            $ci->save(false);

            $serial->status = InventorySerialNumber::STATUS_SOLD;
            $serial->contract_id = $model->id;
            $serial->sold_at = time();
            $serial->save(false);

            $this->deductInventoryQuantity($model, $serial->item_id);
        }
    }

    /**
     * تحديث بنود يدوية عند التعديل
     */
    private function updateManualItems($model)
    {
        // حذف البنود اليدوية القديمة
        ContractInventoryItem::deleteAll([
            'contract_id' => $model->id,
            'serial_number_id' => null,
        ]);
        // إضافة الجديدة
        $this->saveManualItems($model);
    }

    /**
     * خصم كمية من المخزون
     */
    private function deductInventoryQuantity($model, $itemId)
    {
        $location = InventoryStockLocations::find()
            ->andWhere(['company_id' => $model->company_id])
            ->one();

        $qty = new InventoryItemQuantities();
        $qty->item_id = $itemId;
        $qty->suppliers_id = $model->company_id;
        $qty->locations_id = $location ? $location->id : 0;
        $qty->quantity = 1;
        $qty->save(false);
    }

    /**
     * حفظ العملاء والكفلاء
     */
    private function saveContractCustomers($model)
    {
        if ($model->type === 'solidarity') {
            foreach ((array)$model->customers_ids as $customerId) {
                $cc = new ContractsCustomers();
                $cc->contract_id = $model->id;
                $cc->customer_id = $customerId;
                $cc->customer_type = 'client';
                $cc->save(false);
            }
        } else {
            // العميل الأساسي
            $cc = new ContractsCustomers();
            $cc->contract_id = $model->id;
            $cc->customer_id = $model->customer_id;
            $cc->customer_type = 'client';
            $cc->save(false);

            // الكفلاء
            if (!empty($model->guarantors_ids)) {
                foreach ((array)$model->guarantors_ids as $gid) {
                    $gc = new ContractsCustomers();
                    $gc->contract_id = $model->id;
                    $gc->customer_id = $gid;
                    $gc->customer_type = 'guarantor';
                    $gc->save(false);
                }
            }
        }
    }

    /**
     * إنشاء متابعة أولية
     */
    private function createInitialFollowUp($model)
    {
        $fu = new FollowUp();
        $fu->contract_id = $model->id;
        $fu->date_time = date('Y-m-d H:i:s');
        $fu->notes = 'إضافة آلية';
        $fu->feeling = 'normal';
        $fu->connection_goal = 1;
        $fu->reminder = $model->first_installment_date;
        $fu->created_by = Yii::$app->user->id;
        $fu->save(false);
    }

    /**
     * تحديث كاش العقود
     */
    private function refreshContractCaches()
    {
        Yii::$app->cache->set(
            Yii::$app->params['key_contract_id'],
            Yii::$app->db->createCommand(Yii::$app->params['contract_id_query'])->queryAll(),
            Yii::$app->params['time_duration']
        );
        Yii::$app->cache->set(
            Yii::$app->params['key_contract_status'],
            Yii::$app->db->createCommand(Yii::$app->params['contract_status_query'])->queryAll(),
            Yii::$app->params['time_duration']
        );
    }

    /**
     * إيجاد موديل العقد
     */
    protected function findModel($id)
    {
        $model = Contracts::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('الصفحة المطلوبة غير موجودة.');
        }
        return $model;
    }
}
