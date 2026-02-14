<?php

namespace backend\modules\followUp\controllers;

use backend\modules\customers\Customers;
use backend\modules\loanScheduling\models\LoanScheduling;
use Yii;
use backend\modules\followUp\models\FollowUp;
use backend\modules\followUp\models\FollowUpSearch;
use backend\modules\followUp\models\FollowUpTask;
use backend\modules\followUp\helper\RiskEngine;
use backend\modules\followUp\helper\AIEngine;
use backend\modules\followUp\helper\ContractCalculations;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
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
                        'actions' => ['login', 'error', 'verify-statement'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index', 'create', 'delete', 'send-sms', 'view', 'update', 'find-next-contract', 'add-new-loan', 'printer', 'clearance', 'change-status', 'custamer-info',
                        'panel', 'save-follow-up', 'create-task', 'move-task', 'ai-feedback', 'get-timeline', 'update-judiciary-check', 'customer-image'],
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
        // Redirect to the new OCP panel — full merge
        return $this->redirect(['panel', 'contract_id' => $contract_id, 'notificationID' => $notificationID]);
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

    /**
     * Serve ImageManager image by id (for customer images modal).
     * إذا الملف موجود محلياً يُرسل منه، وإلا يُجلب من جادل (لنماء وغيرها).
     */
    public function actionCustomerImage($id)
    {
        $id = (int) $id;
        $model = \backend\modules\imagemanager\models\Imagemanager::findOne($id);
        if (!$model || empty($model->fileHash)) {
            throw new NotFoundHttpException(Yii::t('app', 'الصورة غير موجودة.'));
        }
        $ext = pathinfo((string) $model->fileName, PATHINFO_EXTENSION) ?: 'jpg';
        $mime = $ext === 'png' ? 'image/png' : ($ext === 'gif' ? 'image/gif' : ($ext === 'webp' ? 'image/webp' : 'image/jpeg'));
        $basePath = Yii::getAlias('@backend/web/images/imagemanager');
        if (!is_dir($basePath)) {
            $basePath = dirname(dirname(dirname(dirname(__DIR__)))) . '/web/images/imagemanager';
        }
        $filePath = $basePath . '/' . $id . '_' . $model->fileHash . '.' . $ext;

        if (is_file($filePath)) {
            return Yii::$app->response->sendFile($filePath, $id . '.' . $ext, [
                'inline' => true,
                'mimeType' => $mime,
            ]);
        }

        // الملف غير موجود محلياً (مثلاً على نماء) → جلب من جادل وعرضه
        $jadalBase = isset(Yii::$app->params['customerImagesBaseUrl']) && Yii::$app->params['customerImagesBaseUrl'] !== ''
            ? rtrim((string) Yii::$app->params['customerImagesBaseUrl'], '/')
            : 'https://jadal.aqssat.co';
        $remoteUrl = $jadalBase . '/images/imagemanager/' . $id . '_' . $model->fileHash . '.' . $ext;
        $context = stream_context_create(['http' => ['timeout' => 10]]);
        $content = @file_get_contents($remoteUrl, false, $context);
        if ($content === false || $content === '') {
            throw new NotFoundHttpException(Yii::t('app', 'تعذّر جلب الصورة.'));
        }
        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', $mime);
        Yii::$app->response->headers->set('Content-Disposition', 'inline; filename="' . $id . '.' . $ext . '"');
        Yii::$app->response->content = $content;
        return Yii::$app->response->send();
    }

    /**
     * Verify account statement barcode: فعال / منتهي الصلاحية / غير صحيح
     * Statement expires when a new payment (دفعة) or expense (مصروف) is added.
     */
    public function actionVerifyStatement($c, $d, $t, $s)
    {
        $secret = Yii::$app->params['statementVerifySecret'] ?? 'jadal-statement-verify-' . (defined('YII_ENV') ? YII_ENV : 'prod');
        $payload = $c . '|' . $d . '|' . $t;
        $expectedSig = hash_hmac('sha256', $payload, $secret);

        $status = 'invalid';   // غير صحيح
        $label = 'غير صحيح';
        $message = 'الباركود غير صالح أو تم التلاعب به.';

        if (!hash_equals($expectedSig, $s)) {
            return $this->render('verify-statement', [
                'status' => $status,
                'label' => $label,
                'message' => $message,
            ]);
        }

        $contractId = (int) $c;
        $statementLastDate = $t; // last movement date at statement print time (Y-m-d)

        $db = Yii::$app->db;
        $currentMax = $db->createCommand("
            SELECT MAX(dt) as mx FROM (
                SELECT DATE(Date_of_sale) AS dt FROM os_contracts WHERE id = :cid
                UNION ALL
                SELECT DATE(created_at) FROM os_judiciary WHERE contract_id = :cid
                UNION ALL
                SELECT DATE(created_at) FROM os_expenses WHERE contract_id = :cid
                UNION ALL
                SELECT DATE(date) FROM os_income WHERE contract_id = :cid
            ) u
        ", [':cid' => $contractId])->queryScalar();

        if (!$currentMax) {
            $status = 'valid';
            $label = 'فعال';
            $message = 'كشف الحساب صالح ولم تُضف حركات جديدة بعد تاريخ إصداره.';
        } elseif ($currentMax > $statementLastDate) {
            $status = 'expired';
            $label = 'منتهي الصلاحية';
            $message = 'تم إضافة دفعة أو مصروف جديد على العقد بعد تاريخ هذا الكشف. يرجى طلب كشف حساب محدث.';
        } else {
            $status = 'valid';
            $label = 'فعال';
            $message = 'كشف الحساب صالح ولم تُضف حركات جديدة بعد تاريخ إصداره.';
        }

        return $this->render('verify-statement', [
            'status' => $status,
            'label' => $label,
            'message' => $message,
            'contract_id' => $contractId,
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

    // ═══════════════════════════════════════════════════════════
    // OCP — Operational Control Panel Actions
    // ═══════════════════════════════════════════════════════════

    /**
     * OCP Panel — Main operational control panel for a single contract
     */
    public function actionPanel($contract_id, $notificationID = 0)
    {
        if ($notificationID != 0) {
            Yii::$app->notifications->setReaded($notificationID);
        }

        $contract = Contracts::findOne($contract_id);
        if (!$contract) {
            throw new NotFoundHttpException('العقد غير موجود');
        }

        // Customer
        $customer = null;
        if ($contract->contractsCustomers) {
            foreach ($contract->contractsCustomers as $cc) {
                if ($cc->customer_type === 'client') {
                    $customer = \backend\modules\customers\models\Customers::findOne($cc->customer_id);
                    break;
                }
            }
        }

        // Risk Assessment
        $riskEngine = new RiskEngine($contract);
        $riskAssessment = $riskEngine->assess();
        $dpd = $riskEngine->getDPD();
        $brokenPromises = $riskEngine->getBrokenPromisesCount();
        $lastPayment = $riskEngine->getLastPayment();

        $riskData = array_merge($riskAssessment, [
            'dpd' => $dpd,
            'broken_promises' => $brokenPromises,
            'last_payment' => $lastPayment,
        ]);

        // AI Recommendations (also loads judiciary data internally)
        $aiEngine = new AIEngine($contract);
        $aiData = $aiEngine->recommend();
        $judiciaryData = $aiEngine->getJudiciaryData();

        // ContractCalculations — needed for old tabs (phone_numbers, payments, settlements, judiciary)
        $calc = new ContractCalculations($contract_id);

        // Financial Snapshot
        $total = $calc->getContractTotal();
        $paid = $calc->paidAmount();
        $shouldPaid = $calc->amountShouldBePaid();
        $remaining = $calc->remainingAmount();
        $overdue = max(0, $calc->deservedAmount());
        $monthlyAmount = $contract->monthly_installment_value ?: 1;
        $overdueInstallments = ($monthlyAmount > 0) ? (int)ceil($overdue / $monthlyAmount) : 0;
        $remainingInstallments = ($monthlyAmount > 0) ? (int)ceil($remaining / $monthlyAmount) : 0;
        $complianceRate = ($shouldPaid > 0) ? min(100, (int)round(($paid / $shouldPaid) * 100)) : 100;

        $financials = [
            'total' => $total,
            'paid' => $paid,
            'remaining' => $remaining,
            'overdue' => $overdue,
            'overdue_installments' => $overdueInstallments,
            'remaining_installments' => $remainingInstallments,
            'compliance_rate' => $complianceRate,
        ];

        // Timeline (combine follow-ups + payments + judiciary actions)
        $timeline = $this->buildTimeline($contract_id);

        // Kanban
        $kanbanData = FollowUpTask::getKanbanData($contract_id);

        // Smart Alerts (now judiciary-aware)
        $alerts = $this->buildAlerts($contract, $riskEngine, $riskAssessment, $dpd, $brokenPromises, $judiciaryData);

        // FollowUp model + search (for old form compatibility)
        $model = new FollowUp();
        $searchModel = new FollowUpSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $contract_id);

        return $this->render('panel', [
            'contract' => $contract,
            'customer' => $customer,
            'riskData' => $riskData,
            'aiData' => $aiData,
            'judiciaryData' => $judiciaryData,
            'kanbanData' => $kanbanData,
            'timeline' => $timeline,
            'financials' => $financials,
            'alerts' => $alerts,
            'contractCalculations' => $calc,
            'contract_id' => $contract_id,
            'model' => $model,
            'dataProvider' => $dataProvider,
            'modelsPhoneNumbersFollwUps' => [new FollowUpConnectionReports],
        ]);
    }

    /**
     * Build timeline from multiple data sources
     */
    private function buildTimeline($contract_id)
    {
        $events = [];

        // Follow-ups
        $followUps = FollowUp::find()
            ->where(['contract_id' => $contract_id])
            ->orderBy(['date_time' => SORT_DESC])
            ->limit(50)
            ->all();

        foreach ($followUps as $fu) {
            $type = 'call';
            if (!empty($fu->promise_to_pay_at)) {
                $type = 'promise';
            }
            $goalLabels = [1 => 'تحصيل', 2 => 'مصالحة', 3 => 'إنهاء عقد'];

            $events[] = [
                'id' => 'fu-' . $fu->id,
                'type' => $type,
                'datetime' => $fu->date_time ? date('Y/m/d H:i', strtotime($fu->date_time)) : '',
                'content' => trim(
                    ($fu->notes ?: '') .
                    ($fu->feeling ? ' | الانطباع: ' . $fu->feeling : '') .
                    (isset($goalLabels[$fu->connection_goal]) ? ' | الهدف: ' . $goalLabels[$fu->connection_goal] : '')
                ),
                'employee' => $fu->createdBy ? $fu->createdBy->username : '',
                'promise_date' => $fu->promise_to_pay_at,
                'amount' => null,
                'pinned' => false,
                'attachments' => [],
                'sort_time' => $fu->date_time ? strtotime($fu->date_time) : 0,
            ];
        }

        // Payments (from os_income)
        $payments = Yii::$app->db->createCommand(
            "SELECT i.*, u.username FROM {{%income}} i LEFT JOIN {{%user}} u ON i.created_by = u.id WHERE i.contract_id = :cid ORDER BY i.date DESC LIMIT 30",
            [':cid' => $contract_id]
        )->queryAll();

        foreach ($payments as $pay) {
            $events[] = [
                'id' => 'pay-' . $pay['id'],
                'type' => 'payment',
                'datetime' => $pay['date'] ? date('Y/m/d', strtotime($pay['date'])) : '',
                'content' => 'دفعة بمبلغ ' . number_format($pay['amount']) . ' د.أ' .
                    ($pay['notes'] ? ' — ' . $pay['notes'] : ''),
                'employee' => $pay['username'] ?? '',
                'promise_date' => null,
                'amount' => $pay['amount'],
                'pinned' => false,
                'attachments' => [],
                'sort_time' => $pay['date'] ? strtotime($pay['date']) : 0,
            ];
        }

        // Judiciary events
        $judiciaryItems = Yii::$app->db->createCommand(
            "SELECT j.*, u.username FROM {{%judiciary}} j LEFT JOIN {{%user}} u ON j.created_by = u.id WHERE j.contract_id = :cid AND j.is_deleted = 0 ORDER BY j.created_at DESC LIMIT 20",
            [':cid' => $contract_id]
        )->queryAll();

        foreach ($judiciaryItems as $jud) {
            $events[] = [
                'id' => 'jud-' . $jud['id'],
                'type' => 'legal',
                'datetime' => $jud['created_at'] ? date('Y/m/d', $jud['created_at']) : '',
                'content' => 'إجراء قضائي — رقم القضية: ' . ($jud['judiciary_number'] ?? '-') .
                    ($jud['lawyer_cost'] ? ' | تكلفة المحامي: ' . number_format($jud['lawyer_cost']) : ''),
                'employee' => $jud['username'] ?? '',
                'promise_date' => null,
                'amount' => null,
                'pinned' => false,
                'attachments' => [],
                'sort_time' => $jud['created_at'] ?: 0,
            ];
        }

        // Sort by time DESC (most recent first)
        usort($events, function ($a, $b) {
            return $b['sort_time'] - $a['sort_time'];
        });

        return $events;
    }

    /**
     * Build smart alerts based on contract state — judiciary-aware
     */
    private function buildAlerts($contract, $riskEngine, $riskAssessment, $dpd, $brokenPromises, $judiciaryData = [])
    {
        $alerts = [];
        $isLegal = in_array($contract->status, ['judiciary', 'legal_department']);

        // ═══ JUDICIARY-SPECIFIC ALERTS ═══
        if ($isLegal) {
            $judiciary = $judiciaryData['judiciary'] ?? null;
            $lastAction = $judiciaryData['last_action'] ?? null;
            $daysSinceLast = $judiciaryData['days_since_last'] ?? 999;
            $stageLabel = $judiciaryData['stage_label'] ?? '';

            if (!$judiciary) {
                // No case registered
                $alerts[] = [
                    'severity' => 'critical',
                    'icon' => 'fa-gavel',
                    'title' => 'عقد قضائي بدون ملف قضائي مسجل!',
                    'description' => 'العقد محول للقضاء لكن لا يوجد ملف قضائي في النظام — يجب تسجيل القضية فوراً',
                    'cta' => ['label' => 'سجّل قضية', 'action' => 'add_judiciary_action'],
                ];
            } else {
                // Case exists — show stage info
                $caseNum = ($judiciary->judiciary_number ?: '-') . '/' . ($judiciary->year ?: '-');
                $courtName = $judiciary->court ? $judiciary->court->name : 'غير محدد';
                $lawyerName = $judiciary->lawyer ? $judiciary->lawyer->name : 'غير محدد';

                $alerts[] = [
                    'severity' => 'info',
                    'icon' => 'fa-gavel',
                    'title' => 'قضية ' . $caseNum . ' — ' . $courtName,
                    'description' => 'المحامي: ' . $lawyerName . ' | المرحلة: ' . $stageLabel . ' | الإجراءات: ' . count($judiciaryData['actions'] ?? []),
                    'cta' => null,
                ];

                // Stale case alert
                if ($lastAction && $daysSinceLast > 14) {
                    $lastActionName = $lastAction->judiciaryActions ? $lastAction->judiciaryActions->name : 'غير محدد';
                    $severity = $daysSinceLast > 30 ? 'critical' : 'warning';
                    $alerts[] = [
                        'severity' => $severity,
                        'icon' => 'fa-clock-o',
                        'title' => 'لا إجراء قضائي منذ ' . $daysSinceLast . ' يوم',
                        'description' => 'آخر إجراء: ' . $lastActionName . ' — يجب تحريك القضية منعاً للترك',
                        'cta' => ['label' => 'إضافة إجراء', 'action' => 'add_judiciary_action'],
                    ];
                } elseif (!$lastAction) {
                    $alerts[] = [
                        'severity' => 'warning',
                        'icon' => 'fa-exclamation-circle',
                        'title' => 'لم يُسجل أي إجراء على القضية',
                        'description' => 'يجب البدء بتسجيل الإجراءات القضائية',
                        'cta' => ['label' => 'إضافة إجراء', 'action' => 'add_judiciary_action'],
                    ];
                }
            }
        }

        // Broken promises (relevant for all statuses including legal — collection continues)
        if ($brokenPromises > 0) {
            $severity = $brokenPromises >= 3 ? 'critical' : 'warning';
            $alerts[] = [
                'severity' => $severity,
                'icon' => 'fa-exclamation-triangle',
                'title' => $brokenPromises . ' وعد/وعود دفع غير منفذة',
                'description' => 'العميل لديه وعود دفع منتهية الصلاحية ولم يتم تنفيذها',
                'cta' => ['label' => 'اتصل الآن', 'action' => 'call'],
            ];
        }

        // No contact for a long time
        $lastContact = $riskEngine->getLastContactDate();
        if ($lastContact) {
            $daysSince = (int)((strtotime('today') - strtotime($lastContact)) / 86400);
            if ($daysSince > 14) {
                $alerts[] = [
                    'severity' => $daysSince > 30 ? 'critical' : 'warning',
                    'icon' => 'fa-phone-slash',
                    'title' => 'لا تواصل منذ ' . $daysSince . ' يوم',
                    'description' => 'يجب التواصل مع العميل في أقرب وقت لتحديث وضعه',
                    'cta' => ['label' => 'اتصل', 'action' => 'call'],
                ];
            }
        } elseif ($lastContact === null) {
            $alerts[] = [
                'severity' => 'info',
                'icon' => 'fa-info-circle',
                'title' => 'لم يتم التواصل مع هذا العميل بعد',
                'description' => 'هذا العقد ليس له سجل تواصل. يُنصح بإجراء أول اتصال',
                'cta' => ['label' => 'اتصال أول', 'action' => 'call'],
            ];
        }

        // DPD Warning — context-aware for legal
        if ($dpd > 30) {
            if ($isLegal) {
                // Already in legal — don't suggest "escalate"
                $alerts[] = [
                    'severity' => 'warning',
                    'icon' => 'fa-calendar-times-o',
                    'title' => 'تأخير ' . $dpd . ' يوم',
                    'description' => 'التأخير مستمر — يجب المثابرة على التحصيل بالتوازي مع القضاء',
                    'cta' => ['label' => 'اتصل', 'action' => 'call'],
                ];
            } else {
                $alerts[] = [
                    'severity' => 'critical',
                    'icon' => 'fa-calendar-times-o',
                    'title' => 'تأخير كبير: ' . $dpd . ' يوم',
                    'description' => 'التأخير تجاوز الحد المسموح. يُنصح بالتصعيد الفوري',
                    'cta' => ['label' => 'صعّد', 'action' => 'legal'],
                ];
            }
        } elseif ($dpd > 7) {
            $alerts[] = [
                'severity' => 'warning',
                'icon' => 'fa-clock-o',
                'title' => 'تأخير ' . $dpd . ' يوم',
                'description' => 'يجب المتابعة الفورية للحفاظ على معدل التحصيل',
                'cta' => ['label' => 'تابع', 'action' => 'call'],
            ];
        }

        // Missing contact info
        $customer = null;
        if ($contract->contractsCustomers) {
            foreach ($contract->contractsCustomers as $cc) {
                if ($cc->customer_type === 'client') {
                    $customer = \backend\modules\customers\models\Customers::findOne($cc->customer_id);
                    break;
                }
            }
        }
        if ($customer && empty($customer->primary_phone_number)) {
            $alerts[] = [
                'severity' => 'info',
                'icon' => 'fa-address-book-o',
                'title' => 'نقص بيانات تواصل',
                'description' => 'لا يوجد رقم هاتف أساسي للعميل. يجب تحديث البيانات',
                'cta' => null,
            ];
        }

        return $alerts;
    }

    /**
     * AJAX: Update last_check_date on a judiciary case
     */
    public function actionUpdateJudiciaryCheck()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        if (!$request->isPost) {
            return ['success' => false, 'message' => 'طريقة الطلب غير صحيحة'];
        }

        $judiciaryId = (int)$request->post('judiciary_id');
        $judiciary = \backend\modules\judiciary\models\Judiciary::findOne($judiciaryId);

        if (!$judiciary) {
            return ['success' => false, 'message' => 'القضية غير موجودة'];
        }

        $judiciary->last_check_date = date('Y-m-d');
        $judiciary->detachBehavior('softDeleteBehavior');
        if ($judiciary->save(false, ['last_check_date'])) {
            return [
                'success' => true,
                'message' => 'تم تحديث تاريخ التشييك',
                'date' => date('Y/m/d'),
            ];
        }

        return ['success' => false, 'message' => 'حدث خطأ أثناء الحفظ'];
    }

    /**
     * AJAX: Save a new follow-up entry from OCP side panels
     */
    public function actionSaveFollowUp()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        if (!$request->isPost) {
            return ['success' => false, 'message' => 'طريقة الطلب غير صحيحة'];
        }

        $contractId = (int)$request->post('contract_id');
        $contract = Contracts::findOne($contractId);
        if (!$contract) {
            return ['success' => false, 'message' => 'العقد غير موجود'];
        }

        $model = new FollowUp();
        $model->contract_id = $contractId;
        $model->created_by = Yii::$app->user->id;
        $model->connection_goal = (int)$request->post('connection_goal', 1);
        $model->feeling = $request->post('feeling', '');
        $model->reminder = $request->post('reminder', date('Y-m-d', strtotime('+3 days')));
        $model->notes = $request->post('notes', '');
        $model->promise_to_pay_at = $request->post('promise_to_pay_at') ?: null;

        if ($model->save()) {
            // Log audit event
            $this->logAudit($contractId, 'follow_up_created', [
                'follow_up_id' => $model->id,
                'action_type' => $request->post('action_type', 'call'),
                'feeling' => $model->feeling,
            ]);

            // Auto-create SLA for promise
            if (!empty($model->promise_to_pay_at)) {
                $this->createPromiseSLA($contractId, $model->promise_to_pay_at);
            }

            return ['success' => true, 'message' => 'تم الحفظ بنجاح', 'id' => $model->id];
        }

        return ['success' => false, 'message' => 'خطأ في الحفظ: ' . implode(', ', $model->getFirstErrors())];
    }

    /**
     * AJAX: Create a new Kanban task
     */
    public function actionCreateTask()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        if (!$request->isPost) {
            return ['success' => false, 'message' => 'طريقة الطلب غير صحيحة'];
        }

        $task = new FollowUpTask();
        $task->contract_id = (int)$request->post('contract_id');
        $task->title = $request->post('title', '');
        $task->description = $request->post('description', '');
        $task->stage = $request->post('stage', 'new');
        $task->priority = $request->post('priority', 'medium');
        $task->due_date = $request->post('due_date') ?: null;
        $task->action_type = $request->post('action_type', '');
        $task->assigned_to = Yii::$app->user->id;
        $task->created_by = Yii::$app->user->id;
        $task->status = FollowUpTask::STATUS_PENDING;

        if ($task->save()) {
            $this->logAudit($task->contract_id, 'task_created', [
                'task_id' => $task->id,
                'stage' => $task->stage,
                'title' => $task->title,
            ]);

            return [
                'success' => true,
                'message' => 'تم إنشاء المهمة بنجاح',
                'task' => $task->toKanbanArray(),
            ];
        }

        return ['success' => false, 'message' => 'خطأ: ' . implode(', ', $task->getFirstErrors())];
    }

    /**
     * AJAX: Move a Kanban task to another stage
     */
    public function actionMoveTask()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        if (!$request->isPost) {
            return ['success' => false, 'message' => 'طريقة الطلب غير صحيحة'];
        }

        $taskId = (int)$request->post('task_id');
        $targetStage = $request->post('target_stage');
        $task = FollowUpTask::findOne($taskId);

        if (!$task) {
            return ['success' => false, 'message' => 'المهمة غير موجودة'];
        }

        $oldStage = $task->stage;
        $task->stage = $targetStage;

        // Governance: escalation requires reason
        if (in_array($targetStage, ['escalation', 'legal'])) {
            $task->escalation_reason = $request->post('escalation_reason', '');
            $task->escalation_type = $request->post('escalation_type', '');
            $task->requires_approval = 1;

            if (empty($task->escalation_reason)) {
                return ['success' => false, 'message' => 'سبب التصعيد مطلوب'];
            }
        }

        // Mark as done if moved to closed
        if ($targetStage === 'closed') {
            $task->status = FollowUpTask::STATUS_DONE;
            $task->completed_at = date('Y-m-d H:i:s');
        }

        if ($task->save()) {
            $this->logAudit($task->contract_id, 'task_moved', [
                'task_id' => $task->id,
                'from_stage' => $oldStage,
                'to_stage' => $targetStage,
                'escalation_reason' => $task->escalation_reason,
            ]);

            return ['success' => true, 'message' => 'تم نقل المهمة'];
        }

        return ['success' => false, 'message' => 'خطأ في النقل'];
    }

    /**
     * AJAX: Record AI recommendation feedback
     */
    public function actionAiFeedback()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $request = Yii::$app->request;

        $contractId = (int)$request->post('contract_id');
        $feedback = $request->post('feedback');

        // Store feedback in ai_recommendations table
        try {
            Yii::$app->db->createCommand()->insert('{{%ai_recommendations}}', [
                'contract_id' => $contractId,
                'recommendation_type' => 'next_best_action',
                'action' => 'feedback_recorded',
                'user_feedback' => $feedback,
                'executed_by' => Yii::$app->user->id,
                'executed_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
            ])->execute();
        } catch (\Exception $e) {
            // Non-critical — don't fail the request
        }

        return ['success' => true];
    }

    /**
     * AJAX: Get timeline HTML (for refresh without full page reload)
     */
    public function actionGetTimeline($contract_id)
    {
        $timeline = $this->buildTimeline($contract_id);
        return $this->renderPartial('panel/_timeline', ['timeline' => $timeline]);
    }

    /**
     * Log an audit event
     */
    private function logAudit($contractId, $eventType, $data = [], $oldValue = null, $newValue = null)
    {
        try {
            Yii::$app->db->createCommand()->insert('{{%ocp_audit_log}}', [
                'contract_id' => $contractId,
                'event_type' => $eventType,
                'event_data' => Json::encode($data),
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'performed_by' => Yii::$app->user->id,
                'created_at' => date('Y-m-d H:i:s'),
            ])->execute();
        } catch (\Exception $e) {
            Yii::error('OCP Audit Log Error: ' . $e->getMessage());
        }
    }

    /**
     * Auto-create SLA for promise-to-pay
     */
    private function createPromiseSLA($contractId, $promiseDate)
    {
        try {
            $dueAt = date('Y-m-d H:i:s', strtotime($promiseDate . ' +24 hours'));
            Yii::$app->db->createCommand()->insert('{{%sla_status}}', [
                'contract_id' => $contractId,
                'rule_code' => 'promise_followup_24h',
                'rule_description' => 'متابعة بعد وعد الدفع خلال 24 ساعة',
                'status' => 'compliant',
                'due_at' => $dueAt,
                'created_at' => date('Y-m-d H:i:s'),
            ])->execute();
        } catch (\Exception $e) {
            Yii::error('SLA Creation Error: ' . $e->getMessage());
        }
    }
}
