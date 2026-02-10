<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
/* @var $this yii\web\View */
/* @var $model backend\modules\inventoryInvoices\models\InventoryInvoices */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="questions-bank box box-primary">

    <?php
    if (isset($id)) {
        $form = ActiveForm::begin(['action' => Url::to(['update', 'id' => $id]), 'options' => ['enctype' => 'multipart/form-data'], 'id' => 'dynamic-form']);
    } else {
        $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data'], 'id' => 'dynamic-form']);
    }
    ?>
    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'type')->dropDownList(['نقدي','ذمم','مخلط']) ?>
        </div>
        <div class="col-lg-6">
            <?= $form->field($model, 'suppliers_id')->widget(Select2::classname(), [
                'data' => \yii\helpers\ArrayHelper::map(backend\modules\inventorySuppliers\models\InventorySuppliers::find()->all(),'id','name'),
                'language' => 'es',
                'options' => ['placeholder' => 'Select supplier'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'company_id')->textInput()->widget(Select2::classname(), [
                'data' => \yii\helpers\ArrayHelper::map(backend\modules\companies\models\Companies::find()->all(),'id','name'),
                'language' => 'es',
                'options' => ['placeholder' => 'Select company'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]); ?>
        </div>
    </div>
<?=$this->render('_items_inventory_invoices',[
        'itemsInventoryInvoices'=>$itemsInventoryInvoices,
    'form'=>$form
]);?>

  
	<?php if (!Yii::$app->request->isAjax){ ?>
	  	<div class="form-group">
	        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
	    </div>
	<?php } ?>

    <?php ActiveForm::end(); ?>
    
</div>
