<?php

use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use yii\data\SqlDataProvider;
use johnitvn\ajaxcrud\CrudAsset;
use kartik\grid\GridView;
use \common\helper\LoanContract;
use \backend\modules\contractInstallment\models\ContractInstallment;
use common\components\CompanyChecked;

/* @var $this \yii\web\View */
/* @var $content string */
$CompanyChecked = new CompanyChecked();
$primary_company = $CompanyChecked->findPrimaryCompany();
if ($primary_company == '') {
    $logo = $logo = Yii::$app->params['companies_logo'];
    $companyName = '';
    $compay_banks = '';

} else {
    $logo = $primary_company->logo;
    $companyName = $primary_company->name;
    $compay_banks = $CompanyChecked->findPrimaryCompanyBancks();
}
CrudAsset::register($this);
$clientInContract = \backend\modules\customers\models\ContractsCustomers::find()->where(['customer_type' => 'client'])->where(['contract_id' => $contract_id])->all();
$guarantorInContract = \backend\modules\customers\models\ContractsCustomers::find()->where(['customer_type' => 'guarantor'])->where(['contract_id' => $contract_id])->all();
$modelf = new LoanContract;
$contractModel = $modelf->findContract($contract_id);
$total = $contractModel->total_value;
$campanyModel = \backend\modules\companies\models\Companies::findOne(['id' => $contractModel->company_id]);
$judicary_contract = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $contractModel->id])->all();

