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
                        'actions' => ['logout', 'index', 'update', 'create', 'delete', 'dashboard-data'],
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

        // ─── KPI الرئيسية ───
        $q = new Query();

        // عدد العقود حسب الحالة
        $contractStats = $q->select(['status', 'COUNT(*) as cnt', 'COALESCE(SUM(total_value),0) as total_value'])
            ->from('os_contracts')
            ->where(['is_deleted' => 0])
            ->andFilterWhere(['company_id' => $companyId])
            ->groupBy('status')
            ->all();

        $contractsByStatus = [];
        $totalContracts = 0;
        $totalContractValue = 0;
        foreach ($contractStats as $row) {
            $contractsByStatus[$row['status']] = (int)$row['cnt'];
            $totalContracts += (int)$row['cnt'];
            $totalContractValue += (float)$row['total_value'];
        }

        // عدد العملاء
        $totalCustomers = (new Query())->from('os_customers')
            ->andFilterWhere(['company_id' => $companyId])
            ->count();

        // إيرادات الشهر الحالي
        $monthlyIncome = (new Query())->from('os_income')
            ->where(['YEAR(date)' => date('Y'), 'MONTH(date)' => date('m')])
            ->andFilterWhere(['company_id' => $companyId])
            ->sum('amount') ?: 0;

        // إيرادات السنة الحالية
        $yearlyIncome = (new Query())->from('os_income')
            ->where(['YEAR(date)' => date('Y')])
            ->andFilterWhere(['company_id' => $companyId])
            ->sum('amount') ?: 0;

        // مصاريف الشهر الحالي
        $monthlyExpenses = (new Query())->from('os_expenses')
            ->where(['YEAR(date)' => date('Y'), 'MONTH(date)' => date('m')])
            ->andFilterWhere(['company_id' => $companyId])
            ->sum('amount') ?: 0;

        // عدد القضايا
        $totalCases = (new Query())->from('os_judiciary')
            ->andFilterWhere(['company_id' => $companyId])
            ->count();

        // عدد التسويات
        $totalSettlements = (new Query())->from('os_loan_scheduling')
            ->where(['is_deleted' => 0])
            ->count();

        // ─── بيانات الرسم البياني — إيرادات آخر 12 شهر ───
        $incomeChart = (new Query())
            ->select(["DATE_FORMAT(date, '%Y-%m') as month_key", "SUM(amount) as total"])
            ->from('os_income')
            ->where(['>=', 'date', date('Y-m-d', strtotime('-11 months', strtotime(date('Y-m-01'))))])
            ->andFilterWhere(['company_id' => $companyId])
            ->groupBy("DATE_FORMAT(date, '%Y-%m')")
            ->orderBy("month_key ASC")
            ->all();

        // ─── آخر 10 دفعات ───
        $recentPayments = (new Query())
            ->select(['i.id', 'i.date', 'i.amount', 'i.contract_id', 'c.name as customer_name'])
            ->from('os_income i')
            ->leftJoin('os_contracts ct', 'ct.id = i.contract_id')
            ->leftJoin('os_contracts_customers cc', 'cc.contracts_id = ct.id AND cc.type = "client"')
            ->leftJoin('os_customers c', 'c.id = cc.customers_id')
            ->andFilterWhere(['i.company_id' => $companyId])
            ->orderBy('i.id DESC')
            ->limit(10)
            ->all();

        // ─── آخر 10 عقود ───
        $recentContracts = (new Query())
            ->select(['ct.id', 'ct.Date_of_sale', 'ct.total_value', 'ct.status', 'ct.monthly_installment_value', 'c.name as customer_name'])
            ->from('os_contracts ct')
            ->leftJoin('os_contracts_customers cc', 'cc.contracts_id = ct.id AND cc.type = "client"')
            ->leftJoin('os_customers c', 'c.id = cc.customers_id')
            ->where(['ct.is_deleted' => 0])
            ->andFilterWhere(['ct.company_id' => $companyId])
            ->orderBy('ct.id DESC')
            ->limit(10)
            ->all();

        // ─── أداء الموظفين — أعلى 10 بالإيرادات هذا الشهر ───
        $topCollectors = (new Query())
            ->select(['u.id', "CONCAT(p.name) as emp_name", 'SUM(i.amount) as collected'])
            ->from('os_income i')
            ->leftJoin('os_user u', 'u.id = i._by')
            ->leftJoin('os_profile p', 'p.user_id = u.id')
            ->where(['YEAR(i.date)' => date('Y'), 'MONTH(i.date)' => date('m')])
            ->andFilterWhere(['i.company_id' => $companyId])
            ->groupBy('u.id')
            ->orderBy('collected DESC')
            ->limit(10)
            ->all();

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
