<?php
/**
 * نموذج ديناميكي - عناوين العميل
 */
use yii\helpers\Html;
use wbraganca\dynamicform\DynamicFormWidget;

DynamicFormWidget::begin([
    'widgetContainer' => 'dynamicform_wrapper',
    'widgetBody' => '.container-items',
    'widgetItem' => '.addrres-item',
    'limit' => 20,
    'min' => 1,
    'insertButton' => '.addrres-add-item',
    'deleteButton' => '.addrres-remove-item',
    'model' => $modelsAddress[0],
    'formId' => 'smart-onboarding-form',
    'formFields' => ['address'],
]);
?>

<div class="container-items">
    <?php foreach ($modelsAddress as $i => $addr): ?>
        <div class="addrres-item panel panel-default">
            <div class="panel-body">
                <?php if (!$addr->isNewRecord) echo Html::activeHiddenInput($addr, "[{$i}]id") ?>
                <div class="row">
                    <div class="col-md-6">
                        <?= $form->field($addr, "[{$i}]address")->textInput(['maxlength' => true, 'placeholder' => 'العنوان بالتفصيل'])->label('العنوان') ?>
                    </div>
                    <div class="col-md-5">
                        <?= $form->field($addr, "[{$i}]address_type")->dropDownList([1 => 'عنوان العمل', 2 => 'عنوان السكن'])->label('النوع') ?>
                    </div>
                    <div class="col-md-1">
                        <div style="margin-top:26px">
                            <button type="button" class="addrres-remove-item btn btn-danger btn-xs" title="حذف"><i class="fa fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach ?>
</div>

<button type="button" class="addrres-add-item btn btn-success btn-xs"><i class="fa fa-plus"></i> إضافة عنوان</button>

<?php DynamicFormWidget::end() ?>
