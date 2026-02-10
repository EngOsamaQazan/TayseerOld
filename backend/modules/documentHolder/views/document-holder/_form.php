<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\DocumentHolder */
/* @var $form yii\widgets\ActiveForm */
?>

    <div class="document-holder-form">

<?php $form = ActiveForm::begin(); ?>
    <div class="questions-bank box box-primary">
    <div class="row">
        <div class="col-lg-6">
            <?=
            $form->field($model, 'contract_id')->widget(kartik\select2\Select2::classname(), [
                'data' => \yii\helpers\ArrayHelper::map(\backend\modules\contracts\models\Contracts::find()->all(), 'id', 'id'),
                'language' => 'de',
                'options' => [
                    'placeholder' => 'Select a type.',
                    'class' => 'contract'
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]);
            ?>
        </div>

        <div class="col-lg-6">
            <?= $form->field($model, 'type')->dropDownList(['contract file','judiciary file'],['class'=>'type'])?>
        </div>
    </div>
    <div class="alert alert-warning war" role="alert" style="display: none">

    </div>
    <div class="alert alert-warning war2" role="alert" style="display: none">
    </div>
    <div class="row">

        <?php if (Yii::$app->user->can('مدير') && !$model->isNewRecord) { ?>
            <div class="col-lg-6">
                <?= $form->field($model, 'manager_approved')->checkbox() ?>

            </div>
        <?php } ?>
        <?php if (Yii::$app->user->id == $model->created_by && !$model->isNewRecord) { ?>
        <div class="col-lg-6">

            <?= $form->field($model, 'approved_by_employee')->checkbox() ?>
            <?php } ?>
        </div>
        <?= $form->field($model, 'reason')->textarea(['rows' => 6]) ?>
        <?php if (!Yii::$app->request->isAjax) { ?>
            <div class="form-group" style="margin-top: 10px">
                <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
            </div>
        <?php } ?>

        <?php ActiveForm::end(); ?>

    </div>
<?php
$this->registerJs(<<<SCRIPT
$(document).on('change','.contract',function(){
let contract = $('.contract').val();
$.post('find-list-user',{contract:contract},function(response){
$('.war').css('display','block');
$('.war').text(response);

});
$.post('find-type',{contract:contract},function(response){
response = JSON.parse(response);
if(response.length === 0){
$('.war2').css('display','block');
$('.war2').text('هذا الملف لا يحتوي على ملف للقضيه');
}else {
$('.war2').css('display','none');
$('.war2').text('');
}
})
});
SCRIPT
)
?>