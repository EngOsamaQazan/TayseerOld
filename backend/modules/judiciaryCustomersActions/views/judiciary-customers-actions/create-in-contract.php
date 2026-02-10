<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\modules\judiciary\models\Judiciary;
use backend\modules\customers\models\Customers;
use backend\modules\judiciaryActions\models\JudiciaryActions;
use yii\helpers\ArrayHelper;
use kartik\date\DatePicker;
use backend\modules\customers\models\ContractsCustomers;

/* @var $this yii\web\View */
/* @var $model backend\modules\judiciary\models\JudiciaryCustomersActions */
/* @var $form yii\widgets\ActiveForm */
/* @var $contractID */

$data = ContractsCustomers::find()
    ->select(['c.id', 'c.name'])
    ->alias('cc')
    ->innerJoin('{{%customers}} c', 'c.id=cc.customer_id')
    ->where(['cc.contract_id' => $contractID])
    ->createCommand()->queryAll();

?>
<div class="questions-bank box box-primary">

    <?php $form = ActiveForm::begin([
        'options' => ['enctype' => 'multipart/form-data'], // Important for file uploads
    ]); ?>

    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'judiciary_id')->widget(kartik\select2\Select2::classname(), [
                'data' => yii\helpers\ArrayHelper::map(Judiciary::find()->where(['contract_id' => $contractID])->all(), 'id', 'judiciary_number'),
                'options' => [
                    'placeholder' => 'Select a judiciary.',
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]); ?>
        </div>
        <div class="col-lg-6">
            <?= $form->field($model, 'customers_id')->widget(kartik\select2\Select2::classname(), [
                'data' => yii\helpers\ArrayHelper::map($data, 'id', 'name'),
                'options' => [
                    'placeholder' => 'Select a customer.',
                    'type' => 'search'
                ],
            ]); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'judiciary_actions_id')->widget(kartik\select2\Select2::classname(), [
                'data' => yii\helpers\ArrayHelper::map(JudiciaryActions::find()->all(), 'id', 'name'),
                'options' => [
                    'placeholder' => 'Select a judiciary action.',
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]); ?>
        </div>
        <div>
            <?= $form->field($model, 'action_date')->widget(DatePicker::classname(), [
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'yyyy-mm-dd'
                ]
            ])->label('تاريخ الحركة'); ?>
        </div>
    </div>

    <?= $form->field($model, 'note')->textarea(['rows' => 6]) ?>

    <!-- Add the file input for the image -->
    <?= $form->field($model, 'image')->fileInput() ?>

    <!-- Display the existing image if available -->
    <?php if ($model->image): ?>
        <div class="form-group">
            <label>Current Image:</label><br>
            <!-- Thumbnail Image -->
            <a href="#" data-toggle="modal" data-target="#imageModal">
                <?= Html::img(Yii::getAlias('@web') . '/' . $model->image, [
                    'class' => 'img-thumbnail',
                    'style' => 'max-width: 200px; cursor: pointer;',
                    'alt' => 'Uploaded Image'
                ]) ?>
            </a>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="imageModalLabel">Uploaded Image</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body text-center">
                        <!-- Full-Size Image -->
                        <?= Html::img(Yii::getAlias('@web') . '/' . $model->image, [
                            'class' => 'img-fluid',
                            'alt' => 'Uploaded Image'
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!Yii::$app->request->isAjax) { ?>
        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>
    <?php } ?>

    <?php ActiveForm::end(); ?>

</div>