<?php

use common\components\CompanyChecked;
use yii\helpers\Html;
use yii\helpers\Url;
$CompanyChecked = new CompanyChecked();
$primary_company = $CompanyChecked->findPrimaryCompany();
if ($primary_company == '') {
    $logo = $logo = Yii::$app->params['companies_logo'];
    $compay_name = '';
    $compay_banks = '';

} else {

    $logo = $primary_company->logo;

    $compay_name = $primary_company->name;
    $compay_banks = $CompanyChecked->findPrimaryCompanyBancks();

}

?>
    <!doctype html>
    <html lang="en">
    <head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!-- Bootstrap CSS -->
        <link rel="stylesheet" href="/css-new/bootstrap.min.css">
        <link rel="stylesheet" href="/css-new/style.css">
        <title>Contract</title>
    </head>
    <body>
    <section class="contract-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-2 col-md-2 col-sm-2 col-2" >
                    <?=  Html::img(Url::to(['/' . $logo]), ['style' => 'width:155px;height:200px; object-fit: contain; margin-top: 20px']); ?>
                </div>
                <div class="col-lg-10 col-md-10 col-sm-10 col-10" >
                    <?php foreach ($model->contractsCustomers as $contractsCustomers) {
                        if ($contractsCustomers->customer->selectedImagePath) {

                            ?>

                            <div class="col-lg-2 col-md-2 col-sm-2 col-2" style="float: left ; display: inline-block ;padding-right: 1px !important;padding-left: 1px !important;">
                                <img src="<?= $contractsCustomers->customer->selectedImagePath ?>"  class="signutre-div">
                            </div>
                        <?php } ?>
                    <?php } ?>

                </div>

            </div>
        </div>
    </section>
    <hr>
    <!-- End Contract Header -->

    <!-- Contract Info -->
    <section class="contract-info">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                    <div class="form-group">
                        <label> الطرف الأول : </label>
                        <span><?= $compay_name ?></span>
                    </div>
                    <div class="form-group">
                        <label> الطرف الثاني : </label>
                        <?php $count = 1;
                        foreach ($model->customersAndGuarantor as $customer) {
                            if ($count++ != 1) { ?>
                                و
                            <?php } ?>
                            <span class=""><?= $customer->name ?>  </span>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                    <div class="info-div">
                        <p>تعتبر هذه المقدمة جزء من العقد ونقر نحن المشتري والكفلاء بموافقتنا على البنود التالية وعددها
                            5</p>
                        <p>1- حالة البضاعة : إننا استلمنا البضاعة الموصوفة بعد المعاينة سليمة وخالية من المشاكل
                            والعيوب</p>
                        <p>2- الالتزام بالدفع : يلتزم المشتري والكفلاء متضامنين ومتكافلين بدفع ثمن البضاعة المذكورة
                            بالعقد وتحمل كافة المصاريف القضائية وغير القضائية في حالة تخلفنا عن دفع اي قسط من الأقساط
                            المذكورة ويعتبر كامل المبلغ مستحق.</p>
                        <p>3-طريقة الدفع : نلتزم بدفع الأقساط في موعدها من خلال eFAWATEERcom تبويب تمويل وخدمات مالية
                            - <?= $compay_name ?> - تسديد قسط - ادخال الرقم ( <span><?= $model->id ?></span>) ثم اتمام
                            الدفع او في حساب الشركة في <span><?= $compay_banks ?></span></p>
                        <p> 4- كفالة وارجاع البضاعة : كفالة الوكيل حسب الشركة الموزعة والبضاعة المباعة لاترد ولاتستبدل
                            ونلتزم بخسارة
                            <span style="width: 20px;font-weight: 900">(<?= $model->loss_commitment ? $model->loss_commitment : 'صفر' ?>)</span>
                            دينار إذا أردنا إرجاع البضاعة بمدة لاتزيد عن 24 ساعة من تاريخ البيع
                            ولا يمكن ارجاع البضاعه بعد مضي
                            24
                            ساعه مهما كانت الاحوال
                        </p>
                        <p>5- الشركة غير مسئولة عن : سعر البضاعة خارج فروعها وعن أي اتفاقية أو مبلغ غير موثق في
                            العقد</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <hr>

    <!-- Contract Info -->

    <!-- Contract Body -->
    <section class="contract-body">
        <div class="container">

            <div class="row mb-4">
                <div class="col-lg-4 col-md-4 col-sm-6 col-12 text-center">
                    <p class="text-right">اسم المدين</p>
                    <?php $count = 1;
                    foreach ($model->customersAndGuarantor as $customer) {
                        if ($count++ != 1) { ?>
                        <?php } ?>
                        <p class="text-right"><?= $customer->name ?></p>
                    <?php } ?>
                </div>
                <div class="col-lg-2 col-md-2 col-sm-3 col-6 text-center">
                    <p class="text-right">الرقم الوطني</p>
                    <?php foreach ($model->customersAndGuarantor as $customer) { ?>
                        <p class="text-right"><?= $customer->id_number ?></p>
                    <?php } ?>
                </div>
                <div class="col-lg-5 col-md-5 col-sm-7 col-12">
                    <p class="text-center">بيانات العقد</p>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 col-12">
                            <div class="form-group">
                                <label>تاريخ البيع : </label>
                                <label><?= $model->Date_of_sale ?></label>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 col-12">
                            <div class="form-group">
                                <label>البائع : </label>
                                <label><?= $model->seller->name ?></label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>فئة العقد : </label>
                        <label><?= $model->type == 'normal' ? 'فردي' : 'متضامين ' ?></label>
                    </div>
                    <div class="form-group">
                        <label class="small-fnt">تاريخ استحقاق أول قسط : </label>
                        <label class="small-fnt"><?= $model->due_date ?></label>
                    </div>
                    <div class="form-group">
                        <label class="small-fnt">المبلغ بعد الدفعة الأولى : </label>
                        <label class="small-fnt" id="amount_after_first_installment"></label>
                    </div>
                    <div class="form-group">
                        <label>القسط الشهري : </label>
                        <label class="small-fnt" id="monthly_installment_value"></label>
                    </div>
                    <div class="form-group">
                        <label>الدفعة الأولى : </label>
                        <label id="first_installment_value"></label>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <hr>
    <!-- Contract Body -->
    <!-- Contract Footer -->
    <section class="contract-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                    <table class="table  table-bordered sig-table">
                        <thead>
                        <tr>
                            <th width="30.2%">توقيع المدين</th>
                            <th width="30.2%">توقيع الكفيل الأول</th>
                            <th width="30.2%">توقيع الكفيل التاني</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        </tbody>
                    </table>
                    <table class="table  table-bordered sig-table">
                        <thead>
                        <tr>
                            <th width="30.2%">توقيع الكفيل الثالث</th>
                            <th width="30.2%">توقيع الكفيل الرابع</th>
                            <th width="30.2%">توقيع الكفيل الخامس</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-2 col-md-2 col-sm-3 col-12">
                    <table class="table table-bordered small-sig-table">
                        <thead>
                        <tr>
                            <th>توقيع البائع</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-lg-10 col-md-10 col-sm-9 col-12">
                    <p>ملاحظات</p>
                </div>
            </div>
        </div>
    </section>
    <!-- Contract Footer -->
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="/js-new/jquery-3.3.1.min.js"></script>
    <script src="/js-new/popper.min.js"></script>
    <script src="/js-new/bootstrap.min.js"></script>
    <script src="/js/Tafqeet.js"></script>

    </body>
    </html>
<?php
$total_value = empty($model->total_value) ? 'لايوجد قيمه'  : $model->total_value;
$first_installment_value = empty($model->first_installment_value) ? 'لايوجد قيمه'  : (($model->first_installment_value == 0) ? "بدون دفعه" : $model->first_installment_value);
$monthly_installment_value = empty($model->monthly_installment_value)? 'لايوجد قيمه' : $model->monthly_installment_value ;
$script = <<< JS
$(document).ready(function(){
    $('#amount_after_first_installment').text(tafqeet($total_value)+' دينار اردني فقط لاغير');
    $('#monthly_installment_value').text(tafqeet($monthly_installment_value)+' دينار اردني فقط لاغير');
    $('#first_installment_value').text(tafqeet($first_installment_value)+' دينار اردني فقط لاغير');
}); 
JS;
$this->registerJs($script, $this::POS_END);
