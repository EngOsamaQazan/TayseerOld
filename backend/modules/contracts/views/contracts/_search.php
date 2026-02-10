<?php
/**
 * بحث متقدم - العقود
 */
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use kartik\select2\Select2;

$cache = Yii::$app->cache;
$p = Yii::$app->params;
$d = $p['time_duration'];
$db = Yii::$app->db;

$users = $cache->getOrSet($p['key_users'], fn() => $db->createCommand($p['users_query'])->queryAll(), $d);
$customersName = $cache->getOrSet($p['key_customers_name'], fn() => $db->createCommand($p['customers_name_query'])->queryAll(), $d);
$jobType = $cache->getOrSet($p['key_job_type'], fn() => $db->createCommand($p['job_type_query'])->queryAll(), $d);
$jobs = $cache->getOrSet($p['key_jobs'], fn() => $db->createCommand($p['jobs_query'])->queryAll(), $d);

$statusList = [
    '' => '-- جميع الحالات --', 'active' => 'نشط', 'pending' => 'معلّق',
    'legal_department' => 'قانوني', 'judiciary' => 'قضاء', 'settlement' => 'تسوية',
    'finished' => 'منتهي', 'canceled' => 'ملغي', 'refused' => 'مرفوض',
];
?>

<div class="box box-primary jadal-search-box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> بحث في العقود</h3>
        <div class="box-tools pull-left">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <?php $form = ActiveForm::begin(['id' => 'contracts-search', 'method' => 'get', 'action' => ['index'], 'options' => ['class' => 'jadal-search-form']]) ?>

        <div class="row">
            <div class="col-md-2">
                <?= $form->field($model, 'id')->textInput(['placeholder' => 'رقم العقد', 'type' => 'number'])->label('رقم العقد') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'customer_name')->widget(Select2::class, [
                    'data' => ArrayHelper::map($customersName, 'name', 'name'),
                    'options' => ['placeholder' => 'اسم العميل'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('العميل') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'status')->dropDownList($statusList)->label('الحالة') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'from_date')->widget(DatePicker::class, [
                    'options' => ['placeholder' => 'من تاريخ'],
                    'pluginOptions' => ['autoclose' => true, 'format' => 'yyyy-mm-dd'],
                ])->label('من') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'to_date')->widget(DatePicker::class, [
                    'options' => ['placeholder' => 'إلى تاريخ'],
                    'pluginOptions' => ['autoclose' => true, 'format' => 'yyyy-mm-dd'],
                ])->label('إلى') ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-2">
                <?= $form->field($model, 'seller_id')->widget(Select2::class, [
                    'data' => ArrayHelper::map($users, 'id', 'username'),
                    'options' => ['placeholder' => 'البائع'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('البائع') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'followed_by')->widget(Select2::class, [
                    'data' => ArrayHelper::map($users, 'id', 'username'),
                    'options' => ['placeholder' => 'المتابع'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('المتابع') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'phone_number')->textInput(['placeholder' => 'الهاتف'])->label('الهاتف') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'job_Type')->widget(Select2::class, [
                    'data' => ArrayHelper::map($jobType, 'id', 'name'),
                    'options' => ['placeholder' => 'نوع الوظيفة'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('نوع الوظيفة') ?>
            </div>
            <div class="col-md-1">
                <?= $form->field($model, 'number_row')->textInput(['placeholder' => 'عدد', 'type' => 'number'])->label('نتائج') ?>
            </div>
            <div class="col-md-2">
                <div class="form-group" style="margin-top:24px">
                    <?= Html::submitButton('<i class="fa fa-search"></i> بحث', ['class' => 'btn btn-primary btn-block']) ?>
                </div>
            </div>
        </div>

        <?php ActiveForm::end() ?>
    </div>
</div>
