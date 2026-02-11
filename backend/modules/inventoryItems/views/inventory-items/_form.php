<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use backend\modules\inventorySuppliers\models\InventorySuppliers;

?>

<div class="inventory-items-form" style="padding:10px">
    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-lg-6 col-md-6">
            <?= $form->field($model, 'item_name')->textInput([
                'maxlength' => true,
                'placeholder' => 'مثال: آيفون 15 برو',
                'class' => 'form-control',
            ])->label('اسم الصنف <span style="color:red">*</span>') ?>
        </div>
        <div class="col-lg-6 col-md-6">
            <?= $form->field($model, 'item_barcode')->textInput([
                'maxlength' => true,
                'placeholder' => 'الباركود الفريد',
                'class' => 'form-control',
                'style' => 'direction:ltr; font-family:monospace',
            ])->label('الباركود <span style="color:red">*</span>') ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 col-md-6">
            <?= $form->field($model, 'serial_number')->textInput([
                'maxlength' => true,
                'placeholder' => 'الرقم التسلسلي للجهاز',
                'style' => 'direction:ltr; font-family:monospace',
            ])->label('الرقم التسلسلي') ?>
        </div>
        <div class="col-lg-6 col-md-6">
            <?= $form->field($model, 'category')->textInput([
                'maxlength' => true,
                'placeholder' => 'مثال: هواتف، لابتوبات، إكسسوارات',
            ])->label('التصنيف') ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 col-md-6">
            <?= $form->field($model, 'unit_price')->textInput([
                'type' => 'number',
                'step' => '0.01',
                'placeholder' => '0.00',
                'style' => 'direction:ltr',
            ])->label('سعر الوحدة') ?>
        </div>
        <div class="col-lg-6 col-md-6">
            <?= $form->field($model, 'supplier_id')->dropDownList(
                ArrayHelper::map(InventorySuppliers::find()->andWhere(['is_deleted' => 0])->all(), 'id', 'name'),
                ['prompt' => '— اختر المورد —']
            )->label('المورد') ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <?= $form->field($model, 'description')->textarea([
                'rows' => 3,
                'placeholder' => 'وصف إضافي عن الصنف...',
            ])->label('الوصف') ?>
        </div>
    </div>

    <?php if (!Yii::$app->request->isAjax): ?>
    <div class="form-group" style="margin-top:15px">
        <?= Html::submitButton($model->isNewRecord ? '<i class="fa fa-plus"></i> إضافة' : '<i class="fa fa-check"></i> تحديث', [
            'class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary',
        ]) ?>
    </div>
    <?php endif ?>

    <?php ActiveForm::end(); ?>
</div>