if (!empty($judicary_contract)) {
    $all_case_cost = \backend\modules\expenses\models\Expenses::find()->where(['contract_id' => $contractModel->id])->andWhere(['category_id' => 4])->all();
    $sum_case_cost = 0;
    foreach ($all_case_cost as $case_cost) {
        $sum_case_cost = $sum_case_cost + $case_cost->amount;

    }
}
if (!empty($judicary_contract)) {
    $cost = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $contractModel->id])->all();

    foreach ($cost as $cost) {
        $totle_value = $contractModel->total_value + $sum_case_cost + $cost->lawyer_cost;
        $contractModel->total_value = $totle_value;
    }
}
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
    <head>
        <meta charset="<?= Yii::$app->charset ?>"/>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?= Html::csrfMetaTags() ?>
        <?php $this->head() ?>
    </head>
    <body class="hold-transition skin-blue sidebar-mini">
        <?php $this->beginBody() ?>
        <div class="container">
            <div style="background-color: #b1dfbb;border-radius: 7px">
                <div>
                    <center>
                        <h1><?= $companyName ?></h1>
                    </center>
                </div>
                <div>
                    <center>
                        <h2>كشف حساب عميل</h2>
                    </center>
                    <p style="text-align: left;display: inline"><?= date('Y-m-d') ?></p>
                </div>
            </div>
            <div class="body">
                <div>
                    <center>
                        <h3> معلومات الملف</h3>
                    </center>
                </div>
                <div class="information row">
                    <div class="col-lg-6">
                        <div style="text-align: right">
                            <h4>
                                <span style="background-color: #b1dfbb;font-size: 20px;font-weight: 500;padding: 3px;border-radius: 10px;color: white">  اسم المدين : </span>


                                <?php
                                $cont = 1 ;
                                foreach ($clientInContract as $client) {
                                    $clientName = \backend\modules\customers\models\Customers::findOne(['id' => $client->customer_id]);
                                    if($cont == 1){
                                        echo $clientName->name;
                                    }else{
                                        echo " , ". $clientName->name;

                                    }
                                    $cont ++;
                                }
                                ?>


                            </h4>

                        </div>
                        <div style="text-align: right">
                            <h4>
                                <span style="background-color: #b1dfbb;font-size: 20px;font-weight: 500;padding: 3px;border-radius: 10px;color: white">   اسماء الكفلاء :</span>

                                <?php
                                $cont =1 ;
                                foreach ($guarantorInContract as $client) {
                                    $clientName = \backend\modules\customers\models\Customers::findOne(['id' => $client->customer_id]);
                                    if($cont == 1){
                                        echo $clientName->name;
                                    }else{
                                        echo " , ". $clientName->name;



                                    }
                                    $cont ++;
                                }
                                ?>

                            </h4>

                        </div>
                        <div style="text-align: right">
                            <h4>
                                <span style="background-color: #b1dfbb;font-size: 20px;font-weight: 500;padding: 3px;border-radius: 10px;color: white">  تاريخ البيع :</span>
                                <?php
                                echo $contractModel->Date_of_sale;
                                ?>
                            </h4>

                        </div>
                        <div style="text-align: right">
                            <h4>
                                <span style="background-color: #b1dfbb;font-size: 20px;font-weight: 500;padding: 3px;border-radius: 10px;color: white"> تاريخ أول قسط :</span>
                                <?php
                                echo $contractModel->first_installment_date;
                                ?>
                            </h4>

                        </div>
                        <?php
                        if ($contractModel->status == 'judiciary') {
                            ?>
                            <div style="text-align: right">
                                <h4>
                                    <span style="background-color: #b1dfbb;font-size: 20px;font-weight: 500;padding: 3px;border-radius: 10px;color: white"> اتعاب المحامي :</span>
                                    <?php
                                    $cost = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $contractModel->id])->orderBy(['contract_id' => SORT_DESC])->one();

                                    echo $cost->lawyer_cost;
                                    ?>
                                </h4>

                            </div>

                        <?php }
                        ?>
                        <div style="text-align: right">
                            <?php
                            if ($contractModel->status == 'judiciary') {
                            ?>
                            <h4>
                                <span style="background-color: #b1dfbb;font-size: 20px;font-weight: 500;padding: 3px;border-radius: 10px;color: white"> رسوم المحاكم :</span>
                                <?php
                                $cost = \backend\modules\judiciary\models\Judiciary::find()->where(['contract_id' => $contractModel->id])->orderBy(['contract_id' => SORT_DESC])->one();

                                echo $cost->case_cost;
                                ?>
                            </h4>
<?php }?>

                        </div>

                    </div>
                    <div class="col-lg-6">
                        <?php
                        if ($contractModel->status == 'judiciary') {
                            ?>
                            <div style="text-align: right">
                                <h4>
                                    <span style="background-color: #b1dfbb;font-size: 20px;font-weight: 500;padding: 3px;border-radius: 10px;color: white"> المبلغ الاساسي :</span>
                                    <?php

                                    echo  $total
                                    ?>
                                </h4>

                            </div>

                            <div style="text-align: right">
                                <h4>
                                    <span style="background-color: #b1dfbb;font-size: 20px;font-weight: 500;padding: 3px;border-radius: 10px;color: white"> الرصيد المتبقي :</span>
                                    <?php

                                    $paid_amount = ContractInstallment::find()
                                        ->andWhere(['contract_id' => $contractModel->id])
                                        ->sum('amount');

                                    $paid_amount = ($paid_amount > 0) ? $paid_amount : 0;
                                    $custamer_referance = (empty($custamer_referance)) ? 0 : $custamer_referance;


                                    echo ($contractModel->total_value + $custamer_referance) - $paid_amount;

                                    ?>
                                </h4>

                            </div>


                            <div style="text-align: right">
                                <h4>
                                    <span style="background-color: #b1dfbb;font-size: 20px;font-weight: 500;padding: 3px;border-radius: 10px;color: white"> المبلغ الإجمالي :</span>
                                    <?php
                                    echo $contractModel->total_value;
                                    ?>
                                </h4>

                            </div>
                        <?php }
                        ?>
                        <div style="text-align: right">
                            <h4>
                                <span style="background-color: #b1dfbb;font-size: 20px;font-weight: 500;padding: 3px;border-radius: 10px;color: white"> القسط الشهري :</span>
                                <?php
                                echo $contractModel->monthly_installment_value;
                                ?>
                            </h4>
                        </div>
                        <div style="text-align: right">
                            <h4>
                                <span style="background-color: #b1dfbb;font-size: 20px;font-weight: 500;padding: 3px;border-radius: 10px;color: white"> آخر تاريخ دفع :</span>
                                <?php
                                $lastIncomeDate = \backend\modules\contractInstallment\models\ContractInstallment::find()->where(['contract_id' => $contract_id])->orderBy(['date' => SORT_DESC])->one();
                                if (!empty($lastIncomeDate)) {
                                    echo $lastIncomeDate->date;
                                } else {
                                    echo 'لا يوجد ';
                                }
                                ?>
                            </h4>
                        </div>
                        <div style="text-align: right">
                            <h4>
                                <span style="background-color: #b1dfbb;font-size: 20px;font-weight: 500;padding: 3px;border-radius: 10px;color: white"> مجموع دائن :</span>
                                <?php
                                $sumIncome = \backend\modules\contractInstallment\models\ContractInstallment::find()->where(['contract_id' => $contract_id])->sum('amount');
                                if (!empty($sumIncome)) {
                                    echo $sumIncome;
                                } else {
                                    echo 'لا يوجد ';
                                }
                                ?>
                            </h4>

                        </div>



                    </div>
                </div>
                <div>
                    <?php
                    $provider = new SqlDataProvider([
                        'sql' => "SELECT 
                                    os_contracts.id ,
                                    os_contracts.total_value as amount,
                                   'ثمن البضاعة' as description,
                                    os_contracts.Date_of_sale as date,
                                     'مدين' as type,
                                    '' as notes
                                      from os_contracts WHERE os_contracts.id = $contract_id
                                 
                                    UNION 
                                    SELECT 
                                    os_judiciary.id ,
                                    os_judiciary.lawyer_cost as amount,
                                    'اتعاب محاماه' as description,
                                    os_judiciary.created_at as date,
                                     'مدين' as type,
                                   '' as notes
                                      from os_judiciary WHERE os_judiciary.contract_id = $contract_id
                                 
                                     UNION 
                                    SELECT 
                                    os_expenses.id,
                                    os_expenses.amount,
                                    description as description ,
                                   os_expenses.created_at AS date,
                                    'مدين' as type,
                                    notes
                                    from os_expenses WHERE os_expenses.contract_id = $contract_id
                                    UNION
                                    SELECT 
                                    os_income.id,
                                    os_income.amount,
                                    _by as description,
                                    os_income.date as date,
                                    'دائن' as type,
                                    notes
                                    from os_income WHERE os_income.contract_id = $contract_id
                                    order by date 
                                    ; ",
                        'params' => [':status' => 1],
                        'totalCount' => 50,
                        'pagination' => [
                            'pageSize' => 10,
                        ],
                        'sort' => [
                            'attributes' => [
                                'title',
                                'view_count',
                                'created_at',
                            ],
                        ],
                    ]);


                    echo GridView::widget([
                        'id' => 'os_judiciary_customers_actions',
                        'dataProvider' => $provider,
                        'summary' => '',
                        'columns' => [
                            [
                                'class' => '\kartik\grid\DataColumn',
                                'attribute' => 'amount',
                            ],
                            [
                                'class' => '\kartik\grid\DataColumn',
                                'attribute' => 'date',
                            ],
                            [
                                'class' => '\kartik\grid\DataColumn',
                                'attribute' => 'type',
                            ],
                             [
                                'class' => '\kartik\grid\DataColumn',
                                'attribute' => 'description',
                            ],
                            [
                                'class' => '\kartik\grid\DataColumn',
                                'attribute' => 'notes',
                            ]
                        ],
                        'striped' => false,
                        'condensed' => false,
                        'responsive' => false,
                        'export' => false,
                        'panel' => [
                            'type' => false,
                            'heading' => false,
                            'after' => false,
                            'footer' => false
                        ]
                    ])
                    ?>
                </div>

                <div class="footer" style="text-align: right">
                    <h4>

                        <p>.<?= $companyName ?> مسؤولة عن صحة بيانات هذا الكشف حتى تاريخه </p>
                        <p>.<?= $companyName ?> غير مسؤولة عن أي دفعات غير مدرج فيها اسم العميل الرباعي على خانة اسم المودع
                        <p>.<?= $companyName ?> غير مسؤولة عن أي دفعة مدفوعة في أي حساب غير حسابها
                            في <?= $compay_banks ?>   </p>
                    </h4>

                </div>
            </div>
        </div>

        <?php $this->endBody() ?>
    </body>
</html>
<?php $this->endPage() ?>
<?php
$this->registerJs(<<<SCRIPT

SCRIPT
)
?>
