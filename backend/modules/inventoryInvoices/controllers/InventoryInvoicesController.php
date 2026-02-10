<?php

namespace backend\modules\inventoryInvoices\controllers;

use backend\modules\companyBanks\models\CompanyBanks;
use \backend\modules\inventoryItemQuantities\models\InventoryItemQuantities;
use backend\modules\itemsInventoryInvoices\models\ItemsInventoryInvoices;
use common\models\Model;
use Yii;
use backend\modules\inventoryInvoices\models\InventoryInvoices;
use backend\modules\inventoryInvoices\models\InventoryInvoicesSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;

/**
 * InventoryInvoicesController implements the CRUD actions for InventoryInvoices model.
 */
class InventoryInvoicesController extends Controller
{
    /**
     * @inheritdoc
     */

    /**
     * Lists all InventoryInvoices models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new InventoryInvoicesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }


    /**
     * Displays a single InventoryInvoices model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "InventoryInvoices #" . $id,
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
     * Creates a new InventoryInvoices model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new InventoryInvoices();
        $itemsInventoryInvoices = [new ItemsInventoryInvoices];
        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => "Create new InventoryInvoices",
                    'content' => $this->renderAjax('create', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            } else if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title' => "Create new InventoryInvoices",
                    'content' => '<span class="text-success">Create InventoryInvoices success</span>',
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::a('Create More', ['create'], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])

                ];
            } else {
                return [
                    'title' => "Create new InventoryInvoices",
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
            if ($model->load($request->post())) {
                $itemsInventoryInvoices = Model::createMultiple(ItemsInventoryInvoices::classname());
                Model::loadMultiple($itemsInventoryInvoices, Yii::$app->request->post());
                $valid = $model->validate();
                $valid = Model::validateMultiple($itemsInventoryInvoices);
                if ($valid) {
                    $transaction = \Yii::$app->db->beginTransaction();
                    try {

                        if ($flag = $model->save(false)) {
                            foreach ($itemsInventoryInvoices as $itemsInventoryInvoice) {
                                $inventory_quantitys = InventoryItemQuantities::find()->where(['company_id' => $model->company_id])->andWhere(['item_id' => $itemsInventoryInvoice->inventory_items_id])->all();
                                if (!empty($inventory_quantitys)) {
                                    foreach ($inventory_quantitys as $inventory_quantity) {
                                        $number = $inventory_quantity->quantity + $itemsInventoryInvoice->number;
                                        inventoryItemQuantities::updateAll(['quantity' => $number], ['id' => $inventory_quantity->id]);
                                    }
                                }


                                if (empty($inventory_quantitys)) {
                                    $inventory = new  InventoryItemQuantities();
                                    $inventory->item_id = $itemsInventoryInvoice->inventory_items_id;
                                    $inventory->suppliers_id = $model->suppliers_id;
                                    $inventory->quantity = $itemsInventoryInvoice->number;
                                    $inventory->company_id = $model->company_id;
                                    $inventory->save(false);


                                }
                                $itemsInventoryInvoice->inventory_invoices_id = $model->id;
                                $itemsInventoryInvoice->total_amount = $itemsInventoryInvoice->single_price * $itemsInventoryInvoice->number;

                                if (!($itemsInventoryInvoiceBankFlag = $itemsInventoryInvoice->save())) {
                                    $transaction->rollBack();
                                    var_dump($itemsInventoryInvoice->getErrors());
                                    break;
                                }
                            }

                            if ($flag && $itemsInventoryInvoiceBankFlag) {

                                $transaction->commit();
                            }
                        }

                    } catch (Exception $e) {
                        $transaction->rollBack();
                        var_dump($model->getErrors());
                    }
                }


                return $this->redirect(['index']);
            } else {
                return $this->render('create', [
                    'model' => $model,
                    'itemsInventoryInvoices' => (empty($itemsInventoryInvoices)) ? [new ItemsInventoryInvoices] : $itemsInventoryInvoices,

                ]);
            }
        }

    }

    /**
     * Updates an existing InventoryInvoices model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);
        $itemsInventoryInvoices = ItemsInventoryInvoices::find()->where(['inventory_invoices_id' => $id])->all();


        if ($request->isAjax) {
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                return [
                    'title' => "Update InventoryInvoices #" . $id,
                    'content' => $this->renderAjax('update', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            } else if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#crud-datatable-pjax',
                    'title' => "InventoryInvoices #" . $id,
                    'content' => $this->renderAjax('view', [
                        'model' => $model,
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
                ];
            } else {
                return [
                    'title' => "Update InventoryInvoices #" . $id,
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
            if ($model->load($request->post())) {
                $oldIDs = yii\helpers\ArrayHelper::map($itemsInventoryInvoices, 'id', 'id');

                $itemsInventoryInvoices = Model::createMultiple(ItemsInventoryInvoices::classname(), $itemsInventoryInvoices);

                Model::loadMultiple($itemsInventoryInvoices, Yii::$app->request->post());

                $deletedIDs = array_diff($oldIDs, array_filter(yii\helpers\ArrayHelper::map($itemsInventoryInvoices, 'id', 'id')));


                $transaction = \Yii::$app->db->beginTransaction();
                $oldNumber = 0;
                $count = [];
                foreach ($oldIDs as $oldID) {
                    $oldAmount = ItemsInventoryInvoices::find()->where(['id' => $oldID])->all();

                    foreach ($oldAmount as $old) {
                        $oldNumber = $oldNumber + $old->number;
                        array_push($count, $old->number);
                    }
                }
                try {
                    if ($flag = $model->save(false)) {
                        if (!empty($deletedIDs)) {
                            ItemsInventoryInvoices::deleteAll(['id' => $deletedIDs]);
                        }
                        $number = 0;
                        foreach ($itemsInventoryInvoices as $itemsInventoryInvoice) {
                            $number = $number + $itemsInventoryInvoice->number;
                            $itemsInventoryInvoice->inventory_invoices_id = $model->id;
                            $itemsInventoryInvoice->total_amount = $itemsInventoryInvoice->single_price * $itemsInventoryInvoice->number;

                            $inventory_quantitys = InventoryItemQuantities::find()->where(['company_id' => $model->company_id])->andWhere(['item_id' => $itemsInventoryInvoice->inventory_items_id])->all();
                            $inventory_quantity_number = 0;
                            if (!empty($inventory_quantitys)) {
                                foreach ($inventory_quantitys as $inventory_quantity) {
                                    $inventory_quantity_number = $inventory_quantity->quantity;
                                }

                            }


                            if (empty($inventory_quantitys)) {

                                $inventory = new  InventoryItemQuantities();
                                $inventory->item_id = $itemsInventoryInvoice->inventory_items_id;
                                $inventory->suppliers_id = $model->suppliers_id;
                                $inventory->quantity = $itemsInventoryInvoice->number;
                                $inventory->company_id = $model->company_id;
                                $inventory->save();
                            }

                            if (!($itemsInventoryInvoiceFlag = $itemsInventoryInvoice->save())) {

                                $transaction->rollBack();
                                var_dump($itemsInventoryInvoice->getErrors());
                                break;
                            }
                        }

                        inventoryItemQuantities::updateAll(['quantity' => $inventory_quantity_number - $oldNumber], ['id' => $inventory_quantity->id]);

                        $inventory_quantitys = InventoryItemQuantities::find()->where(['company_id' => $model->company_id])->andWhere(['item_id' => $itemsInventoryInvoice->inventory_items_id])->all();
                        $inventory_quantity_number_2 = 0;
                        if (!empty($inventory_quantitys)) {
                            foreach ($inventory_quantitys as $inventory_quantity) {
                                $inventory_quantity_number_2 = $inventory_quantity->quantity;
                            }

                        }
                        inventoryItemQuantities::updateAll(['quantity' => $inventory_quantity_number_2 + $number], ['id' => $inventory_quantity->id]);

                        if ($flag) {
                            $transaction->commit();
                            return $this->redirect(['index']);
                        }

                    }

                } catch (Exception $e) {
                    $transaction->rollBack();
                }


                return $this->redirect(['index']);
            } else {
                return $this->render('update', [
                    'model' => $model,
                    'itemsInventoryInvoices' => (empty($itemsInventoryInvoices)) ? [new ItemsInventoryInvoices] : $itemsInventoryInvoices,

                ]);
            }
        }
    }

    /**
     * Delete an existing InventoryInvoices model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $request = Yii::$app->request;

        $model = $this->findModel($id);
        $itemsInventoryInvoices = ItemsInventoryInvoices::find()->where(['inventory_invoices_id' => $id])->all();
        $number = 0;
        foreach ($itemsInventoryInvoices as $itemsInventoryInvoice) {
            $inventory_quantitys = InventoryItemQuantities::find()->where(['company_id' => $model->company_id])->andWhere(['item_id' => $itemsInventoryInvoice->inventory_items_id])->all();
            $number = $itemsInventoryInvoice->number + $number;
            foreach ($inventory_quantitys as $inventory_quantity) {
                $inventory_quantity_number = $inventory_quantity->quantity;
            }

            $itemsInventoryInvoice->delete();
        }
        $result = $inventory_quantity_number - $number;
        if($result == 0 || $result < 0){
            inventoryItemQuantities::deleteAll(['id' => $inventory_quantity->id]);
        }else{
            inventoryItemQuantities::updateAll(['quantity' => $inventory_quantity_number - $number], ['id' => $inventory_quantity->id]);

        }


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
     * Delete multiple existing InventoryInvoices model.
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
     * Finds the InventoryInvoices model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return InventoryInvoices the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = InventoryInvoices::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
