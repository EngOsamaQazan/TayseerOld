<?php

namespace backend\modules\judiciary\controllers;

use \backend\modules\contractDocumentFile\models\ContractDocumentFile;
use \backend\modules\contracts\models\Contracts;
use \backend\modules\contractCustomers\models\ContractsCustomers;
use backend\modules\customers\models\Customers;
use backend\modules\expenses\models\Expenses;
use \backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions;
use Yii;
use \backend\modules\judiciary\models\Judiciary;
use \backend\modules\judiciary\models\JudiciarySearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use \backend\modules\followUpReport\models\FollowUpReport;
use yii\helpers\Html;
use backend\modules\contractInstallment\models\ContractInstallment;

/**
 * JudiciaryController implements the CRUD actions for Judiciary model.
 */
class JudiciaryController extends Controller
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
                        'actions' => ['add-print-case', 'print-case', 'logout', 'index', 'update', 'create', 'delete', 'view', 'customer-action', 'delete-customer-action', 'report', 'cases-report', 'export-cases-report'],
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

    /* ═══════════════════════════════════════════════════════════
     *  دالة مساعدة: تحويل persistence_status إلى label/color/icon
     * ═══════════════════════════════════════════════════════════ */
    private function parsePersistence(&$row)
    {
        $s = $row['persistence_status'];
        if ($s === 'red_renew') {
            $row['persistence_label'] = 'بحاجة تجديد دعوى';
            $row['persistence_color'] = 'red';
            $row['persistence_icon']  = '🔴';
        } elseif ($s === 'red_due') {
            $row['persistence_label'] = 'مستحق المثابرة';
            $row['persistence_color'] = 'red';
            $row['persistence_icon']  = '🔴';
        } elseif ($s === 'orange_due') {
            $row['persistence_label'] = 'مستحق المثابرة';
            $row['persistence_color'] = 'orange';
            $row['persistence_icon']  = '🟠';
        } elseif ($s === 'green_due') {
            $row['persistence_label'] = 'مستحق المثابرة';
            $row['persistence_color'] = 'green';
            $row['persistence_icon']  = '🟢';
        } elseif (strpos($s, 'remaining_') === 0) {
            $parts  = explode('_', $s);
            $months = isset($parts[1]) ? (int)$parts[1] : 0;
            $days   = isset($parts[2]) ? (int)$parts[2] : 0;
            $row['persistence_label'] = "باقي {$months} شهر و {$days} يوم لاستحقاق المثابرة";
            $row['persistence_color'] = 'green';
            $row['persistence_icon']  = '🟢';
        } else {
            $row['persistence_label'] = $s;
            $row['persistence_color'] = 'gray';
            $row['persistence_icon']  = '⚪';
        }
    }

    /* ═══════════════════════════════════════════════════════════
     *  كشف المثابره — يستخدم VIEW بدل الاستعلام الثقيل
     * ═══════════════════════════════════════════════════════════ */
    public function actionCasesReport()
    {
        $rows = Yii::$app->db->createCommand("SELECT * FROM vw_persistence_report")->queryAll();

        foreach ($rows as &$row) {
            $this->parsePersistence($row);
        }
        unset($row);

        return $this->render('cases_report', ['rows' => $rows]);
    }

    /* ═══════════════════════════════════════════════════════════
     *  تصدير كشف المثابره — XLSX بتنسيقات وألوان كاملة
     *  محسّن: VIEW + تنسيق بالدُفعات لتقليل استهلاك الذاكرة
     * ═══════════════════════════════════════════════════════════ */
    public function actionExportCasesReport()
    {
        /* ── تفعيل التخزين المؤقت لتقليل استهلاك الذاكرة ── */
        $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
        \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, ['memoryCacheSize' => '32MB']);

        /* ── جلب البيانات من VIEW ── */
        $rows = Yii::$app->db->createCommand("SELECT * FROM vw_persistence_report")->queryAll();

        /* ── تحويل حالة المثابرة ── */
        foreach ($rows as &$r) {
            $this->parsePersistence($r);
        }
        unset($r);

        /* ══════════════════════════════════════════
         *  بناء ملف Excel — تنسيق محسّن بالدُفعات
         * ══════════════════════════════════════════ */
        $excel = new \PHPExcel();
        $excel->getProperties()
            ->setCreator('نظام جدل')
            ->setTitle('كشف المثابره');

        $sheet = $excel->getActiveSheet();
        $sheet->setTitle('كشف المثابره');
        $sheet->setRightToLeft(true);

        /* ألوان */
        $HBG = '800020'; $HFG = 'FFFFFF';

        /* العناوين */
        $headers = ['#','رقم القضية','سنة القضية','اسم المحكمة','رقم العقد','اسم العميل','الإجراء الأخير','تاريخ آخر إجراء','مؤشّر المثابرة','آخر متابعة للعقد','آخر تشييك وظيفة','المحامي','الوظيفة','نوع الوظيفة'];
        $colCount = count($headers);
        $lastCol  = \PHPExcel_Cell::stringFromColumnIndex($colCount - 1);

        /* ── صف العنوان الرئيسي (صف 1) ── */
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'كشف المثابره — تاريخ: ' . date('Y-m-d'));
        $sheet->getStyle("A1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => $HFG], 'name' => 'Arial'],
            'fill' => ['type' => \PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => ['rgb' => $HBG]],
            'alignment' => ['horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER, 'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(36);

        /* ── صف الإحصائيات (صف 2) ── */
        $cnt = ['red' => 0, 'orange' => 0, 'green' => 0];
        foreach ($rows as $r) {
            $c = $r['persistence_color'];
            if (isset($cnt[$c])) $cnt[$c]++;
            else $cnt['green']++;
        }
        $total = count($rows);
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', "إجمالي: {$total}  |  عاجل: {$cnt['red']}  |  قريب: {$cnt['orange']}  |  جيد: {$cnt['green']}");
        $sheet->getStyle("A2")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '334155'], 'name' => 'Arial'],
            'fill' => ['type' => \PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => ['rgb' => 'F0F0FF']],
            'alignment' => ['horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER, 'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(26);

        /* ── رؤوس الأعمدة (صف 3) ── */
        $hRow = 3;
        for ($c = 0; $c < $colCount; $c++) {
            $sheet->setCellValueByColumnAndRow($c, $hRow, $headers[$c]);
        }
        $sheet->getStyle("A{$hRow}:{$lastCol}{$hRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => $HFG], 'name' => 'Arial'],
            'fill' => ['type' => \PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => ['rgb' => $HBG]],
            'alignment' => ['horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER, 'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allborders' => ['style' => \PHPExcel_Style_Border::BORDER_THIN, 'color' => ['rgb' => '666666']]],
        ]);
        $sheet->getRowDimension($hRow)->setRowHeight(28);
        $sheet->freezePane('A4');

        /* ── كتابة البيانات ── */
        $keys = ['judiciary_number','case_year','court_name','contract_id','customer_name','last_action_name','last_action_date','persistence_label','last_followup_date','last_job_check_date','lawyer_name','job_title','job_type'];

        /* تجميع الصفوف حسب اللون لتنسيق الدُفعات */
        $redRows = []; $orangeRows = []; $greenRows = []; $oddRows = [];

        $rowNum = $hRow + 1;
        foreach ($rows as $idx => $row) {
            /* كتابة القيم */
            $sheet->setCellValueByColumnAndRow(0, $rowNum, $idx + 1);
            for ($c = 0; $c < count($keys); $c++) {
                $sheet->setCellValueByColumnAndRow($c + 1, $rowNum, $row[$keys[$c]] ?? '');
            }
            /* تصنيف الصفوف */
            $pc = $row['persistence_color'];
            if ($pc === 'red')         $redRows[]    = $rowNum;
            elseif ($pc === 'orange')  $orangeRows[] = $rowNum;
            else                       $greenRows[]  = $rowNum;
            if ($idx % 2 === 1)        $oddRows[]    = $rowNum;
            $rowNum++;
        }
        $lastDataRow = $rowNum - 1;

        /* ── تنسيق بالدُفعات (Range-based) بدل صف-بصف ── */
        $dataRange = "A{$hRow}:{$lastCol}{$lastDataRow}"; // تشمل الهيدر+البيانات

        /* 1. تنسيق أساسي لكل البيانات مرة واحدة */
        $allData = "A" . ($hRow + 1) . ":{$lastCol}{$lastDataRow}";
        $sheet->getStyle($allData)->applyFromArray([
            'font'      => ['size' => 10.5, 'name' => 'Arial'],
            'alignment' => ['horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT, 'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER],
            'borders'   => ['allborders' => ['style' => \PHPExcel_Style_Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
        ]);

        /* 2. الصفوف الزوجية (خلفية مخططة) — بالدُفعة */
        foreach ($oddRows as $or) {
            $sheet->getStyle("A{$or}:{$lastCol}{$or}")->applyFromArray([
                'fill' => ['type' => \PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => ['rgb' => 'F9FAFB']],
            ]);
        }

        /* 3. عمود المثابرة — تلوين بالدُفعات */
        foreach ($redRows as $rr) {
            $sheet->getStyle("I{$rr}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '991B1B']],
                'fill' => ['type' => \PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => ['rgb' => 'FEE2E2']],
                'alignment' => ['horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getStyle("A{$rr}")->applyFromArray([
                'borders' => ['left' => ['style' => \PHPExcel_Style_Border::BORDER_THICK, 'color' => ['rgb' => 'DC2626']]],
            ]);
        }
        foreach ($orangeRows as $or) {
            $sheet->getStyle("I{$or}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '92400E']],
                'fill' => ['type' => \PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => ['rgb' => 'FEF3C7']],
                'alignment' => ['horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getStyle("A{$or}")->applyFromArray([
                'borders' => ['left' => ['style' => \PHPExcel_Style_Border::BORDER_THICK, 'color' => ['rgb' => 'D97706']]],
            ]);
        }
        foreach ($greenRows as $gr) {
            $sheet->getStyle("I{$gr}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '166534']],
                'fill' => ['type' => \PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => ['rgb' => 'DCFCE7']],
                'alignment' => ['horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER],
            ]);
        }

        /* ── عرض الأعمدة ── */
        $widths = [6,12,10,18,10,28,22,14,30,14,14,18,18,16];
        for ($c = 0; $c < $colCount; $c++) {
            $sheet->getColumnDimension(\PHPExcel_Cell::stringFromColumnIndex($c))->setWidth($widths[$c]);
        }

        /* ── فلتر تلقائي ── */
        $sheet->setAutoFilter("A{$hRow}:{$lastCol}{$lastDataRow}");

        /* ══════════════════════════════════════════
         *  حفظ مؤقت ثم إرسال
         * ══════════════════════════════════════════ */
        $filename = 'كشف_المثابره_' . date('Y-m-d') . '.xlsx';
        $tmpFile  = tempnam(sys_get_temp_dir(), 'xl') . '.xlsx';

        $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $writer->save($tmpFile);

        $excel->disconnectWorksheets();
        unset($excel, $rows, $redRows, $orangeRows, $greenRows, $oddRows);

        return Yii::$app->response->sendFile($tmpFile, $filename, [
            'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->on(\yii\web\Response::EVENT_AFTER_SEND, function () use ($tmpFile) {
            @unlink($tmpFile);
        });
    }

    /**
     * Lists all Judiciary models.
     * @return mixed
     */
    public function actionReport()
    {
        $searchModel = new JudiciarySearch();
        $search = $searchModel->report();
        $dataProvider = $search['dataProvider'];
        $counter = $search['count'];

        return $this->render('report', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'counter' => $counter,
        ]);
    }

    /**
     * Lists all Judiciary models.
     * @return mixed
     */
    public function actionIndex()
    {
        $request = Yii::$app->request->queryParams;
        //$db=Yii::$app->db;
        $searchModel = new JudiciarySearch();
        //  $search = $db->cache(function($db) use ($searchModel,$request){

        //       return $searchModel->search($request);
        //  });
        $search = $searchModel->search($request);


        $dataProvider = $search['dataProvider'];
        $counter = $search['count'];

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'counter' => $counter,
        ]);
    }


    /**
     * Displays a single Judiciary model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "Judiciary #" . $id,
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


    public function actionPrintCase($id)
    {
        $request = Yii::$app->request;
        $this->layout = '/print_cases';
        $model =  $this->findModel($id);

        if ($request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'title' => "contracts #" . $id,
                'content' => $this->renderAjax('print_case', [
                    'model' => $model,
                    'id' => $id
                ]),
                'footer' => Html::button('Close', ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) .
                    Html::a('Edit', ['update', 'id' => $id], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])
            ];
        } else {
            return $this->render('print_case', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Creates a new Judiciary model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($contract_id)
    {
        $request = Yii::$app->request;
        $model = new Judiciary();
        if ($model->load($request->post())) {
            $model->contract_id = $contract_id;
            if ($model->input_method == 1) {

                $total_amount = Contracts::findOne(['id' => $contract_id]);
                $total_amount = $total_amount->total_value;
                $paid_amount = ContractInstallment::find()
                    ->andWhere(['contract_id' => $contract_id])
                    ->sum('amount');

                $paid_amount = ($paid_amount > 0) ? $paid_amount : 0;
                $custamer_referance = (empty($custamer_referance)) ? 0 : $custamer_referance;

                $amount =  ($total_amount + $custamer_referance) - $paid_amount;

                $model->lawyer_cost = $amount * ($model->lawyer_cost / 100);
            }

            if ($model->save()) {
                \backend\modules\contracts\models\Contracts::updateAll(['company_id' => $model->company_id, 'status' => 'judiciary'], ['id' => $contract_id]);

                $contractCustamersMosels = \backend\modules\customers\models\ContractsCustomers::find()->where(['contract_id' => $model->contract_id])->all();
                foreach ($contractCustamersMosels as $contractCustamersMosel) {
                    $judicaryCustamerAction = new \backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions();
                    $judicaryCustamerAction->judiciary_id = $model->id;
                    $judicaryCustamerAction->customers_id = $contractCustamersMosel->customer_id;
                    $judicaryCustamerAction->judiciary_actions_id = 1;
                    $judicaryCustamerAction->note = null;
                    $judicaryCustamerAction->action_date = $model->income_date;
                    $judicaryCustamerAction->save();
                }
            }
            $modelContractDocumentFile = new \backend\modules\contractDocumentFile\models\ContractDocumentFile;
            $modelContractDocumentFile->document_type = 'judiciary file';
            $modelContractDocumentFile->contract_id = $model->id;
            $modelContractDocumentFile->save();
            Yii::$app->cache->set(Yii::$app->params['key_judiciary_contract'], Yii::$app->db->createCommand(Yii::$app->params['judiciary_contract_query'])->queryAll(), Yii::$app->params['time_duration']);
            Yii::$app->cache->set(Yii::$app->params['key_judiciary_year'], Yii::$app->db->createCommand(Yii::$app->params['judiciary_year_query'])->queryAll(), Yii::$app->params['time_duration']);
            if (isset($_POST['print'])) {
                return $this->redirect(['print-case', 'id' => $model->id]);
            } else {
                $this->redirect('index');
            }
        } else {
            $queryParams = Yii::$app->request->queryParams;
            $contract_model = \backend\modules\contracts\models\Contracts::findOne($contract_id);
            if ($contract_model->is_locked()) {
                throw new \yii\web\HttpException(403, 'هذا العقد مقفل ومتابع من قبل موظف اخر.');
            } else {
                $contract_model->unlock();
                $contract_model->lock();
            }
            return $this->render('create', [
                'model' => $model,
                'contract_id' => $contract_id,
                'contract_model' => $contract_model,
                'modelsPhoneNumbersFollwUps' => [new \backend\modules\followUpReport\models\FollowUpReport],
            ]);
        }
    }


    /**
     * Updates an existing Judiciary model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);
        $modelCustomerAction = new JudiciaryCustomersActions();

        if ($model->load($request->post())) {
            if ($model->input_method == 1) {

                $total_amount = Contracts::findOne(['id' => $model->contract_id]);
                $total_amount = $total_amount->total_value;
                $paid_amount = ContractInstallment::find()
                    ->andWhere(['contract_id' => $model->contract_id])
                    ->sum('amount');

                $paid_amount = ($paid_amount > 0) ? $paid_amount : 0;
                $custamer_referance = (empty($custamer_referance)) ? 0 : $custamer_referance;

                $amount =  ($total_amount + $custamer_referance) - $paid_amount;

                $model->lawyer_cost = $amount * ($model->lawyer_cost / 100);
            }
            $model->save();

            \backend\modules\contracts\models\Contracts::updateAll(['company_id' => $model->company_id], ['id' => $model->contract_id]);
            Yii::$app->cache->set(Yii::$app->params['key_judiciary_contract'], Yii::$app->db->createCommand(Yii::$app->params['judiciary_contract_query'])->queryAll(), Yii::$app->params['time_duration']);
            Yii::$app->cache->set(Yii::$app->params['key_judiciary_year'], Yii::$app->db->createCommand(Yii::$app->params['judiciary_year_query'])->queryAll(), Yii::$app->params['time_duration']);

            $this->redirect('index');
        }
        return $this->render('update', [
            'model' => $model,
            'modelCustomerAction' => $modelCustomerAction,
            'contract_id' => $model->contract_id
        ]);
    }

    /**
     * Delete an existing Judiciary model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id, $contract_id = null)
    {
        $request = Yii::$app->request;
        $this->findModel($id)->delete();
        $judicarysCustamer = JudiciaryCustomersActions::find()->where(['judiciary_id' => $id])->all();
        $conection = Yii::$app->getDb();
        $conection->createCommand('UPDATE `os_judiciary_customers_actions` SET `is_deleted`=1 WHERE `judiciary_id`=' . $id)->execute();
        if ($contract_id != null) {
            $judicarys = Judiciary::find()->where(['contract_id' => $contract_id])->all();
            if (empty($judicarys)) {
                Contracts::updateAll(['status' => 'active'], ['id' => $contract_id]);
            }
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
     * Delete multiple existing Judiciary model.
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
     * Finds the Judiciary model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Judiciary the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Judiciary::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionCustomerAction($judiciary, $contract_id)
    {
        $modelCustomerAction = new JudiciaryCustomersActions();
        $request = Yii::$app->request;
        $modelCustomerAction->judiciary_id = $judiciary;

        if ($modelCustomerAction->load($request->post())) {
            // Handle file upload
            $uploadedFile = \yii\web\UploadedFile::getInstance($modelCustomerAction, 'image');
            if ($uploadedFile) {
                $filePath = 'uploads/judiciary_customers_actions/' . uniqid() . '.' . $uploadedFile->extension;
                if ($uploadedFile->saveAs($filePath)) {
                    $modelCustomerAction->image = $filePath; // Save the file path to the model
                }
            }

            if ($modelCustomerAction->save()) {
                return $this->redirect(['update', 'id' => $judiciary, 'contract_id' => $contract_id]);
            }
        }

        return $this->render('update', [
            'modelCustomerAction' => $modelCustomerAction,
            'judiciary' => $judiciary,
            'contract_id' => $contract_id,
        ]);
    }

    public function actionDeleteCustomerAction($id, $judiciary)
    {
        $request = Yii::$app->request;
        $model = JudiciaryCustomersActions::findOne($id);
        $model->delete();
        $this->redirect(['update', 'id' => $judiciary]);
    }
}
