<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use backend\modules\judiciaryActions\models\JudiciaryActions;
/* @var $model */
?>
<div class="questions-bank box box-primary">

    <?php
    $form = yii\widgets\ActiveForm::begin([
                'id' => '_search',
                'method' => 'get',
                'action' => ['index']
    ]);
    ?>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true])->label('الاسم') ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'action_type')->dropDownList(
                JudiciaryActions::getActionTypeList(),
                ['prompt' => '-- جميع الأنواع --']
            )->label('نوع الإجراء') ?>
        </div>
        <div class="col-md-2" style="margin-top: 27px">
            <div class="form-group">
                <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
        <?php yii\widgets\ActiveForm::end() ?>
    </div>
</div>