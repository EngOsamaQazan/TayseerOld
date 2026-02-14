<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\select2\Select2;
use backend\modules\jobs\models\JobsType;

/* @var $this yii\web\View */
/* @var $model backend\modules\jobs\models\JobsSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<?php $form = ActiveForm::begin([
    'action' => ['index'],
    'method' => 'get',
    'options' => ['data-pjax' => 1],
]); ?>

<div class="box box-primary jadal-search-box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> بحث متقدم</h3>
        <div class="box-tools pull-left">
            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                <i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-3">
                <?= $form->field($model, 'name')->textInput(['placeholder' => 'اسم جهة العمل']) ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'job_type')->widget(Select2::class, [
                    'data' => ArrayHelper::map(JobsType::find()->all(), 'id', 'name'),
                    'options' => ['placeholder' => 'جميع الأنواع'],
                    'pluginOptions' => ['allowClear' => true],
                ]) ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'address_city')->textInput(['placeholder' => 'المدينة']) ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'status')->dropDownList(
                    ['' => 'الكل', 1 => 'فعال', 0 => 'غير فعال']
                ) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 text-left">
                <?= Html::submitButton('<i class="fa fa-search"></i> بحث', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('<i class="fa fa-times"></i> إعادة تعيين', ['index'], ['class' => 'btn btn-default']) ?>
            </div>
        </div>
    </div>
</div>

<?php ActiveForm::end(); ?>
