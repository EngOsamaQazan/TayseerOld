<?php

namespace backend\modules\followUp\controllers;

use backend\modules\customers\Customers;
use backend\modules\loanScheduling\models\LoanScheduling;
use Yii;
use backend\modules\followUp\models\FollowUp;
use backend\modules\followUp\models\FollowUpSearch;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use backend\modules\followUp\models\FollowUpConnectionReports;
use common\models\Model;
use yii\helpers\ArrayHelper;
use backend\modules\contracts\models\Contracts;
use backend\modules\notification\models\Notification;
use common\components\customersInformation;

/**
 * FollowUpController implements the CRUD actions for FollowUp model.
 */
class FollowUpController extends Controller
{

    /**
     * {@inheritdoc}
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
                        'actions' => ['logout', 'index', 'create', 'delete', 'send-sms', 'view', 'update', 'find-next-contract', 'add-new-loan', 'printer', 'clearance', 'change-status', 'custamer-info'],
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
     * Lists all FollowUp models.
     * @return mixed
     */
    public function actionIndex($contract_id, $notificationID = 0)
    {
        if ($notificationID != 0) {
            Yii::$app->notifications->setReaded($notificationID);
        }
        $model = new FollowUp();
        $searchModel = new FollowUpSearch();
        $queryParams = Yii::$app->request->queryParams;
        $dataProvider = $searchModel->search($queryParams, $contract_id);
        $contract_model = \backend\modules\contracts\models\Contracts::findOne($contract_id);
//        if ($contract_model->is_locked()) {
//            throw new \yii\web\HttpException(403, 'هذا العقد مقفل ومتابع من قبل موظف اخر.');
//        } else {
//            $contract_model->unlock();
//            $contract_model->lock();
//        }
        return $this->render('index', [
            'model' => $model,
            'contract_id' => $contract_id,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'contract_model' => $contract_model,
            'modelsPhoneNumbersFollwUps' => [new FollowUpConnectionReports],
        ]);
    }

