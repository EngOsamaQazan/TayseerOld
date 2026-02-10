<?php
/**
 * Created by PhpStorm.
 * User: huda
 * Date: 9/17/20
 * Time: 9:03 PM
 */

use common\components\CompanyChecked;
$CompanyChecked = new CompanyChecked();
$primary_company = $CompanyChecked->findPrimaryCompany();
if ($primary_company == '') {
    $logo = $logo = Yii::$app->params['companies_logo'];
    $compay_name = '';
    $compay_banks = '';
} else {
    $logo = $primary_company->logo;
    $compay_name = $primary_company->name;
    $compay_banks = CompanyChecked::findPrimaryCompanyBancks();
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
    <style>
        p {
            font-size: 21px;
        }
        label{
            font-size: 21px;
        }
        span{
            font-size: 21px;
        }
        .line-frm label {
            font-size: 20px;
        }
        .div-25 {
            height: 0px !important;
        }
    </style>
    <?php
    $total=$model->total_value*=1.15;
    ?>
    <body>

        <!-- Start Contract Header -->
        <section class="contract-header">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                        <h4 class="text-center green-color">اتفاقية الموطن المختار والمحكمة لمختصه</h4>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Contract Header -->
        <!-- Contract Info -->
        <section class="contract-info">
            <div class="container">
                <div class="row mb-4">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                        <div class="form-group">
                            <label>الطرف الأول</label>
                            <label><?= $compay_name?></label>
                        </div>
                        <div class="form-group">
                            <label>الطرف التاني</label>
                            <?php if ($model->type == 'normal') { ?>
                                <span><?= isset($model->customer) ? $model->customer->name : '' ?></span>
                                <?php
                            } else {
                                $count = 1;
                                ?>
                                <?php
                                foreach ($model->customers as $customer) {
                                    if ($count++ != 1) {
                                        ?> و <?php } ?>
                                    <span class=""><?= $customer->name ?>  </span>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                        <p class="mb-4" style="margin-bottom: 0.5rem!important;">اتفق الطرفان على أن تكون محكمة صلح و جزاء و دائرة تنفيذ :</p>
                        <p class="mb-4" style="margin-bottom: 2.5rem!important;">هي المحكـــمة المختصة في أي دعـــوى أو خصومة أو طرح و تنفيذ جميع السنـــــدات التنفيذية و الجزائية المحررة بين الطرفين و أن الموطن المختار للتبليغات القضائية هو </p>
                        <p class="mb-4"> و هو العنــوان الصحيح فقط و يقر الطــرف الثاني أن أي تبليغ من خـلال العنوان المذكور سواء كان تبلغيا بالإلصــــاق أو بالذات يُعتبر تبليغاً أصولياً ، و يسقــــط حقه في إبطـــــال التبليغــــات على هذا العنوان و يقر أيضاً بكافة التبليغات الإلكترونية المرسله على بريده الالكتروني أو على رقم هاتفه التالية : 
                            <?php
                            foreach ($model->customersAndGuarantor as $key) {
                                if (!empty($key->primary_phone_number)) {
                                    echo $key->primary_phone_number . '-';
                                }
                            }
                            foreach ($model->customersAndGuarantor as $key) {
                                if (!empty($key->email)) {
                                    echo $key->email . '-';
                                }
                            }
                            ?>
                        </p>

                        <p>بعد طباعة الكمبيالة رقم :  </p>
                        <p>وبعد الأطلاع والموافقة على جميع البيانات المحررة فيها . تم التوقيع بتاريخ <span class="empty-span">
                         <?=date("Y-m-d");?>
                    </span></p>
                        
                    </div>
                </div>
                <div class="row mb-5">
                    <div class="col-lg-4 col-md-4 col-sm-7 col-12 text-center">
                        <p class="text-center">اسم المدين</p>
                        <?php foreach ($model->customersAndGuarantor as $guarantor) { ?>
                            <p class="text-center"><?= $guarantor->name ?></p>
                        <?php } ?>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 col-12 text-center">
                        <p class="text-center">الرقم الوطني</p>
                        <?php foreach ($model->customersAndGuarantor as $guarantor) { ?>
                            <p class="text-center"><?= $guarantor->id_number ?></p>
                        <?php } ?>
                    </div>
                    <div class="col-lg-3 col-md-3 col-sm-6 col-12 text-center">
                        <p class="text-center">عنوانه</p>
                    </div>
                    <div class="col-lg-2 col-md-2 col-sm-5 col-11 text-center">
                        <p class="text-center">التوقيع</p>
                    </div>
                </div>
                <div class="div-25"></div>
            </div>
        </section>
        <!-- Contract Info -->

        <!-- Contract Body -->
        <section class="contract-body line-frm">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                        <hr class="fat-hr">
                        <h4 class="text-center red-color">كمبيالة</h4>
                        <div class="row mb-5">
                            <div class="col-lg-4 col-md-4 col-sm-6 col-12 text-center">
                                <p class="text-center">اسم المدين</p>
                                <?php foreach ($model->customersAndGuarantor as $guarantor) { ?>
                                    <p class="text-center"><?= $guarantor->name ?></p>
                                <?php } ?>
                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-6 col-12 text-center">
                                <p class="text-center">الرقم الوطني</p>
                                <?php foreach ($model->customersAndGuarantor as $guarantor) { ?>
                                    <p class="text-center"><?= $guarantor->id_number ?></p>
                                <?php } ?>
                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-6 col-12 text-center">
                                <p class="text-center">عنوانه</p>
                            </div>
                        </div>
                        <div class="div-25"></div>
                        <hr class="fat-hr">

                        <div class="row">
                            <div class="col-lg-5 col-md-5 col-sm-5 col-12">
                                <div class="form-group">
                                    <span style="width: 250px !important"></span>
                                    <label class="mr-auto">والدفع بها</label>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-2 col-sm-2 col-12">
                                <div class="form-group">
                                    <table class="table table-bordered">
                                        <thead>
                                        <th>فلس</th>
                                        <th>دينار</th>
                                        </thead>
                                        <tbody>
                                        <td>00</td>
                                        <td><?= $total ?></td>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-5 col-md-5 col-sm-5 col-12">
                                <label>تاريخ الاستحقاق</label>
                                <span><?= $model->due_date ?></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                                <p id="amount_in_words"></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                                <p>  أدفع لأمر <?= $compay_name ?> </p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                                <div class="form-group">
                                    <p class="mb-0">القيمة وصلتنا
                                        <span class="w-100">
                                            <select name="val" id="val">
                                                <option value="بضاعة">بضاعة</option>
                                            </select>
                                        </span> بعد المعاينة والأختبار والقبول تحريرا في  <span class="w-200"><?=date("Y-m-d");?></span>   </p>

                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                                <p class="mb-2">تم طباعة الكمبيالة قبل التوقيع و بعد الإطلاع على البيانات  وبعد الإطلاع على البيانات المطلوبة</p>
                            </div>
                        </div>
                        <div class="row pb-lg-3">
                            <div class="col-lg-4 col-md-4 col-sm-6 col-12 text-center text-center">
                                <p class="">اسم المدين</p>
                                <?php foreach ($model->customersAndGuarantor as $guarantor) { ?>
                                    <p><?= $guarantor->name ?></p>
                                <?php } ?>
                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-6 col-12 text-center text-center">
                                <p class="">الرقم الوطني</p>
                                <?php foreach ($model->customersAndGuarantor as $guarantor) { ?>
                                    <p><?= $guarantor->id_number ?></p>
                                <?php } ?>
                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-6 col-12 text-center text-center">
                                <p class="">التوقيع</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- Contract Body -->
        <!-- Optional JavaScript -->
        <!-- jQuery first, then Popper.js, then Bootstrap JS -->
        <script src="/js-new/jquery-3.3.1.min.js"></script>
        <script src="/js-new/popper.min.js"></script>
        <script src="/js-new/bootstrap.min.js"></script>
        <script src="/js/Tafqeet.js"></script>
        <?php
        $script = <<< JS
$(document).ready(function(){
    $('#amount_in_words').text('فقط مبلغ وقدره '+tafqeet($total)+' دينار اردني فقط لاغير');
}); 
JS;
        $this->registerJs($script, $this::POS_END);
        ?>
    </body>
</html>