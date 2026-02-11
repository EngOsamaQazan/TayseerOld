<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  كونترولر أوامر الشراء v2 — مصلح ومعاد بناؤه
 *  ─────────────────────────────────────────────────────────────
 *  إصلاحات: AccessControl, var_dump, undefined vars, quantity logic
 * ═══════════════════════════════════════════════════════════════
 */

namespace backend\modules\inventoryInvoices\controllers;

use Yii;
use backend\modules\inventoryInvoices\models\InventoryInvoices;
use backend\modules\inventoryInvoices\models\InventoryInvoicesSearch;
use backend\modules\inventoryItemQuantities\models\InventoryItemQuantities;
use backend\modules\itemsInventoryInvoices\models\ItemsInventoryInvoices;
use backend\modules\inventoryItems\models\StockMovement;
use common\models\Model;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

class InventoryInvoicesController extends Controller
{
    /* مصلح: كان بدون AccessControl */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['actions' => ['login', 'error'], 'allow' => true],
                    [
                        'actions' => ['logout', 'index', 'view', 'create', 'update', 'delete', 'bulk-delete'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete'      => ['post'],
                    'bulk-delete' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new InventoryInvoicesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title'   => 'أمر شراء #' . $id,
                'content' => $this->renderAjax('view', ['model' => $model]),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                             Html::a('تعديل', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote']),
            ];
        }
        return $this->render('view', ['model' => $model]);
    }