    /**
     * Displays a single FollowUp model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($contract_id, $id,$notificationID=null)
    {

            $request = Yii::$app->request;
            $model = $this->findModel($id);
            $modelsPhoneNumbersFollwUps = FollowUpConnectionReports::find()->where(['os_follow_up_id' => $id])->all();

            $contract_model = \backend\modules\contracts\models\Contracts::findOne($contract_id);

            if ($model->load($request->post())) {
                $oldIDs = yii\helpers\ArrayHelper::map($modelsPhoneNumbersFollwUps, 'os_follow_up_id', 'os_follow_up_id');
                $modelsPhoneNumbersFollwUps = Model::createMultiple(FollowUpConnectionReports::classname(), $modelsPhoneNumbersFollwUps);

                Model::loadMultiple($modelsPhoneNumbersFollwUps, Yii::$app->request->post());
                $deletedIDs = array_diff($oldIDs, array_filter(yii\helpers\ArrayHelper::map($modelsPhoneNumbersFollwUps, 'os_follow_up_id', 'os_follow_up_id')));
                // validate all models
                $valid = $model->validate();
                $valid = Model::validateMultiple($modelsPhoneNumbersFollwUps) && $valid;
                if ($valid) {
                    $transaction = \Yii::$app->db->beginTransaction();
                    try {
                        if ($flag = $model->save(false)) {
                            if (!empty($deletedIDs)) {
                                FollowUpConnectionReports::deleteAll(['id' => $deletedIDs]);
                            }
                            foreach ($modelsPhoneNumbersFollwUps as $modelFollowUpConnectionReports) {
                                $modelFollowUpConnectionReports->os_follow_up_id = $model->id;
                                if (!($addressFlag = $modelFollowUpConnectionReports->save(false))) {

                                    $transaction->rollBack();
                                    break;
                                }
                            }
                        }
                        if ($flag) {
                            $contract_model->unlock();
                            $transaction->commit();
                            return $this->redirect(['update',
                                'id' => $model->id,
                                'contract_id' => $contract_id]);
                        }
                    } catch (Exception $e) {
                        $transaction->rollBack();
                    }
                }

                return $this->render('update', [
                    'model' => $model,
                    'contract_id' => $contract_id,
                    'contract_model' => $contract_model,
                    'modelsPhoneNumbersFollwUps' => (empty($modelsPhoneNumbersFollwUps)) ? [new FollowUpConnectionReports] : $modelsPhoneNumbersFollwUps,
                ]);
            } else {
                return $this->render('update', [
                    'model' => $model,
                    'contract_id' => $contract_id,
                    'contract_model' => $contract_model,
                    'modelsPhoneNumbersFollwUps' => (empty($modelsPhoneNumbersFollwUps)) ? [new FollowUpConnectionReports] : $modelsPhoneNumbersFollwUps,
                ]);
            }
        }


    /**
     * Creates a new FollowUp model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($contract_id)
    {

        $model = new FollowUp();
        $modelsPhoneNumbersFollwUps = [new FollowUpConnectionReports];


        if ($model->load(Yii::$app->request->post())) {
            $contractModel = Contracts::findOne($contract_id);
            $modelsPhoneNumbersFollwUps = Model::createMultiple(FollowUpConnectionReports::classname(), $modelsPhoneNumbersFollwUps);
            Model::loadMultiple($modelsPhoneNumbersFollwUps, Yii::$app->request->post());
            $valid = $model->validate();
            $valid = Model::validateMultiple($modelsPhoneNumbersFollwUps) && $valid;
            if ($valid) {
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    if ($flag = $model->save(false)) {
                        $modelsPhoneNumbersFollwUpFlag = false;
                        if (!empty($modelsPhoneNumbersFollwUps)) {
                            foreach ($modelsPhoneNumbersFollwUps as $modelsPhoneNumbersFollwUp) {
                                $modelsPhoneNumbersFollwUp->os_follow_up_id = $model->id;
                                if (empty($modelsPhoneNumbersFollwUp->connection_response) && empty($modelsPhoneNumbersFollwUp->note) && empty($modelsPhoneNumbersFollwUp->connection_type) && empty($modelsPhoneNumbersFollwUp->customer_name)) {
                                    break;
                                }
                                if (!($modelsPhoneNumbersFollwUpFlag = $modelsPhoneNumbersFollwUp->save(false))) {
                                    $transaction->rollBack();
                                    var_dump($modelsPhoneNumbersFollwUp->getErrors());
                                    break;
                                }
                            }
                            if ($flag && $modelsPhoneNumbersFollwUpFlag) {
                                $contractModel->unlock();
                                $transaction->commit();
                                return $this->redirect(['/followUpReport/follow-up-report']);
                            }
                        } else {
                            $contractModel->unlock();
                            $transaction->commit();
                            return $this->redirect(['/followUpReport/follow-up-report']);
                        }
                    }
                } catch (Exception $e) {
                    $transaction->rollBack();
                    var_dump($model->getErrors());
                }
            }
            Yii::$app->session->setFlash('success', Yii::t('app', "follow up created successfully."));
            return $this->redirect(['index', 'contract_id' => $contract_id]);
        }

        return $this->redirect([
                'index',
                'contract_id' => $contract_id,
                'modelsPhoneNumbersFollwUps' => (empty($modelsPhoneNumbersFollwUps)) ? [new FollowUpConnectionReports] : $modelsPhoneNumbersFollwUps,
            ]
        );
    }

    /**
     * Updates an existing FollowUp model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($contract_id, $id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);
        $modelsPhoneNumbersFollwUps = FollowUpConnectionReports::find()->where(['os_follow_up_id' => $id])->all();

        $contract_model = \backend\modules\contracts\models\Contracts::findOne($contract_id);

        if ($model->load($request->post())) {
            $oldIDs = yii\helpers\ArrayHelper::map($modelsPhoneNumbersFollwUps, 'os_follow_up_id', 'os_follow_up_id');
            $modelsPhoneNumbersFollwUps = Model::createMultiple(FollowUpConnectionReports::classname(), $modelsPhoneNumbersFollwUps);

            Model::loadMultiple($modelsPhoneNumbersFollwUps, Yii::$app->request->post());
            $deletedIDs = array_diff($oldIDs, array_filter(yii\helpers\ArrayHelper::map($modelsPhoneNumbersFollwUps, 'os_follow_up_id', 'os_follow_up_id')));
            // validate all models
            $valid = $model->validate();
            $valid = Model::validateMultiple($modelsPhoneNumbersFollwUps) && $valid;
            if ($valid) {
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    if ($flag = $model->save(false)) {
                        if (!empty($deletedIDs)) {
                            FollowUpConnectionReports::deleteAll(['id' => $deletedIDs]);
                        }
                        foreach ($modelsPhoneNumbersFollwUps as $modelFollowUpConnectionReports) {
                            $modelFollowUpConnectionReports->os_follow_up_id = $model->id;
                            if (!($addressFlag = $modelFollowUpConnectionReports->save(false))) {

                                $transaction->rollBack();
                                break;
                            }
                        }
                    }
                    if ($flag) {
                        $contract_model->unlock();
                        $transaction->commit();
                        return $this->redirect(['update',
                            'id' => $model->id,
                            'contract_id' => $contract_id]);
                    }
                } catch (Exception $e) {
                    $transaction->rollBack();
                }
            }

            return $this->render('update', [
                'model' => $model,
                'contract_id' => $contract_id,
                'contract_model' => $contract_model,
                'modelsPhoneNumbersFollwUps' => (empty($modelsPhoneNumbersFollwUps)) ? [new FollowUpConnectionReports] : $modelsPhoneNumbersFollwUps,
            ]);
        } else {
            return $this->render('update', [
                'model' => $model,
                'contract_id' => $contract_id,
                'contract_model' => $contract_model,
                'modelsPhoneNumbersFollwUps' => (empty($modelsPhoneNumbersFollwUps)) ? [new FollowUpConnectionReports] : $modelsPhoneNumbersFollwUps,
            ]);
        }
    }

    /**
     * Deletes an existing FollowUp model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the FollowUp model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return FollowUp the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = FollowUp::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    public function actionSendSms()
    {
        $phone_number = Yii::$app->request->post('phone_number');
        $phone_number = strip_tags($phone_number, '+');
        $text = Yii::$app->request->post('text');
        $url = 'http://www.smsapril.com/api.php?comm=sendsms';
        $params = array(
            'to' => $phone_number,
            'sender' => Yii::$app->params['sender'],
            'user' => Yii::$app->params['user'],
            'pass' => Yii::$app->params['pass'],
            'message' => $text,
        );


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        curl_close($ch);

        $response = [
            '-100' => 'المعلومات ناقصه',
            '-110' => 'اسم المستخدم أو كلمة المرور خاطئة',
            '-111' => 'الحساب غير مفعل',
            '-112' => 'حساب مجمد',
            '-113' => 'لا يوجد رصيد كافٍ',
            '-114' => 'الخدمة غير متوفرة في الوقت الحالي',
            '-115' => 'المرسل غير متوفر',
            '-116' => 'اسم المرسل غير صالح'
        ];

        return Json::encode(['status' => $output, 'message' => (isset($response[$output]) ? $response[$output] : '')]);
    }

    public function actionAddNewLoan()
    {
        $monthlyInstallment = (int)(@Yii::$app->request->post('monthly_installment'));
        $newInstallmentDate = @Yii::$app->request->post('new_installment_date');
        $firstInstallmentDate = @Yii::$app->request->post('first_installment_date');
        $contract = (int)(@Yii::$app->request->post('contract_id'));
        $msg = ' ';
        $model = new LoanScheduling();
        $model->first_installment_date = $firstInstallmentDate;
        $model->new_installment_date = $newInstallmentDate;
        $model->monthly_installment = $monthlyInstallment;
        $model->status_action_by = Yii::$app->user->id;
        $model->status = 'pending';
        $model->contract_id = $contract;
        if ($model->save()) {
            $msg = 'تم اضافة التسوية بنجاح';
        } else {
            $msg = 'يجب التاكد من البيات المدخلة';
        }
        return $msg;
    }

    public function actionPrinter($contract_id)
    {
        $this->layout = '/print-template-1';
        return $this->render('printer', [
            'contract_id' => $contract_id,

        ]);
    }

    public function actionClearance($contract_id)
    {
        $this->layout = '/print-template-1';
        return $this->render('clearance', [
            'contract_id' => $contract_id
        ]);
    }

    function actionChangeStatus()
    {
        $id = Yii::$app->request->post('id');
        $statusContent = Yii::$app->request->post('statusContent');
        Contracts::updateAll(['status' => $statusContent], ['id' => $id]);

    }

    function actionCustamerInfo()
    {
        $id = Yii::$app->request->post('customerId');

        $custumers = \backend\modules\customers\models\Customers::find()->where(['id' => $id])->all();

        $custumer_info = [];
        foreach ($custumers as $custumer) {

            $custumer_info['name'] = $custumer->name;
            $custumer_info['id_number'] = $custumer->id_number;
            $custumer_info['birth_date'] = $custumer->birth_date;
            $custumer_info['job_number'] = $custumer->job_number;
            $custumer_info['email'] = $custumer->email;
            $custumer_info['notes'] = $custumer->notes;
            $custumer_info['account_number'] = $custumer->account_number;
            $custumer_info['bank_branch'] = $custumer->bank_branch;
            $custumer_info['primary_phone_number'] = $custumer->primary_phone_number;
            $custumer_info['facebook_account'] = $custumer->facebook_account;
            $custumer_info['sex'] = customersInformation::getSex($custumer->sex);
            $custumer_info['hear_about_us'] = customersInformation::getHearAboutUs($custumer->hear_about_us);
            $custumer_info['citizen'] = customersInformation::getCitizen($custumer->citizen);
            $custumer_info['status'] = customersInformation::getStatus($custumer->status);
            $custumer_info['city'] = customersInformation::getCitys($custumer->city);
            $custumer_info['bank_name'] = customersInformation::getBank($custumer->bank_name);
            $custumer_info['job_title'] = customersInformation::getJobs($custumer->job_title);
            $custumer_info['social_security_number'] = $custumer->social_security_number;
            $custumer_info['is_social_security'] = $custumer->is_social_security;
            $custumer_info['do_have_any_property'] = $custumer->do_have_any_property;

            return json_encode($custumer_info);


        }
    }
}
