<?php
/**
 * بحث متقدم - إجراءات العملاء القضائية - بناء من الصفر
 */
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use kartik\select2\Select2;

/* بيانات مرجعية - كاش */
$cache = Yii::$app->cache;
$p = Yii::$app->params;
$d = $p['time_duration'];
$db = Yii::$app->db;

$customers = ArrayHelper::map(
    $cache->getOrSet($p['key_customers_name'], fn() => $db->createCommand($p['customers_name_query'])->queryAll(), $d),
    'name', 'name'
);
$users = ArrayHelper::map(
    $cache->getOrSet($p['key_users'], fn() => $db->createCommand($p['users_query'])->queryAll(), $d),
    'id', 'username'
);
$actions = ArrayHelper::map(\backend\modules\judiciaryActions\models\JudiciaryActions::find()->asArray()->all(), 'id', 'name');
$courts = ArrayHelper::map(\backend\modules\court\models\Court::find()->asArray()->all(), 'id', 'name');
$lawyers = ArrayHelper::map(\backend\modules\lawyers\models\Lawyers::find()->asArray()->all(), 'id', 'name');
$years = ArrayHelper::map(\backend\modules\judiciary\models\Judiciary::find()->select('year')->distinct()->asArray()->all(), 'year', 'year');
?>

<div class="box box-primary jadal-search-box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> بحث في الإجراءات القضائية</h3>
        <div class="box-tools pull-left">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <?php $form = ActiveForm::begin([
            'id' => 'jca-search',
            'method' => 'get',
            'action' => ['index'],
            'options' => ['class' => 'jadal-search-form'],
        ]) ?>

        <div class="row">
            <div class="col-md-2">
                <?= $form->field($model, 'judiciary_id')->textInput(['placeholder' => 'رقم القضية', 'type' => 'number'])->label('رقم القضية') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'customers_id')->widget(Select2::class, [
                    'data' => $customers,
                    'options' => ['placeholder' => 'اسم العميل'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('العميل') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'judiciary_actions_id')->widget(Select2::class, [
                    'data' => $actions,
                    'options' => ['placeholder' => 'الإجراء', 'multiple' => true],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('الإجراء') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'year')->widget(Select2::class, [
                    'data' => $years,
                    'options' => ['placeholder' => 'السنة'],
                    'pluginOptions' => ['allowClear' => true],
                ])->label('السنة') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'contract_id')->textInput(['placeholder' => 'رقم العقد', 'type' => 'number'])->label('العقد') ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-2">
                <?= $form->field($model, 'court_name')->widget(Select2::class, [
                    'data' => $courts,
                    'options' => ['placeholder' => 'المحكمة'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('المحكمة') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'lawyer_name')->widget(Select2::class, [
                    'data' => $lawyers,
                    'options' => ['placeholder' => 'المحامي'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('المحامي') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'created_by')->widget(Select2::class, [
                    'data' => $users,
                    'options' => ['placeholder' => 'المنشئ'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('المنشئ') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'form_action_date')->widget(DatePicker::class, [
                    'options' => ['placeholder' => 'من تاريخ الإجراء'],
                    'pluginOptions' => ['autoclose' => true, 'format' => 'yyyy-mm-dd'],
                ])->label('من تاريخ') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'to_action_date')->widget(DatePicker::class, [
                    'options' => ['placeholder' => 'إلى تاريخ الإجراء'],
                    'pluginOptions' => ['autoclose' => true, 'format' => 'yyyy-mm-dd'],
                ])->label('إلى تاريخ') ?>
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
