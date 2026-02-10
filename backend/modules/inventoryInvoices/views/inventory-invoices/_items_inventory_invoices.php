<?php

use wbraganca\dynamicform\DynamicFormWidget;
use yii\helpers\Html;
?>

<div class="panel-body">
    <?php
    DynamicFormWidget::begin([
        'widgetContainer' => 'dynamicform_wrapper90', // required: only alphanumeric characters plus "_" [A-Za-z0-9_]
        'widgetBody' => '.container-items90', // required: css class selector
        'widgetItem' => '.itemsInventoryInvoice-item', // required: css class
        'limit' => 1000000000000000, // the maximum times, an element can be cloned (default 999)
        'min' => 1, // 0 or 1 (default 1)
        'insertButton' => '.itemsInventoryInvoice-add-item', // css class
        'deleteButton' => '.itemsInventoryInvoice-remove-item', // css class
        'model' => $itemsInventoryInvoices[0],
        'formId' => 'dynamic-form',
        'formFields' => [
            'number',
            'single_price',
            'inventory_items_id',
        ],

    ]);
    ?>

    <div class="container-items90">
        <?php foreach ($itemsInventoryInvoices as $i => $itemsInventoryInvoice): ?>
            <div class="itemsInventoryInvoice-item panel panel-default"><!-- widgetBody -->

                <div class="panel-body">
                    <?php
                    // necessary for update action.
                    if (!$itemsInventoryInvoice->isNewRecord) {
                        echo Html::activeHiddenInput($itemsInventoryInvoice, "[{$i}]id");
                    }
                    ?>
                    <div class="row">
                        <div class="col-sm-3">
                            <?= $form->field($itemsInventoryInvoice, "[{$i}]number")->textInput(['maxlength' => true])?>  </div>


                        <div class="col-sm-3">
                            <?= $form->field($itemsInventoryInvoice, "[{$i}]single_price")->textInput(['maxlength' => true])?>  </div>
        <div class="col-sm-3">
                            <?= $form->field($itemsInventoryInvoice, "[{$i}]inventory_items_id")->dropDownList([\yii\helpers\ArrayHelper::map(backend\modules\inventoryItems\models\InventoryItems::find()->all(),'id','item_name')])?>  </div>

                        <div class="col-sm-3">
                            <button type="button" class="itemsInventoryInvoice-remove-item btn btn-danger btn-xs" style="margin-top: 30px;">
                                <i class="glyphicon glyphicon-minus"></i>
                            </button>
                        </div>
                    </div><!-- .row -->
                </div>
            </div>
        <?php endforeach; ?>

    </div>
    <div class="pull-right">
        <button type="button" class="itemsInventoryInvoice-add-item btn btn-success btn-xs"><i class="glyphicon glyphicon-plus"></i></button>

    </div>
    <?php DynamicFormWidget::end(); ?>
</div>