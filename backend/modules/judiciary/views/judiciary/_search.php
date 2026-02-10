<?php
/**
 * بحث متقدم - القضايا
 */
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use kartik\date\DatePicker;
use backend\modules\court\models\Court;
use backend\modules\lawyers\models\Lawyers;
use backend\modules\judiciaryType\models\JudiciaryType;
use backend\modules\judiciaryActions\models\JudiciaryActions;

/* بيانات مرجعية */
$courts = ArrayHelper::map(Court::find()->asArray()->all(), 'id', 'name');
$types = ArrayHelper::map(JudiciaryType::find()->asArray()->all(), 'id', 'name');
$lawyers = ArrayHelper::map(Lawyers::find()->asArray()->all(), 'id', 'name');
$actions = ArrayHelper::map(JudiciaryActions::find()->asArray()->all(), 'id', 'name');
?>

<div class="box box-primary jadal-search-box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> بحث في القضايا</h3>
        <div class="box-tools pull-left">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <?php $form = ActiveForm::begin([
            'id' => 'judiciary-search',
            'method' => 'get',
            'action' => ['index'],
            'options' => ['class' => 'jadal-search-form'],
        ]) ?>

        <div class="row">
            <div class="col-md-2">
                <?= $form->field($model, 'id')->textInput(['placeholder' => 'رقم القضية', 'type' => 'number'])->label('رقم القضية') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'contract_id')->textInput(['placeholder' => 'رقم العقد', 'type' => 'number'])->label('رقم العقد') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'court_id')->widget(Select2::class, [
                    'data' => $courts,
                    'options' => ['placeholder' => 'المحكمة'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('المحكمة') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'type_id')->widget(Select2::class, [
                    'data' => $types,
                    'options' => ['placeholder' => 'نوع القضية'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('النوع') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'judiciary_number')->textInput(['placeholder' => 'رقم القضية في المحكمة'])->label('رقم المحكمة') ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <?= $form->field($model, 'lawyer_id')->widget(Select2::class, [
                    'data' => $lawyers,
                    'options' => ['placeholder' => 'المحامي'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('المحامي') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'income_date')->widget(DatePicker::class, [
                    'options' => ['placeholder' => 'تاريخ الورود'],
                    'pluginOptions' => ['autoclose' => true, 'format' => 'yyyy-mm-dd'],
                ])->label('تاريخ الورود') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'year')->dropDownList($model->year(), ['prompt' => '-- السنة --'])->label('السنة') ?>
            </div>
            <div class="col-md-2">
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
