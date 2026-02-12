<?php

namespace backend\controllers;

use common\models\Expenses;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use backend\modules\contracts\models\Contracts;
use yii\data\ActiveDataProvider;
use backend\modules\customers\models\Customers;
use common\models\Income;
use yii\db\Query;

/**
 * Site controller — Dashboard + Auth
 */
class SiteController extends Controller
{

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'error', 'import'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index', 'update', 'create', 'delete'],
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

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * لوحة التحكم الرئيسية
     */
    public function actionIndex()
    {
        $companyId = isset(Yii::$app->params['company_id']) ? Yii::$app->params['company_id'] : null;
        $db = Yii::$app->db;

        // ─── الاستعلامات مغلفة بـ try-catch لمنع كسر الصفحة ───
        $contractsByStatus = [];
        $totalContracts = 0;
        $totalContractValue = 0;
        $totalCustomers = 0;
        $monthlyIncome = 0;
        $yearlyIncome = 0;
        $monthlyExpenses = 0;
        $totalCases = 0;
        $totalSettlements = 0;
        $incomeChart = [];
        $recentPayments = [];
        $recentContracts = [];
        $topCollectors = [];

        try {
            // ─── KPI: عدد العقود حسب الحالة ───
            $contractStats = $db->createCommand(
                "SELECT status, COUNT(*) as cnt, COALESCE(SUM(total_value),0) as total_value
                 FROM os_contracts WHERE is_deleted=0
                 " . ($companyId ? "AND company_id=:cid" : "") . "
                 GROUP BY status",
                $companyId ? [':cid' => $companyId] : []
            )->queryAll();

            foreach ($contractStats as $row) {
                $contractsByStatus[$row['status']] = (int)$row['cnt'];
                $totalContracts += (int)$row['cnt'];
                $totalContractValue += (float)$row['total_value'];
            }
        } catch (\Exception $e) { Yii::error($e->getMessage()); }

        try {
            $totalCustomers = (int)$db->createCommand("SELECT COUNT(*) FROM os_customers")->queryScalar();
        } catch (\Exception $e) {}

        try {
            $monthlyIncome = (float)$db->createCommand(
                "SELECT COALESCE(SUM(amount),0) FROM os_income WHERE YEAR(`date`)=:y AND MONTH(`date`)=:m",
                [':y' => date('Y'), ':m' => date('m')]
            )->queryScalar();
        } catch (\Exception $e) {}

        try {
            $yearlyIncome = (float)$db->createCommand(
                "SELECT COALESCE(SUM(amount),0) FROM os_income WHERE YEAR(`date`)=:y",
                [':y' => date('Y')]
            )->queryScalar();
        } catch (\Exception $e) {}

        try {
            $monthlyExpenses = (float)$db->createCommand(
                "SELECT COALESCE(SUM(amount),0) FROM os_expenses WHERE YEAR(expenses_date)=:y AND MONTH(expenses_date)=:m",
                [':y' => date('Y'), ':m' => date('m')]
            )->queryScalar();
        } catch (\Exception $e) {}

        try {
            $totalCases = (int)$db->createCommand(
                "SELECT COUNT(*) FROM os_judiciary" . ($companyId ? " WHERE company_id=:cid" : ""),
                $companyId ? [':cid' => $companyId] : []
            )->queryScalar();
        } catch (\Exception $e) {}

        try {
            $totalSettlements = (int)$db->createCommand("SELECT COUNT(*) FROM os_loan_scheduling WHERE is_deleted=0")->queryScalar();
        } catch (\Exception $e) {}

        try {
            $incomeChart = $db->createCommand(
                "SELECT DATE_FORMAT(`date`, '%Y-%m') as month_key, SUM(amount) as total
                 FROM os_income
                 WHERE `date` >= :startDate
                 GROUP BY DATE_FORMAT(`date`, '%Y-%m')
                 ORDER BY month_key ASC",
                [':startDate' => date('Y-m-d', strtotime('-11 months', strtotime(date('Y-m-01'))))]
            )->queryAll();
        } catch (\Exception $e) {}

        try {
            $recentPayments = $db->createCommand(
                "SELECT i.id, i.date, i.amount, i.contract_id, c.name as customer_name
                 FROM os_income i
                 LEFT JOIN os_contracts ct ON ct.id = i.contract_id
                 LEFT JOIN os_contracts_customers cc ON cc.contract_id = ct.id AND cc.customer_type = 'client'
                 LEFT JOIN os_customers c ON c.id = cc.customer_id
                 ORDER BY i.id DESC LIMIT 10"
            )->queryAll();
        } catch (\Exception $e) {}

        try {
            $recentContracts = $db->createCommand(
                "SELECT ct.id, ct.Date_of_sale, ct.total_value, ct.status, ct.monthly_installment_value, c.name as customer_name
                 FROM os_contracts ct
                 LEFT JOIN os_contracts_customers cc ON cc.contract_id = ct.id AND cc.customer_type = 'client'
                 LEFT JOIN os_customers c ON c.id = cc.customer_id
                 WHERE ct.is_deleted=0
                 " . ($companyId ? "AND ct.company_id=:cid" : "") . "
                 ORDER BY ct.id DESC LIMIT 10",
                $companyId ? [':cid' => $companyId] : []
            )->queryAll();
        } catch (\Exception $e) {}

        try {
            $topCollectors = $db->createCommand(
                "SELECT u.id, p.name as emp_name, SUM(i.amount) as collected
                 FROM os_income i
                 LEFT JOIN os_user u ON u.id = i._by
                 LEFT JOIN os_profile p ON p.user_id = u.id
                 WHERE YEAR(i.`date`)=:y AND MONTH(i.`date`)=:m
                 GROUP BY u.id, p.name
                 ORDER BY collected DESC LIMIT 10",
                [':y' => date('Y'), ':m' => date('m')]
            )->queryAll();
        } catch (\Exception $e) {}

        return $this->render('index', [
            'totalContracts'    => $totalContracts,
            'contractsByStatus' => $contractsByStatus,
            'totalContractValue'=> $totalContractValue,
            'totalCustomers'    => $totalCustomers,
            'monthlyIncome'     => $monthlyIncome,
            'yearlyIncome'      => $yearlyIncome,
            'monthlyExpenses'   => $monthlyExpenses,
            'totalCases'        => $totalCases,
            'totalSettlements'  => $totalSettlements,
            'incomeChart'       => $incomeChart,
            'recentPayments'    => $recentPayments,
            'recentContracts'   => $recentContracts,
            'topCollectors'     => $topCollectors,
        ]);
    }

    /**
     * Login action.
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->redirect(['/site/index']);
        } else {
            $model->password = '';
            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Logout action.
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }

    public function actionImport()
    {
        $extension = 'xlsx';
        $filePath = Yii::getAlias('@backend/web/rptAccountStatment.xlsx');

        if ($extension == 'xlsx') {
            $objReader = new \PHPExcel_Reader_Excel2007();
        } elseif ($extension == 'xls') {
            $objReader = new \PHPExcel_Reader_Excel5();
        } else {
            $objReader = \PHPExcel_IOFactory::createReader($extension);
        }

        $objPHPExcel = $objReader->load($filePath);
        $objPHPExcel->setActiveSheetIndex(0);
        $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);

        $sheetDataCount = count($sheetData);
        if ($sheetData < 16) {
        }
        $notImportedData = [];
        for ($i = 16; $i < $sheetDataCount; $i++) {
            $model = new Expenses();
            if (!$model->save()) {
                array_push($notImportedData, $model->attributes);
            }
        }
    }
}
