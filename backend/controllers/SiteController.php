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

/**
 * Site controllers
 */
class SiteController extends Controller
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

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        /*     $customersModel = new Customers();
             $contractModel = new Contracts();
             $incomeModel = new income();
             $customersDataProvider = new ActiveDataProvider([
                 'query' => $customersModel->find()->limit(10)->orderBy([
                     'id' => SORT_DESC
                 ]),
                 'pagination' => false,
             ]);
             $contractSataProvider = new ActiveDataProvider([
                 'query' => $contractModel->find()->limit(10)->orderBy([
                     'id' => SORT_DESC
                 ]),
                 'pagination' => false,
             ]);
             $incomeDataProvider = new ActiveDataProvider([
                 'query' => $incomeModel->find()->limit(10)->orderBy([
                     'id' => SORT_DESC
                 ]),
                 'pagination' => false,
             ]);
             return $this->render('index', [
                         'contractSataProvider' => $contractSataProvider,
                         'customersDataProvider' => $customersDataProvider,
                         'incomeDataProvider' => $incomeDataProvider,
             ]);*/
        $this->redirect('followUpReport/follow-up-report');

    }

    /**
     * Login action.
     *
     * @return string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            //return $this->goBack();
            return $this->redirect(['customers/customers/index']);
        } else {
            $model->password = '';

            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Logout action.
     *
     * @return string
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
            //set flash error: No Data Found To Import
            //return $this->>render('import_result',['notImportedData'=>[]]) view and print flash message there
        }
        $notImportedData = [];
        for ($i = 16; $i < $sheetDataCount; $i++) {
            $model = new Expenses();
            //fill all attributes here
            if (!$model->save()) {
                array_push($notImportedData, $model->attributes);
            }
        }
        //set flash success: count($notImportedData) Record has been imported
        //return $this->>render('import_result',['notImportedData'=>$notImportedData]) view and print flash message there

    }

}
