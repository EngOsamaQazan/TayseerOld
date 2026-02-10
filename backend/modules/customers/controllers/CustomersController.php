<?php

namespace backend\modules\customers\controllers;

use backend\modules\realEstate\models\RealEstate;
use yii\web\Controller;
use backend\modules\notification\models\Notification;
use Yii;
use backend\modules\customers\models\Customers;
use  backend\modules\customers\models\CustomersSearch;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use backend\modules\address\models\Address;
use yii\helpers\ArrayHelper;
use common\models\Model;
use backend\modules\phoneNumbers\models\PhoneNumbers;
use backend\modules\customers\models\CustomersDocument;
use yii\web\UploadedFile;
use yii\filters\AccessControl;

/**
 * Default controller for the `reports` module
 */
class CustomersController extends Controller
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
                        'actions' => ['logout', 'index', 'update', 'create', 'delete', 'update-contact', 'customer-data'],
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
        ];
    }

    /**
     * Lists all customers models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new CustomersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $searchCounter = $searchModel->searchCounter(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'searchCounter' => $searchCounter
        ]);
    }

    /**
     * Displays a single customers model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "customers #" . $id,
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
     * Creates a new customers model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $model = new Customers();
        $modelsAddress = [new Address];
        $modelsPhoneNumbers = [new PhoneNumbers];
        $modelCustomerDocuments = [new CustomersDocument];
        $modelRealEstate = [new RealEstate];
     $model->scenario = 'create';
        /*
         *   Process for non-ajax request
         */
        if ($model->load($request->post())) {
            $modelsAddress = Model::createMultiple(Address::classname());
            $modelRealEstate = Model::createMultiple(RealEstate::classname());
            $modelsPhoneNumbers = Model::createMultiple(PhoneNumbers::classname(), $modelsPhoneNumbers);
            $modelCustomerDocuments = Model::createMultiple(CustomersDocument::classname(), $modelCustomerDocuments);
            Model::loadMultiple($modelsAddress, Yii::$app->request->post());
            Model::loadMultiple($modelsPhoneNumbers, Yii::$app->request->post());
            Model::loadMultiple($modelCustomerDocuments, Yii::$app->request->post());
            Model::loadMultiple($modelRealEstate, Yii::$app->request->post());

// validate all models
            $valid = $model->validate();
            $valid = Model::validateMultiple($modelsAddress) && Model::validateMultiple($modelRealEstate) && Model::validateMultiple($modelsPhoneNumbers) && Model::validateMultiple($modelCustomerDocuments) && $valid;

                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    if ($flag = $model->save(false)) {
                        foreach ($modelsAddress as $modelAddress) {
                            $modelAddress->customers_id = $model->id;
                            if (!($addressFlag = $modelAddress->save())) {
                                $transaction->rollBack();
                                break;
                            }
                        }
                        foreach ($modelsPhoneNumbers as $modelsPhoneNumber) {

                            $modelsPhoneNumber->customers_id = $model->id;
                            if (!($phoneNumberflag = $modelsPhoneNumber->save())) {
                                $transaction->rollBack();
                                break;
                            }
                        }
                        foreach ($modelRealEstate as $modelRealEstates) {

                            $modelRealEstates->customer_id = $model->id;
                            if (!($modelRealEstatesflage = $modelRealEstates->save())) {
                                $transaction->rollBack();
                                break;
                            }
                        }

                        foreach ($modelCustomerDocuments as $modelCustomerDocument) {
                            $modelCustomerDocument->customer_id = $model->id;
                            $image_manager_model = new \noam148\imagemanager\models\ImageManager();
                            if (!($customersDocumentflag = $modelCustomerDocument->save())) {
                                $transaction->rollBack();

                                break;
                            }

                            if (!empty($model->id)) {
                                //!$image_manager_model->updateAll(['contractId' => $model->id], ['contractId' => $model->image_manager_id])
                                if (!Yii::$app->db->createCommand("UPDATE {{%ImageManager}} set contractId={$model->id} WHERE contractId={$model->image_manager_id}")) {
                                    $transaction->rollBack();

                                }
                            }
                        }
                    }

                    if ($flag && $addressFlag && $phoneNumberflag && $modelRealEstatesflage) {
                        $transaction->commit();
                        Yii::$app->notifications->sendByRule(['Manager'], 'customers/customers/update?id=' . $model->id, Notification::GENERAL, Yii::t('app', ' اضافة عميل  ') . $model->name . Yii::t('app', '       الى العملاء من قبل') . Yii::$app->user->identity['username'], Yii::t('app', 'اضافة  ') . $model->name . Yii::t('app', '  الى العملاء من قبل') . Yii::$app->user->identity['username'], Yii::$app->user->id);
                        Yii::$app->cache->set(Yii::$app->params['key_customers'],Yii::$app->db->createCommand(Yii::$app->params['customers_query'])->queryAll(), Yii::$app->params['time_duration']);
                        Yii::$app->cache->set(Yii::$app->params['key_customers_name'],Yii::$app->db->createCommand(Yii::$app->params['customers_name_query'])->queryAll(), Yii::$app->params['time_duration']);
                        return $this->redirect(['update', 'id' => $model->id]);
                    }
                } catch (Exception $e) {
                    $transaction->rollBack();
                }

        } else {
            return $this->render('create', [
                'model' => $model,
                'modelsAddress' => (empty($modelsAddress)) ? [new Address] : $modelsAddress,
                'modelsPhoneNumbers' => (empty($modelsPhoneNumbers)) ? [new PhoneNumbers] : $modelsPhoneNumbers,
                'customerDocumentsModel' => (empty($customerDocumentsModel)) ? [new CustomersDocument] : $customerDocumentsModel,
                'modelRealEstate' => (empty($modelRealEstate)) ? [new RealEstate] : $modelRealEstate
            ]);
        }
    }

    /**
     * Updates an existing customers model.
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
        $modelsAddress = Address::find()->where(['customers_id' => $id])->all();
        $modelsPhoneNumbers = PhoneNumbers::find()->where(['customers_id' => $id])->all();
        $customerDocumentsModel = CustomersDocument::find()->where(['customer_id' => $id])->all();
        $modelRealEstate = RealEstate::find()->where(['customer_id' => $id])->all();

        if ($model->load($request->post())) {
            $oldIDs = yii\helpers\ArrayHelper::map($modelsAddress, 'id', 'id');
            $oldPhoneIDs = yii\helpers\ArrayHelper::map($modelsPhoneNumbers, 'id', 'id');
            $oldDocumentsIDs = yii\helpers\ArrayHelper::map($customerDocumentsModel, 'id', 'id');
            $oldmodelRealEstateIDs = yii\helpers\ArrayHelper::map($modelRealEstate, 'id', 'id');

            $modelsAddress = Model::createMultiple(Address::classname(), $modelsAddress);
            $modelsPhoneNumbers = Model::createMultiple(PhoneNumbers::classname(), $modelsPhoneNumbers);
            $customerDocumentsModel = Model::createMultiple(CustomersDocument::classname(), $customerDocumentsModel);
            $modelRealEstate = Model::createMultiple(RealEstate::classname(), $modelRealEstate);

            Model::loadMultiple($modelsAddress, Yii::$app->request->post());
            Model::loadMultiple($modelsPhoneNumbers, Yii::$app->request->post());
            Model::loadMultiple($customerDocumentsModel, Yii::$app->request->post());
            Model::loadMultiple($modelRealEstate, Yii::$app->request->post());

            $deletedIDs = array_diff($oldIDs, array_filter(yii\helpers\ArrayHelper::map($modelsAddress, 'id', 'id')));
            $deletedPhoneIDs = array_diff($oldPhoneIDs, array_filter(yii\helpers\ArrayHelper::map($modelsPhoneNumbers, 'id', 'id')));
            $deletedDocumentsIDs = array_diff($oldDocumentsIDs, array_filter(yii\helpers\ArrayHelper::map($customerDocumentsModel, 'id', 'id')));
            $deleteRealEstateIDs = array_diff($oldmodelRealEstateIDs, array_filter(yii\helpers\ArrayHelper::map($modelRealEstate, 'id', 'id')));
            // validate all models
            $valid = $model->validate();
            $valid = Model::validateMultiple($modelsAddress) && Model::validateMultiple($modelRealEstate)&& Model::validateMultiple($modelsPhoneNumbers) && Model::validateMultiple($customerDocumentsModel) && $valid;

            if ($valid) {
                $transaction = \Yii::$app->db->beginTransaction();
                try {

                    if ($flag = $model->save(false)) {
                        if (!empty($deletedIDs)) {
                            Address::deleteAll(['id' => $deletedIDs]);
                        }
                        foreach ($modelsAddress as $modelAddress) {
                            $modelAddress->customers_id = $model->id;
                            if (!($addressFlag = $modelAddress->save(false))) {

                                $transaction->rollBack();
                                break;
                            }
                        }
                        if (!empty($deleteRealEstateIDs)) {

                            RealEstate::deleteAll(['id' => $deleteRealEstateIDs]);
                        }
                        foreach ($modelRealEstate as $modelRealEstates) {

                            $modelRealEstates->customer_id = $model->id;
                            if (!($modelRealEstateFlag = $modelRealEstates->save(false))) {

                                $transaction->rollBack();
                                break;
                            }
                        }

                        if (!empty($deletedPhoneIDs)) {
                            PhoneNumbers::deleteAll(['id' => $deletedPhoneIDs]);
                        }
                        foreach ($modelsPhoneNumbers as $modelsPhoneNumber) {

                            $modelsPhoneNumber->customers_id = $model->id;
                            if (!($phoneNumberflag = $modelsPhoneNumber->save(false))) {

                                $transaction->rollBack();
                                break;
                            }
                        }
                        if (!empty($deletedDocumentsIDs)) {
                            CustomersDocument::deleteAll(['id' => $deletedDocumentsIDs]);
                        }
                        foreach ($customerDocumentsModel as $modelCustomerDocument) {

                            $modelCustomerDocument->customer_id = $model->id;
                            if (!($customersDocumentflag = ($modelCustomerDocument->save(false)))) {
                                $transaction->rollBack();
                                var_dump($modelCustomerDocument->getErrors());
                                break;
                            }
                        }
                    }
                    if ($flag && $addressFlag && $phoneNumberflag && $modelRealEstateFlag ) {
                        $transaction->commit();

                        Yii::$app->cache->set(Yii::$app->params['key_customers'],Yii::$app->db->createCommand(Yii::$app->params['customers_query'])->queryAll(), Yii::$app->params['time_duration']);
                        Yii::$app->cache->set(Yii::$app->params['key_customers_name'],Yii::$app->db->createCommand(Yii::$app->params['customers_name_query'])->queryAll(), Yii::$app->params['time_duration']);

                        return $this->redirect(['update', 'id' => $model->id]);
                    }
                } catch (Exception $e) {
                    $transaction->rollBack();
                }
            }
            return $this->render('update', [
                'modelCustomer' => $model,
                'model' => $model,
                'modelsAddress' => (empty($modelsAddress)) ? [new Address] : $modelsAddress,
                'modelsPhoneNumbers' => (empty($modelsPhoneNumbers)) ? [new PhoneNumbers] : $modelsPhoneNumbers,
                'customerDocumentsModel' => (empty($customerDocumentsModel)) ? [new CustomersDocument] : $customerDocumentsModel,
                'modelRealEstate' => (empty($modelRealEstate)) ? [new RealEstate] : $modelRealEstate
            ]);
        } else {
            return $this->render('update', [
                'model' => $model,
                'modelsAddress' => (empty($modelsAddress)) ? [new Address] : $modelsAddress,
                'modelsPhoneNumbers' => (empty($modelsPhoneNumbers)) ? [new PhoneNumbers] : $modelsPhoneNumbers,
                'customerDocumentsModel' => (empty($customerDocumentsModel)) ? [new CustomersDocument] : $customerDocumentsModel,
               'modelRealEstate' => (empty($modelRealEstate)) ? [new RealEstate] : $modelRealEstate

            ]);
        }
    }

    /**
     * Delete an existing customers model.
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
            return ['forceClose' => true, 'forceReload' => '#customers-table-crud-datatable'];
        } else {
            /*
             *   Process for non-ajax request
             */
            return $this->redirect(['index']);
        }
    }

    /**
     * Delete multiple existing customers model.
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
            return ['forceClose' => true, 'forceReload' => '#customers-table-crud-datatable'];
        } else {
            /*
             *   Process for non-ajax request
             */
            return $this->redirect(['index']);
        }
    }

    /**
     * Finds the customers model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return customers the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = customers::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    function actionCustomerData($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = $this->findModel($id);
        $contracts_info = $model->getContractsCustomers();
        return [
            'model' => $model,
            'contracts_info' => [
                'count' => $contracts_info->count(),
                'info' => $contracts_info->all(),
            ]
        ];
    }

    /**
     * Updates an existing PhoneNumbers model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdateContact($id)
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
                    'title' => "",
                    //'forceReload' => '#customers-table-crud-datatable',
                    'content' => $this->renderAjax('contact_update', [
                        'model' => $model,
                        'id' => $id
                    ]),
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::button('Save', ['class' => 'btn btn-primary', 'type' => "submit"])
                ];
            } else if ($model->load($request->post()) && $model->save()) {
                return [
                    'forceReload' => '#customers-table-crud-datatable',
                    'title' => "PhoneNumbers #" . $id,
                    'content' => "<h3>تم التعديل</h3>",
                    'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                        Html::a('Edit', ['contact_update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
                ];
            } else {
                return [
                    'title' => "Update PhoneNumbers #" . $id,
                    'content' => $this->renderAjax('contact_update', [
                        'model' => $model,
                        'id' => $id
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
                return $this->render('contact_update', [
                    'model' => $model,
                    'id' => $id
                ]);
            }
        }
    }

}
