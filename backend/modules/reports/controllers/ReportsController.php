<?php
namespace backend\modules\reports\controllers;

use Yii;
use backend\modules\income\models\IncomeSearch;
use backend\modules\income\models\Income;
use backend\modules\judiciary\models\JudiciarySearch;

use backend\modules\followUp\models\FollowUp;
use backend\modules\followUp\models\FollowUpSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;
use backend\modules\judiciary\models\Judiciary;
use backend\modules\reports\models\CustomersJudiciaryActionsSearch;

/**
 * FollowUpReportsController implements the CRUD actions for FollowUpReports model.
 */
class ReportsController extends Controller
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
                        'actions' => ['customers-judiciary-actions', 'logout', 'judiciary-index', 'index', 'index2', 'update', 'create', 'due-installment', 'month-installments', 'delete', 'monthly-installmentBeer-user', 'monthly-installment', 'total-customer-payments-index', 'total-judiciary-customer-payments-index'],
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
            //             [
//            'class' => 'yii\filters\PageCache',
//            'only' => ['index'],
//            'duration' => 60,
//            'variations' => [
//                \Yii::$app->language,
//            ],
//            'dependency' => [
//                'class' => 'yii\caching\DbDependency',
//                'sql' => 'SELECT COUNT(*) FROM os_follow_up_report',
//            ],
//        ],
        ];
    }

    /**
     * Lists all FollowUpReports models.
     * @return mixed
     */
    public function actionIndex()
    {
        $totalUnpaidInstallment = Yii::$app->db->createCommand("SELECT SUM(Income.total) FROM Income JOIN customers ON Income.customer_id = customers.id WHERE Income.is_made_payment = 0 AND customers.status =1");
        $totalDueInstallment = Yii::$app->db->createCommand("SELECT SUM(Income.total) FROM Income JOIN customers ON Income.customer_id = customers.id WHERE Income.is_made_payment = 0 AND customers.status =1 AND Income.date < LAST_DAY(CURDATE())");
        $totalPaidInstallment = Yii::$app->db->createCommand("SELECT SUM(Income.total) FROM Income WHERE Income.is_made_payment = 1");
        $totalInstallment = Yii::$app->db->createCommand("SELECT SUM(Income.total) FROM Income");
        return $this->render('index', [
            'totalUnpaidInstallment' => $totalUnpaidInstallment->queryScalar(),
            'totalDueInstallment' => $totalDueInstallment->queryScalar(),
            'totalPaidInstallment' => $totalPaidInstallment->queryScalar(),
            'totalInstallment' => $totalInstallment->queryScalar(),
        ]);
    }

    public function actionMonthlyInstallment()
    {

        $monthly_installment = "SELECT SUM(Income.total) AS SUM ,YEAR(Income.date) AS YEAR, MONTH(Income.date) AS MONTH FROM Income JOIN customers ON Income.customer_id = customers.id WHERE Income.is_made_payment = 0 AND customers.status = 1 GROUP BY YEAR(Income.date), MONTH(Income.date)";
        $count = count(Yii::$app->db->createCommand($monthly_installment)->queryAll());
        $monthly_installment_dataProvider = new SqlDataProvider([
            'sql' => $monthly_installment,
            'totalCount' => $count,
        ]);
        return $this->render('monthly_installment', [
            'monthly_installment' => $monthly_installment_dataProvider,
        ]);
    }

    public function actionMonthlyInstallmentBeerUser()
    {
        $monthly_installment_monthly_beer_user = "SELECT SUM(Income.total) AS SUM ,YEAR(Income.date) AS YEAR, MONTH(Income.date) AS MONTH, customers.name AS NAME FROM Income 
                                JOIN customers 
                                ON customers.id = Income.customer_id
                                WHERE Income.is_made_payment = 0
                                AND customers.`status` =1
                                GROUP BY YEAR(Income.date), MONTH(Income.date),customers.id";
        $count = count(Yii::$app->db->createCommand($monthly_installment_monthly_beer_user)->queryAll());
        $monthly_installment_beer_user_dataProvider = new SqlDataProvider([
            'sql' => $monthly_installment_monthly_beer_user,
            'totalCount' => $count,
        ]);
        return $this->render('monthly_installment_monthly_beer_user', [
            'monthly_installment_monthly_beer_user' => $monthly_installment_beer_user_dataProvider,
        ]);
    }

    public function actionDueInstallment()
    {
        $due_installment = "SELECT * , SUM(Income.total) as total_sum,COUNT(Income.id) as total_installment FROM Income
                            JOIN customers on customers.id =Income.customer_id
                            WHERE Income.is_made_payment = 0 AND Income.date < CURDATE()
                            AND customers.status = 1
                            GROUP BY customers.id";
        $count = count(Yii::$app->db->createCommand($due_installment)->queryAll());
        $due_installment_dataProvider = new SqlDataProvider([
            'sql' => $due_installment,
            'totalCount' => $count,
        ]);
        return $this->render('due_installment', [
            'due_installment' => $due_installment_dataProvider,
        ]);
    }

    public function actionThisMonthInstallments($date = null)
    {
        if ($date == null) {
            $date = date("Y-m-d");
        }
        $this_month_installments = "SELECT * FROM Income JOIN customers on customers.id =Income.customer_id WHERE Income.is_made_payment = 0 AND customers.status = 1 AND (Income.date between DATE_FORMAT('" . $date . "','%Y-%m-01') AND LAST_DAY('" . $date . "')) GROUP BY customers.id";
        $count = count(Yii::$app->db->createCommand($this_month_installments)->queryAll());
        $this_month_installments_dataProvider = new SqlDataProvider([
            'sql' => $this_month_installments,
            'totalCount' => $count,
        ]);
        return $this->render('this_month_installments', [
            'this_month_installments' => $this_month_installments_dataProvider,
        ]);
    }

    public function actionIndex2()
    {
        $searchModel = new FollowUpSearch();
        $dataProvider = $searchModel->searchReport(Yii::$app->request->queryParams);
        $searchCount = $searchModel->searchReportCount(Yii::$app->request->queryParams);
        return $this->render('/follow-up-reports/index', [
            'searchModel' => $searchModel,
            'count' => $searchCount,
            'dataProvider' => $dataProvider,
        ]);
    }


    /**
     * Displays a single FollowUpReports model.
     * @param integer $id
     * @return mixed
     */
    public function actionTotalCustomerPaymentsIndex()
    {
        $searchModel = new IncomeSearch();
        $dataProvider = $searchModel->totalCustomerPayments(Yii::$app->request->queryParams);
        $sumTotalCustomerPayments = $searchModel->sumTotalCustomerPayments(Yii::$app->request->queryParams);
        $sumTotalCustomerPayments = $sumTotalCustomerPayments;

        return $this->render('/income-reports/TotalCustomerPaymentsIndex.php', [
            'searchModel' => $searchModel,
            'sumTotalCustomerPayments' => $sumTotalCustomerPayments,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionTotalJudiciaryCustomerPaymentsIndex()
    {
        $searchModel = new IncomeSearch();
        $dataProvider = $searchModel->totalJudiciaryCustomerPayments(Yii::$app->request->queryParams);
        $sumTotalCustomerPayments = $searchModel->sumTotalJudiciaryCustomerPayments(Yii::$app->request->queryParams);

        return $this->render('/income-reports/TotalJudiciaryCustomerPaymentsIndex.php', [
            'searchModel' => $searchModel,
            'sumTotalCustomerPayments' => $sumTotalCustomerPayments,
            'dataProvider' => $dataProvider,
        ]);
    }
    public function actionJudiciaryIndex()
    {
        $request = Yii::$app->request->queryParams;
        $db = Yii::$app->db;
        $searchModel = new JudiciarySearch();
        $search = $db->cache(function ($db) use ($searchModel, $request) {

            return $searchModel->reportSearch($request);
        });


        $dataProvider = $search['dataProvider'];
        $counter = $search['count'];

        return $this->render('/judiciary/index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'counter' => $counter,
        ]);
    }

    public function actionCustomersJudiciaryActions()
    {
        $searchModel = new CustomersJudiciaryActionsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('/customers-judiciary-actions-report/index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
}
