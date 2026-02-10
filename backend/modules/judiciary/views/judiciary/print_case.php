<?php


use common\components\CompanyChecked;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\VarDumper;
use backend\modules\followUp\helper\ContractCalculations;
use backend\modules\lawyers\models\lawyers;

use backend\modules\contractInstallment\models\ContractInstallment;
$CompanyChecked = new CompanyChecked();
$CompanyChecked->id = $model->company_id;
$companyInfo = $CompanyChecked->findCompany();
$moj_logo = Yii::$app->params['moj_logo'];
if ($companyInfo == '') {
    $logo = Yii::$app->params['companies_logo'];
    $compay_name = '';
    $compay_banks = '';

} else {

    $logo = $companyInfo->logo;

    $compay_name = $companyInfo->name;
    $compay_banks = $CompanyChecked->findPrimaryCompanyBancks();

}
$contractCalculations = new ContractCalculations($model->contract_id);
$total_value = $model->contract->total_value;

?>
<style>
    *{font-size: 20px;}
</style>
<page size="A4" >


    <table class="table case-table">
        <tbody>
            <tr>
                <td width="40%">
                    <h3><b>المملكة الأردنية الهاشمية</b></h3>
                    <h3>وزارة العدل</h3>
                </td>
                <td width="20%" class="text-center">
                    <?= Html::img(Url::to(['/' . $moj_logo]), ['style' => 'border-radius: 0;width: 100px;height: auto', 'class' => 'jordan-logo']); ?>
                </td>
                <td width="40%">
                    <h3 class="text-right">الدعاوى التنفيذية</h3>
                </td>
            </tr>
        </tbody>
    </table>

    <h4 class="text-center">تعهد بصحة المعلومات</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>إسم الدائن</th>
                <th>الرقم الوطني للمنشأة</th>
                <th>العنوان </th>
                <th>رقم الهاتف</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <?= $companyInfo->name; ?>
                </td>
                <td>
                    <?= $companyInfo->company_social_security_number ?>
                </td>
                <td>
                    <?= $companyInfo->company_address ?>
                </td>
                <td>
                    <?= $companyInfo->phone_number ?>
                </td>
            </tr>
        </tbody>
    </table>

    <h5>مفوض المحكوم له:
        <?= $model->lawyer->name ?>
    </h5>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th width="20px">#</th>
                <th>إسم المدين</th>
                <th>الرقم الوطني</th>
                <th>العنوان ورقم الهاتف</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $number = 1;
            foreach ($model->customersAndGuarantor as $key => $Customers) {
                ?>
                <tr>
                    <td>
                        <?= $number++ ?>
                    </td>
                    <td>
                        <?= $Customers->name ?>
                    </td>
                    <td>
                        <?= $Customers->id_number ?>
                    </td>
                    <td>
                        <?= $model->informAddress->address ?>
                        <?= $Customers->primary_phone_number ?>
                    </td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>

    <h5 style="margin: 40px 0;line-height: 30px;"><b>لا مانع من رد المبالغ على IBAN رقم: <br>
            <span dir="ltr">
                <?= $companyInfo->primeryBankAccount->iban_number ?>
            </span>(
            <?= $companyInfo->name ?> ) مرفق الـ IBAN مصدق من
            <?= $companyInfo->primeryBankAccount->bank->name ?>
        </b></h5>

   <div style="    text-align: center;
    margin-left: 20%;">
    <?php
    $lawyer_images =lawyers::getLawyerImage($model->lawyer->id);
   ?> 
   <table style="">
   
   <?php foreach($lawyer_images as $image){?>

    <td ><?=    Html::img(Url::to(['/'.$image->image]), ['style' => 'width: 500px;height:300px ;object-fit: contain; margin-top: 20px']);
            ?></td>
   <?php }?>
    </table>
   </div >
<footer class="  p-3 text-center text-lg-start" >
   <h5 class="text-center">أنا الموقع أدناه
        <?= $model->lawyer->name ?> أتعهد بأن جميع البيانات الواردة أعلاه صحيحة وبحسب
        ما أفاد المدين.
    </h5>


    <table class="table case-table">
        <tbody>
            <tr>
                <td width="50%">
                    <h5>التوقيع</h5>
                </td>
                <td width="50%">
                    <h5 class="text-right">التاريخ <br><br>

                    </h5>
                </td>
            </tr>
        </tbody>
    </table>
</footer>





</page>
<page size="A4">
            
    <table class="table case-table">
        <tbody>
            <tr>
                <td width="18%" style="border-left: 1px solid black!important">
                    <h4 class="text-center">دائرة تنفيذ محكمة</h4>
                    <h4 class="text-center">
                        <?= $model->court->name ?>
                    </h4>
                    <h4 class="text-center">رقم الدعوى التنفيذية</h4>
                </td>

                <td style="border-bottom: 1px solid black!important">
                <h4> <?= Html::img(Url::to(['/' . $moj_logo]), ['style' => 'border-radius: 0;width: 100px;height: auto;margin-right: 46%;', 'class' => 'jordan-logo']); ?>
      </h4>
               
                <h4 class="text-center"><b>المملكة الأردنية الهاشمية</b></h4>
                    <h4 class="text-center">وزارة العدل</h4>
                    <h4 class="text-center">محضر طلبات تنفيذ سندات</h4>
                </td>
            </tr>

            <tr>
                <td width="18%" style="border-left: 1px solid black!important">
                </td>

                <td class="case-talabat-container">

                    <h4><b>السند التنفيذي:</b></h4>


                    <h4>كمبيالة
                        / رقمه: ......
                        / 
                        تاريخ السند:
                        <?= $model->contract->Date_of_sale ?>
                        / تاريخ الإستحقاق:
                        <?= $model->contract->first_installment_date ?>
                       /المبلغ الاصلي :<?=$total_value*1.15?> 
                      / المبلغ المنفذ:    <?php
                                  echo $contractCalculations->getExecutedAmount()
                                   ?>
                                   
                    </h4>



                    <h4 style="margin-top: 30px;" class="text-justify">(
                        <?= $companyInfo->name ?> )
                    </h4>
                    <h4>عنوانه:
                        <?= $companyInfo->company_address ?>
                    </h4>
                    <h4>مفوض المحكوم له:
                        <?= $model->lawyer->name ?>
                    </h4>
                    <?php
                    $number = 1;
                    foreach ($model->customersAndGuarantor as $key => $Customers) {

                        ?>

                        <h4 style="margin-top: 30px;" class="text-justify">
                            <?= $number++ ?>- المحكوم عليه:
                            <?= $Customers->name ?> <span class="pull-right">الرقم الوطني:
                                <?=
                                    $Customers->id_number ?>
                            </span>
                        </h4>
                        <h4>عنوانه: ( الموطن المختار )
                            <?= $model->informAddress->address ?>
                        </h4>
                        <?php
                    }
                    ?>



<footer >

                    <table class="table">
                        <tbody>
                            <tr>
                                <td width="50%">
                                    <h5>مفوض المحكوم له</h5>
                                    <h6>   <?= $model->lawyer->name ?></h6>
                                </td>
                             
                                <td width="50%">
                                    <h5 class="text-right">مأمور التنفيذ<br><br>

                                    </h5>
                                </td>
                            </tr>
                   
                        </tbody>
                    </table>
                </footer>
                  


                    <h1 style="opacity: 0;">ZAJAL</h1>
                    <h1 style="opacity: 0;">ZAJAL</h1>
                    <h1 style="opacity: 0;">ZAJAL</h1>
                    <h1 style="opacity: 0;">ZAJAL</h1>

                </td>
            </tr>

        </tbody>
    </table>
</page>

<?php
$total_value = empty($model->contract->total_value) ? '0' : $model->contract->total_value;
$script = <<<JS
$(document).ready(function(){
    $('#amount_after_first_installment').text(tafqeet($total_value)+' دينار اردني فقط لاغير');
}); 
JS;
$this->registerJs($script, $this::POS_END);