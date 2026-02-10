<?php

namespace backend\modules\contracts\controllers;

use Yii;
use yii\helpers\Html;
use \yii\web\Response;
use yii\web\Controller;
use backend\models\Model;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;

use yii\filters\AccessControl;

use yii\web\NotFoundHttpException;
use common\components\notificationComponent;
use backend\modules\followUp\models\FollowUp;
use backend\modules\customers\models\Customers;
use  backend\modules\contracts\models\Contracts;
use backend\modules\notification\models\Notification;
use  backend\modules\contracts\models\ContractsSearch;
use  backend\modules\customers\models\ContractsCustomers;
use backend\modules\followUp\models\FollowUpConnectionReports;
use backend\modules\inventoryItems\models\ContractInventoryItem;
use backend\modules\contractDocumentFile\models\ContractDocumentFile;
use backend\modules\inventoryItemQuantities\models\InventoryItemQuantities;
use backend\modules\inventoryStockLocations\models\InventoryStockLocations;

/**
 * ContractsController implements the CRUD actions for contracts model.
 */
class ContractsController extends Controller
{

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index', 'update', 'create', 'delete', 'is-connect','is-not-connect', 'print-first-page', 'print-second-page', 'finish', 'finish-contract', 'cancel', 'cancel-contract', 'legal-department', 'to-legal-department', 'return-to-continue', 'view', 'index-legal-department', 'convert-to-manager', 'is-read', 'chang-follow-up'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all contracts models.
     * @return mixed
     */
    public function actionIndex()
    {

        $searchModel = new ContractsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataCount = $searchModel->searchcounter(Yii::$app->request->queryParams);
     
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'dataCount' => $dataCount
        ]);
    }

    public function actionIndexLegalDepartment()
    {

        $searchModel = new ContractsSearch();
        $dataProvider = $searchModel->searchLegalDepartment(Yii::$app->request->queryParams);
        $dataCount = $searchModel->searchLegalDepartmentCount(Yii::$app->request->queryParams);
        return $this->render('index-legal-department', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'dataCount' => $dataCount
        ]);
    }

    public function actionLegalDepartment()
    {
        $searchModel = new ContractsSearch();
        $dataProvider = $searchModel->searchLegalDepartment(Yii::$app->request->queryParams);

        $dataCount = $searchModel->searchLegalDepartmentCount(Yii::$app->request->queryParams);

        return $this->render('index-legal-department', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'dataCount' => $dataCount
        ]);
    }

    /**
     * Displays a single contracts model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "contracts #" . $id,
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
     * Creates a new contracts model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new contracts();
        $customers_model = new Customers();
        $contracts_customers = new ContractsCustomers();
        $modelContractInventoryItem = [new ContractInventoryItem];
        $transaction = \Yii::$app->db->beginTransaction();
        $model->status = $model::STATUS_ACTIVE;
        $model->seller_id = Yii::$app->user->id;
        if ($model->load($request->post()) && $model->save(false)) {
            $modelContractInventoryItem = Model::createMultiple(ContractInventoryItem::class);
            Model::loadMultiple($modelContractInventoryItem, Yii::$app->request->post());
            $valid = $model->validate();
            $valid = Model::validateMultiple($modelContractInventoryItem) && $valid;
            if (1) {
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    if ($flag = $model->save(false)) {
                        foreach ($modelContractInventoryItem as $modelsContractInventoryItem) {
                            $modelsContractInventoryItem->contract_id = $model->id;
                            if (!($flag = $modelsContractInventoryItem->save(false))) {
                                $transaction->rollBack();
                                break;
                            }
                            $localtion = InventoryStockLocations::find()->andWhere(['company_id' => $model->company_id])->one();
                            $inventory_item_quantity = new InventoryItemQuantities();
                            $inventory_item_quantity->item_id = $modelsContractInventoryItem->item_id;
                            $inventory_item_quantity->suppliers_id = $model->company_id;
                            $inventory_item_quantity->locations_id = isset($localtion->id) ? $localtion->id : 0;
                            $inventory_item_quantity->quantity = 1;
                            $inventory_item_quantity->save(false);
                        }
                    }
                    if ($flag) {
                        $transaction->commit();
                    }
                } catch (Exception $e) {
                    $transaction->rollBack();
                }
            }

            if ($model->type == 'solidarity') {
                foreach ($model->customers_ids as $customer) {
                    $contracts_customers->id = NULL;
                    $contracts_customers->isNewRecord = true;
                    $contracts_customers->contract_id = $model->id;
                    $contracts_customers->customer_id = $customer;
                    $contracts_customers->customer_type = 'client';

                    if (!$contracts_customers->save()) {
                        $transaction->rollBack();
                        echo '1 .<pre>';
                        var_dump($contracts_customers->getErrors());
                        echo '</pre>';
                        die();
                    }

                }
            } else {

                $contracts_customers->contract_id = $model->id;
                $contracts_customers->customer_id = $model->customer_id;
                $contracts_customers->customer_type = 'client';
                if (!$contracts_customers->save()) {
                    $transaction->rollBack();
                    echo '2<pre>';
                    var_dump($contracts_customers->getErrors());
                    echo '</pre>';
                    die();
                }

                $contracts_customers = new ContractsCustomers();
                if (!empty($model->guarantors_ids)) {
                    foreach ($model->guarantors_ids as $guarantor) {
                        $contracts_customers->id = NULL;
                        $contracts_customers->isNewRecord = true;
                        $contracts_customers->contract_id = $model->id;
                        $contracts_customers->customer_id = $guarantor;
                        $contracts_customers->customer_type = 'guarantor';
                        if (!$contracts_customers->save()) {
                            $transaction->rollBack();
                        }
                    }
                }

//                $total = $model->total_value;
//                $installment_model = new Installment();
//                $time = strtotime($model->first_installment_date);
//                $final = $time;
//                while ($total !== 0) {
//                    $installment_model->id = NULL;
//                    $installment_model->isNewRecord = true;
//                    $installment_model->contract_id = $model->id;
//                    $installment_model->total = 0;
//                    $installment_model->date = $final;
//                    if (!$installment_model->save()) {
//                        $transaction->rollBack();
//                        echo '4<pre>';
//                        var_dump($installment_model->getErrors());
//                        echo '</pre>';
//                        die();
//                    }
//                    if ($total < $model->monthly_installment_value) {
//                        $time = $time - $model->monthly_installment_value;
//                    } else {
//                        $total = 0;
//                    }
//                    $final = date("Y-m-d", strtotime("+1 month", $time));
//                }
            }

            $image_manager_model = new \noam148\imagemanager\models\ImageManager();
            $image_manager_model->contractId = $model->id;
            $image_manager_model->save();

//                var_dump($image_manager_model->updateAll(['contractId' => $model->id], ['contractId' => $model->image_manager_id]));die();
//            if (!$image_manager_model->updateAll(['contractId' => $model->id], ['contractId' => $model->image_manager_id])) {
//
//                var_dump($image_manager_model->errors);die('tyu');
//                die($model->image_manager_id);
//            }
            $transaction->commit();

            if (isset($_POST['print'])) {
                return $this->redirect(['print-first-page', 'id' => $model->id]);
            }
            $followUpModel = new FollowUp();
            $followUpModel->contract_id = $model->id;
            $followUpModel->date_time = date('Y-m-d h:m:s');
            $followUpModel->notes = 'اضافة اليه';
            $followUpModel->feeling = 'normal';
            $followUpModel->connection_goal = 1;
            $followUpModel->reminder = $model->first_installment_date;
            $followUpModel->created_by = Yii::$app->user->id;
            $followUpModel->save();
            Yii::$app->notifications->sendByRule(['Manager'], 'contracts/update?id=' . $model->id, Notification::GENERAL, Yii::t('app', 'إنشاء عقد رقم'), Yii::t('app', 'إنشاء عقد رقم') . $model->id, Yii::$app->user->id);
            $modelContractDocumentFile = new ContractDocumentFile;
            $modelContractDocumentFile->document_type = 'contract file';
            $modelContractDocumentFile->contract_id = $model->id;
            $modelContractDocumentFile->save();
            Yii::$app->cache->set(Yii::$app->params['key_contract_id'],Yii::$app->db->createCommand(Yii::$app->params['contract_id_query'])->queryAll(), Yii::$app->params['time_duration']);
            Yii::$app->cache->set(Yii::$app->params['key_contract_status'],Yii::$app->db->createCommand(Yii::$app->params['contract_status_query'])->queryAll(), Yii::$app->params['time_duration']);

            return $this->redirect(['contracts/index']);
        } else {
            $transaction->rollBack();
            return $this->render('create', [
                'model' => $model,
                'customers_model' => $customers_model,
                'modelContractInventoryItem' => (empty($modelContractInventoryItem)) ? [new ContractInventoryItem] : $modelContractInventoryItem
            ]);
        }
    }

    /**
     * Updates an existing contracts model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id, $notificationID = 0)
    {
        if ($notificationID != 0) {
            Yii::$app->notifications->setReaded($notificationID);
        }
        $request = Yii::$app->request;
        $model = $this->findModel($id);
        $modelContractInventoryItem = ContractInventoryItem::find()->where(['contract_id' => $id])->all();
        $newContractsCustomerModle = new ContractsCustomers();


        $transaction = \Yii::$app->db->beginTransaction();
        if ($model->load($request->post())) {
            $oldIDs = yii\helpers\ArrayHelper::map($modelContractInventoryItem, 'id', 'id');
            $modelContractInventoryItem = Model::createMultiple(ContractInventoryItem::class, $modelContractInventoryItem);
            Model::loadMultiple($modelContractInventoryItem, Yii::$app->request->post());
            $deletedIDs = array_diff($oldIDs, array_filter(yii\helpers\ArrayHelper::map($modelContractInventoryItem, 'id', 'id')));
            $valid = $model->validate();
            $valid = Model::validateMultiple($modelContractInventoryItem) && $valid;
            if (1) {
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    if ($flag = $model->save(false)) {
                        if (!empty($deletedIDs)) {
                            FollowUpConnectionReports::deleteAll(['id' => $deletedIDs]);
                        }
                        foreach ($modelContractInventoryItem as $modelContractInventoryItem) {
                            $modelContractInventoryItem->contract_id = $model->id;

                            if (!($addressFlag = $modelContractInventoryItem->save(false))) {

                                $transaction->rollBack();
                                break;
                            }
                            $localtion = InventoryStockLocations::find()->andWhere(['company_id' => $model->company_id])->one();
                            $inventory_item_quantity = new InventoryItemQuantities();
                            $inventory_item_quantity->item_id = $modelContractInventoryItem->item_id;
                            $inventory_item_quantity->suppliers_id = $model->company_id;
                            $inventory_item_quantity->locations_id = isset($localtion->id) ? $localtion->id : 0;
                            $inventory_item_quantity->quantity = 1;
                            $inventory_item_quantity->save(false);
                        }
                    }
                    if ($flag) {
                        $transaction->commit();
                    }
                } catch (Exception $e) {
                    $transaction->rollBack();
                }
            }
            if (ContractsCustomers::deleteAll(['contract_id' => $id])) {
            }
            if ($model->type == 'solidarity') {
                foreach ($model->customers_ids as $customer) {
                    $newContractsCustomerModle->id = NULL;
                    $newContractsCustomerModle->isNewRecord = true;
                    $newContractsCustomerModle->contract_id = $id;
                    $newContractsCustomerModle->customer_id = $customer;
                    $newContractsCustomerModle->customer_type = 'client';
                    if (!$newContractsCustomerModle->save()) {
                        $transaction->rollBack();
                    }
                }
            } else {
                $newContractsCustomerModle->contract_id = $id;
                $newContractsCustomerModle->customer_id = $model->customer_id;
                $newContractsCustomerModle->customer_type = 'client';
                if (!$newContractsCustomerModle->save()) {
                    $transaction->rollBack();
                }
                $newContractsCustomerModle = new ContractsCustomers();
                if (!empty($model->guarantors_ids)) {
                    foreach ($model->guarantors_ids as $guarantor) {
                        $newContractsCustomerModle->id = NULL;
                        $newContractsCustomerModle->isNewRecord = true;
                        $newContractsCustomerModle->contract_id = $id;
                        $newContractsCustomerModle->customer_id = $guarantor;
                        $newContractsCustomerModle->customer_type = 'guarantor';
                        if (!$newContractsCustomerModle->save()) {
                            $transaction->rollBack();
                        }
                    }
                }
            }
//            $total = $model->total_value;
//            $installment_model = new Installment();
//            $installment_model->deleteAll(['contract_id' => $id]);
//            $time = strtotime($model->first_installment_date);
//            $final = $time;
//            while ($total !== 0) {
//                $installment_model->id = NULL;
//                $installment_model->isNewRecord = true;
//                $installment_model->total = $model->monthly_installment_value;
//                $installment_model->contract_id = $model->id;
//                $installment_model->date = $final;
//                if (!$installment_model->save()) {
//                    $transaction->rollBack();
//                    echo '<pre>';
//                    var_dump($installment_model->getErrors());
//                    echo '</pre>';
//                }
//                if ($total > $model->monthly_installment_value) {
//                    $total = $total - $model->monthly_installment_value;
//                } else {
//                    $total = 0;
//                }
//                $final = date("Y-m-d", strtotime("+1 month", $time));
//            }
            $transaction->commit();
            if (isset($_POST['print'])) {
                return $this->redirect(['print-first-page', 'id' => $model->id]);
            }
            Yii::$app->notifications->sendByRule(['Manager'], 'contracts/update?id=' . $model->id, Notification::GENERAL, Yii::t('app', ' تم تعديل عقد رقم') . $model->id . Yii::t('app', ' من قبل' . Yii::$app->user->identity['username']), Yii::t('app', 'تعديل عقد رقم') . $model->id, Yii::$app->user->id);

            return $this->redirect(['update', 'id' => $model->id]);
        } else {
            if ($model->type == 'solidarity') {
                $model->customers_ids = $model->customers;
            } elseif ($model->type == 'normal') {
                $model->customer_id = $model->customers;
                $model->guarantors_ids = $model->getGuarantor();
            }

            $model->guarantors_ids = $model->guarantor;
            Yii::$app->cache->set(Yii::$app->params['key_contract_id'],Yii::$app->db->createCommand(Yii::$app->params['contract_id_query'])->queryAll(), Yii::$app->params['time_duration']);
            Yii::$app->cache->set(Yii::$app->params['key_contract_status'],Yii::$app->db->createCommand(Yii::$app->params['contract_status_query'])->queryAll(), Yii::$app->params['time_duration']);

            return $this->render('update', [
                'model' => $model,
                'modelContractInventoryItem' => (empty($modelContractInventoryItem)) ? [new ContractInventoryItem] : $modelContractInventoryItem,
            ]);
        }
    }

    /**
     * Delete an existing contracts model.
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
     * Delete multiple existing contracts model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionBulkdelete()
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

    public function actionCustomers()
    {
        $model = new Customers();
        return $this->renderAjax('_customers', [
            'model' => $model,
        ]);
    }

    public function actionPrintFirstPage($id)
    {
        $request = Yii::$app->request;
        $this->layout = '/print-template-1';
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "contracts #" . $id,
                'content' => $this->renderAjax('_contract_print', [
                    'model' => $this->findModel($id),
                    'id' => $id
                ]),
                'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                    Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
            ];
        } else {
            return $this->render('_contract_print', [
                'model' => $this->findModel($id),
            ]);
        }
    }

    public function actionPrintSecondPage($id)
    {
        $request = Yii::$app->request;
        $this->layout = '/print-template-1';
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "contracts #" . $id,
                'content' => $this->renderAjax('_draft_print', [
                    'model' => $this->findModel($id),
                ]),
                'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                    Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
            ];
        } else {
            return $this->render('_draft_print', [
                'model' => $this->findModel($id),
            ]);
        }
    }

    /**
     * Finds the contracts model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return contracts the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = contracts::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Finish an existing contracts model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $contract_ids
     * @return mixed
     */
    public function actionFinish()
    {
        $contract_id = Yii::$app->request->post('contract_id');
        $this->findModel($contract_id)->finish();
        Yii::$app->session->addFlash('success', 'Contract has been Finished');
        return $this->redirect(['index']);
    }

    public function actionFinishContract($contract_id)
    {
        $this->findModel($contract_id)->finish();
        Yii::$app->session->addFlash('success', 'Contract has been Finished');
        return $this->redirect(['index']);
    }

    /**
     * Finish an existing contracts model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $contract_ids
     * @return mixed
     */
    public function actionCancel()
    {

        $contract_id = Yii::$app->request->post('contract_id');
        if (Contracts::updateAll(['status' => 'canceled'], ['id' => $contract_id])) {
            Yii::$app->session->addFlash('success', 'Contract has been Cancled');
            return $this->redirect(['index']);
        } else {
            Yii::$app->session->addFlash('success', 'Contract has not been Cancled');
            return $this->redirect(['index']);
        }
    }

    public function actionCancelContract($contract_id)
    {


        if (Contracts::updateAll(['status' => 'canceled'], ['id' => $contract_id])) {
            Yii::$app->session->addFlash('success', 'Contract has been Cancled');
            return $this->redirect(['index']);
        } else {
            Yii::$app->session->addFlash('success', 'Contract has not been Cancled');
            return $this->redirect(['index']);
        }
    }

    public function actionReturnToContinue($id)
    {
        if ($id > 0) {
            Contracts::updateAll(['status' => 'active'], ['id' => $id]);
        }
        $this->redirect(['/follow-up-report/index']);
    }

    public function actionToLegalDepartment($id)
    {
        $this->findModel($id)->legalDepartment();
        Yii::$app->session->addFlash('success', 'Contract has been transferred to legal Department');
        Yii::$app->notifications->sendByRule(['Manager'], '/follow-up?contract_id=' . $id, Notification::GENERAL, Yii::t('app', 'تحويل عقد الى الدائره القانونيه'), Yii::t('app', 'تحويل عقد' . $id . ' الى الدائره القانونيه'), Yii::$app->user->id);

        return $this->redirect(['index']);
    }

    public function actionConvertToManager($id)
    {
        Yii::$app->notifications->sendByRule(['Manager'], '/follow-up?contract_id=' . $id, Notification::GENERAL, Yii::t('app', 'مراجعة متابعه'), Yii::t('app', 'مراجعة متابعه للعقد رقم') . $id, Yii::$app->user->id);

        return $this->redirect(['index']);
    }

    public function actionChangFollowUp()
    {
        $id = @Yii::$app->request->post('id');
        $followedBy = @Yii::$app->request->post('followedBy');
        Contracts::updateAll(['followed_by' => (int)($followedBy)], ['id' => (int)($id)]);
    }

    function actionIsNotConnect($contract_id)
    {
        Yii::$app->db->createCommand()
            ->update('{{%contracts}}', ['is_can_not_contact' => 1], 'id = ' . $contract_id)
            ->execute();

        $this->redirect(['/followUp/follow-up/index', 'contract_id'=> $contract_id]);
    }
    function actionIsConnect($contract_id)
    {
        Yii::$app->db->createCommand()
            ->update('{{%contracts}}', ['is_can_not_contact' => 0], 'id = ' . $contract_id)
            ->execute();

        $this->redirect(['/followUp/follow-up/index', 'contract_id'=> $contract_id]);
    }
}
