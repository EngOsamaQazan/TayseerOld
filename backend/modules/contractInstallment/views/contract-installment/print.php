<?php
/** @var $model \backend\modules\contractInstallment\models\contract-installment**/
//var_dump($model->contract->contractsCustomers);die;
?>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/contract_style.css">
    <title>Contract</title>
</head>
<style>
    table, th, td {
        border: 1px solid black;
        border-collapse: collapse;
    }
    th, td {
        padding: 5px;
        text-align: right;
    }
    th {
        font-size: 17px;
        text-align: right;
    }
</style>
<body>
<section class="contract-header">
    <div class="container">
        <div class="row">
            <div class="padding-6 col-lg-12 col-md-12 col-sm-12" >
                <h2 class="text-center" style="padding-bottom: 15px">ايصال استلام</h2>
            </div>
        </div>
    </div>
</section>
<section class="contract-info">
    <div class="container">

        <div class="row" style="padding-top: 10px">
            <table style="width:100%; padding-bottom: 10px">
                <tr>
                    <th style="width: 150px">اسم الدافع</th>
                    <th style="width: 150px">التاريخ</th>
                </tr>
                <tr style="padding-top:10px">
                    <td><?= $model->_by ?></td>
                    <td><?= $model->date ?></td>
                </tr>
            </table>
        </div>

        <div class="row" style="padding-top: 30px">
            <table style="width:100%; padding-bottom: 10px">
                <tr>
                    <th style="width: 150px">عن عقد السيد</th>
                </tr>
                <?php foreach ($model->contract->contractsCustomers as $value) { ?>
                    <td><?=$value->customer->name?></td>
                <?php } ?>

            </table>
        </div>
        <div class="row" style="padding-top: 10px">
            <div class="padding-6 col-lg-12 col-md-12 col-sm-12 ">
                <div class="col-lg-6 col-md-6 col-sm-6 "></div>
                <div class="col-lg-6 col-md-6 col-sm-6 ">
                    <p>رقم العقد : <span><?= $model->contract_id ?></span></p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="padding-6 col-lg-12 col-md-12 col-sm-12 ">
                <div class="col-lg-6 col-md-6 col-sm-6 "></div>
                <div class="col-lg-6 col-md-6 col-sm-6 ">
                    <p>المبلغ : <span id="amount_written"></span></p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="padding-6 col-lg-12 col-md-12 col-sm-12 ">
                <div class="col-lg-6 col-md-6 col-sm-6 "></div>
                <div class="col-lg-6 col-md-6 col-sm-6 ">
                    <p>اسم المستلم : <span class=""><?= Yii::$app->user->identity->name ?></span></p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-6 "></div>
            <div class="col-lg-6 col-md-6 col-sm-6 ">
                <div class="padding-6 col-lg-12 col-md-12 col-sm-12 ">
                    <?php $payment_type = \backend\modules\paymentType\models\PaymentType::findOne(['id'=>$model->payment_type]); ?>
                    <p>نقدا / شيك : <span class=""><?= !empty($payment_type) ? $payment_type->name : $model->payment_type ?></span>  </p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="padding-6 col-lg-12 col-md-12 col-sm-12 ">
                <div class="col-lg-6 col-md-6 col-sm-6 "></div>
                <div class="col-lg-6 col-md-6 col-sm-6 ">

                    <p> وذلك مقابل : <span class=""><?=  !empty($model->payment_purpose) ?$model->payment_purpose:$model->payment_purpose ?></span></p>
                </div>
            </div>
        </div>
        <div class="row">

            <div class="padding-6 col-lg-12 col-md-12 col-sm-12 ">
                <div class="col-lg-6 col-md-6 col-sm-6 "></div>
                <div class="col-lg-6 col-md-6 col-sm-6 ">
                    <p>هاتف العميل :
                        <?php foreach ($model->contract->customer->phoneNumbers as $number) { ?>
                            <span class=""><?=$number->phone_number?></span>
                            <span class=""> - </span>
                        <?php } ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="padding-6 col-lg-12 col-md-12 col-sm-12 ">
                <div class="col-lg-6 col-md-6 col-sm-6 "></div>
                <div class="col-lg-6 col-md-6 col-sm-6 ">
                    <p>التوقيع:</p>
                </div>
            </div>
        </div>
    </div>
</section>
<script src="/js/jquery-3.3.1.min.js"></script>
<script src="/js/popper.min.js"></script>
<script src="/js/bootstrap.min.js"></script>
<script src="/js/Tafqeet.js"></script>

<?php
$amount = $model->amount;
$script = <<< JS
$(document).ready(function(){
    $('#amount_written').text(tafqeet($amount)+' دينار اردني فقط لاغير');
}); 
JS;
$this->registerJs($script, $this::POS_END);
?>
</body>
</html>