    /**
     * إنشاء أمر شراء جديد — معاد بناؤه بالكامل
     * يحفظ الفاتورة + بنود الأصناف + يحدث الكميات + يسجل الحركات
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new InventoryInvoices();
        $itemsInventoryInvoices = [new ItemsInventoryInvoices];

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title'   => 'أمر شراء جديد',
                    'content' => $this->renderAjax('create', ['model' => $model]),
                    'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                                 Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
                ];
            }
            if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title'       => 'أمر شراء جديد',
                    'content'     => '<span class="text-success">تم إنشاء أمر الشراء بنجاح</span>',
                    'footer'      => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']),
                ];
            }
            return [
                'title'   => 'أمر شراء جديد',
                'content' => $this->renderAjax('create', ['model' => $model]),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                             Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
            ];
        }

        /* Non-AJAX: full form with line items */
        if ($model->load($request->post())) {
            $itemsInventoryInvoices = Model::createMultiple(ItemsInventoryInvoices::class);
            Model::loadMultiple($itemsInventoryInvoices, $request->post());

            $valid = $model->validate();
            $valid = Model::validateMultiple($itemsInventoryInvoices) && $valid;

            if ($valid) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    if ($model->save(false)) {
                        $totalAmount = 0;

                        foreach ($itemsInventoryInvoices as $lineItem) {
                            $lineItem->inventory_invoices_id = $model->id;
                            $lineItem->total_amount = $lineItem->single_price * $lineItem->number;
                            $totalAmount += $lineItem->total_amount;

                            if (!$lineItem->save(false)) {
                                throw new \Exception('فشل حفظ بند الفاتورة');
                            }

                            /* تحديث الكمية */
                            $this->updateItemQuantity($model, $lineItem, 'add');

                            /* تسجيل حركة مخزون */
                            StockMovement::record($lineItem->inventory_items_id, StockMovement::TYPE_IN, $lineItem->number, [
                                'reference_type' => 'invoice',
                                'reference_id'   => $model->id,
                                'unit_cost'      => $lineItem->single_price,
                                'supplier_id'    => $model->suppliers_id,
                                'company_id'     => $model->company_id,
                            ]);
                        }

                        /* تحديث إجمالي الفاتورة */
                        $model->total_amount = $totalAmount;
                        $model->save(false);

                        $transaction->commit();
                        Yii::$app->session->setFlash('success', 'تم إنشاء أمر الشراء بنجاح');
                        return $this->redirect(['index']);
                    }
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'خطأ: ' . $e->getMessage());
                }
            }
        }

        return $this->render('create', [
            'model' => $model,
            'itemsInventoryInvoices' => $itemsInventoryInvoices,
        ]);
    }

    /**
     * تعديل أمر شراء — معاد بناؤه بالكامل
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);
        $itemsInventoryInvoices = ItemsInventoryInvoices::find()
            ->where(['inventory_invoices_id' => $id])
            ->all();

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title'   => 'تعديل أمر الشراء #' . $id,
                    'content' => $this->renderAjax('update', ['model' => $model]),
                    'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                                 Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
                ];
            }
            if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title'       => 'أمر الشراء #' . $id,
                    'content'     => $this->renderAjax('view', ['model' => $model]),
                    'footer'      => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']),
                ];
            }
            return [
                'title'   => 'تعديل أمر الشراء #' . $id,
                'content' => $this->renderAjax('update', ['model' => $model]),
                'footer'  => Html::button('إغلاق', ['class' => 'btn btn-default pull-left', 'data-dismiss' => 'modal']) .
                             Html::button('حفظ', ['class' => 'btn btn-primary', 'type' => 'submit']),
            ];
        }

        /* Non-AJAX */
        if ($model->load($request->post())) {
            $oldLineItems = $itemsInventoryInvoices;
            $oldIDs = ArrayHelper::map($oldLineItems, 'id', 'id');

            $itemsInventoryInvoices = Model::createMultiple(ItemsInventoryInvoices::class, $itemsInventoryInvoices);
            Model::loadMultiple($itemsInventoryInvoices, $request->post());
            $deletedIDs = array_diff($oldIDs, array_filter(ArrayHelper::map($itemsInventoryInvoices, 'id', 'id')));

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save(false)) {
                    /* إزالة الكميات القديمة */
                    foreach ($oldLineItems as $old) {
                        $this->updateItemQuantity($model, $old, 'subtract');
                    }

                    /* حذف البنود المحذوفة */
                    if (!empty($deletedIDs)) {
                        ItemsInventoryInvoices::deleteAll(['id' => $deletedIDs]);
                    }

                    /* حفظ البنود الجديدة/المعدلة */
                    $totalAmount = 0;
                    foreach ($itemsInventoryInvoices as $lineItem) {
                        $lineItem->inventory_invoices_id = $model->id;
                        $lineItem->total_amount = $lineItem->single_price * $lineItem->number;
                        $totalAmount += $lineItem->total_amount;

                        if (!$lineItem->save(false)) {
                            throw new \Exception('فشل حفظ بند الفاتورة');
                        }

                        /* إضافة الكميات الجديدة */
                        $this->updateItemQuantity($model, $lineItem, 'add');
                    }

                    $model->total_amount = $totalAmount;
                    $model->save(false);

                    $transaction->commit();
                    Yii::$app->session->setFlash('success', 'تم تحديث أمر الشراء بنجاح');
                    return $this->redirect(['index']);
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'خطأ: ' . $e->getMessage());
            }
        }

        return $this->render('update', [
            'model' => $model,
            'itemsInventoryInvoices' => empty($itemsInventoryInvoices) ? [new ItemsInventoryInvoices] : $itemsInventoryInvoices,
        ]);
    }

    /**
     * حذف أمر شراء — مصلح بالكامل
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $lineItems = ItemsInventoryInvoices::find()
            ->where(['inventory_invoices_id' => $id])
            ->all();

        $transaction = Yii::$app->db->beginTransaction();
        try {
            /* إزالة الكميات لكل بند */
            foreach ($lineItems as $lineItem) {
                $this->updateItemQuantity($model, $lineItem, 'subtract');

                /* تسجيل حركة إلغاء */
                StockMovement::record($lineItem->inventory_items_id, StockMovement::TYPE_OUT, $lineItem->number, [
                    'reference_type' => 'invoice_cancel',
                    'reference_id'   => $model->id,
                    'notes'          => 'إلغاء أمر شراء #' . $model->id,
                    'company_id'     => $model->company_id,
                ]);

                $lineItem->delete();
            }
            $model->delete();
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'خطأ في الحذف: ' . $e->getMessage());
        }

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }
        return $this->redirect(['index']);
    }

    public function actionBulkDelete()
    {
        $pks = explode(',', Yii::$app->request->post('pks'));
        foreach ($pks as $pk) {
            $this->actionDelete($pk);
        }

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => '#crud-datatable-pjax'];
        }
        return $this->redirect(['index']);
    }

    /* ═══════════════════════════════════════════════════════════
     *  مساعدات — تحديث الكميات (مصلح بالكامل)
     * ═══════════════════════════════════════════════════════════ */

    /**
     * تحديث كمية الصنف في المخزون
     * @param InventoryInvoices $invoice
     * @param ItemsInventoryInvoices $lineItem
     * @param string $operation 'add' | 'subtract'
     */
    private function updateItemQuantity($invoice, $lineItem, $operation)
    {
        if (!$lineItem->inventory_items_id || !$lineItem->number) return;

        $qtyRecord = InventoryItemQuantities::find()
            ->where(['item_id' => $lineItem->inventory_items_id, 'is_deleted' => 0])
            ->andFilterWhere(['company_id' => $invoice->company_id])
            ->one();

        if ($operation === 'add') {
            if ($qtyRecord) {
                $qtyRecord->quantity += $lineItem->number;
                $qtyRecord->save(false);
            } else {
                $qtyRecord = new InventoryItemQuantities();
                $qtyRecord->item_id      = $lineItem->inventory_items_id;
                $qtyRecord->quantity      = $lineItem->number;
                $qtyRecord->company_id    = $invoice->company_id;
                $qtyRecord->suppliers_id  = $invoice->suppliers_id ?: 0;
                $qtyRecord->locations_id  = 0;
                $qtyRecord->save(false);
            }
        } elseif ($operation === 'subtract') {
            if ($qtyRecord) {
                $qtyRecord->quantity = max(0, $qtyRecord->quantity - $lineItem->number);
                if ($qtyRecord->quantity <= 0) {
                    $qtyRecord->delete();
                } else {
                    $qtyRecord->save(false);
                }
            }
        }
    }

    protected function findModel($id)
    {
        if (($model = InventoryInvoices::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('الصفحة المطلوبة غير موجودة.');
    }
}
