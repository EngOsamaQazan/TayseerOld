<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

use borales\extensions\phoneInput\PhoneInput;
/* @var $this yii\web\View */
/* @var $model common\models\PhoneNumbers */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="questions-bank box box-primary">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'customers_id')->hiddenInput()->label(false) ?>
    <div class="row">
        <div class="col-lg-6">
            <label>رقم الهاتف</label>
            <br>
            <?= $form->field($model, 'phone_number')->widget(PhoneInput::className(), [
                'jsOptions' => [
                    'preferredCountries' => ['jo'],
                ]
            ])->label(false); ?>
        </div>
        <div class="col-lg-6">
            <?= $form->field($model, 'fb_account')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'phone_number_owner')->dropDownList(yii\helpers\ArrayHelper::map(\backend\modules\cousins\models\Cousins::find()->all(),'id','name')) ?>
        </div>
        <div class="col-lg-6">
            <?= $form->field($model, 'owner_name')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <?php if (!Yii::$app->request->isAjax) { ?>
        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>
    <?php } ?>

    <?php ActiveForm::end(); ?>

</div>
