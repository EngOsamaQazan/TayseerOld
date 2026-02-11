<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\modules\judiciaryActions\models\JudiciaryActions;

/* @var $this yii\web\View */
/* @var $model backend\modules\judiciaryActions\models\JudiciaryActions */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="questions-bank box box-primary">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'action_type')->dropDownList(
                JudiciaryActions::getActionTypeList(),
                ['prompt' => '-- اختر نوع الإجراء --']
            ) ?>
        </div>
        <div class="col-md-2" style="margin-top: 27px">
            <?php if (!Yii::$app->request->isAjax) { ?>
                <div class="form-group">
                    <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>
