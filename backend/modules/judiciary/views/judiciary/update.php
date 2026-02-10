<?php
/**
 * تعديل القضية
 */
use yii\helpers\Html;

$this->title = 'تعديل القضية #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'القضاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="judiciary-update">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-pencil"></i> <?= $this->title ?></h3>
            <div class="box-tools pull-left">
                <?= Html::a('<i class="fa fa-arrow-right"></i> القضايا', ['index'], ['class' => 'btn btn-default btn-sm']) ?>
            </div>
        </div>
        <div class="box-body">
            <?= $this->render('_form', ['model' => $model, 'modelCustomerAction' => $modelCustomerAction, 'contract_id' => $model->contract_id]) ?>
        </div>
    </div>
</div>
