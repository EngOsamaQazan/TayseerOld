<?php
/**
 * بحث متقدم - العملاء
 * حقول بحث سريعة مع كاش للقوائم المنسدلة
 */
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* جلب البيانات المرجعية من الكاش دفعة واحدة */
$cache = Yii::$app->cache;
$p = Yii::$app->params;
$d = $p['time_duration'];

$status = $cache->getOrSet($p['key_status'], fn() => Yii::$app->db->createCommand($p['status_query'])->queryAll(), $d);
/* العملاء يتم تحميلهم عبر AJAX */
$city = $cache->getOrSet($p['key_city'], fn() => Yii::$app->db->createCommand($p['city_query'])->queryAll(), $d);
$jobs = $cache->getOrSet($p['key_jobs'], fn() => Yii::$app->db->createCommand($p['jobs_query'])->queryAll(), $d);
$jobType = $cache->getOrSet($p['key_job_type'], fn() => Yii::$app->db->createCommand($p['job_type_query'])->queryAll(), $d);
$contractStatus = $cache->getOrSet($p['key_contract_status'], fn() => Yii::$app->db->createCommand($p['contract_status_query'])->queryAll(), $d);
?>

<div class="box box-primary jadal-search-box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> بحث متقدم</h3>
        <div class="box-tools pull-left">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <?php $form = ActiveForm::begin([
            'id' => 'customers-search',
            'method' => 'get',
            'action' => ['index'],
            'options' => ['class' => 'jadal-search-form'],
        ]) ?>

        <div class="row">
            <div class="col-md-3">
                <?= $form->field($model, 'name')->widget(Select2::class, [
                    'initValueText' => $model->name,
                    'options' => ['placeholder' => 'ابحث بالاسم أو الرقم الوطني أو الهاتف...'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl', 'minimumInputLength' => 1,
                        'ajax' => [
                            'url' => \yii\helpers\Url::to(['/customers/customers/search-customers', 'mode' => 'name']),
                            'dataType' => 'json', 'delay' => 250,
                            'data' => new \yii\web\JsExpression('function(p){return{q:p.term}}'),
                            'processResults' => new \yii\web\JsExpression('function(d){return d}'),
                            'cache' => true,
                        ],
                        'templateResult' => new \yii\web\JsExpression("function(i){if(i.loading)return i.text;var h='<div><b>'+i.text+'</b>';if(i.id_number)h+=' <small style=\"color:#64748b\">· '+i.id_number+'</small>';if(i.phone)h+=' <small style=\"color:#0891b2\">☎ '+i.phone+'</small>';return $(h+'</div>')}"),
                        'templateSelection' => new \yii\web\JsExpression("function(i){return i.text||i.id}"),
                    ],
                ])->label('اسم العميل') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'id')->textInput(['placeholder' => 'رقم العميل', 'type' => 'number'])->label('رقم العميل') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'id_number')->textInput(['placeholder' => 'الرقم الوطني'])->label('الرقم الوطني') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'primary_phone_number')->textInput(['placeholder' => 'رقم الهاتف'])->label('رقم الهاتف') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'status')->dropDownList(
                    ArrayHelper::map($status, 'id', 'name'),
                    ['prompt' => '-- جميع الحالات --']
                )->label('الحالة') ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-2">
                <?= $form->field($model, 'city')->dropDownList(
                    ArrayHelper::map($city, 'id', 'name'),
                    ['prompt' => '-- المدينة --']
                )->label('المدينة') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'job_title')->widget(Select2::class, [
                    'data' => ArrayHelper::map($jobs, 'id', 'name'),
                    'options' => ['placeholder' => 'الوظيفة'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('الوظيفة') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'job_Type')->widget(Select2::class, [
                    'data' => ArrayHelper::map($jobType, 'id', 'name'),
                    'options' => ['placeholder' => 'نوع الوظيفة'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('نوع الوظيفة') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'contract_type')->widget(Select2::class, [
                    'data' => ArrayHelper::map($contractStatus, 'status', 'status'),
                    'options' => ['placeholder' => 'حالة العقد'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('حالة العقد') ?>
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
