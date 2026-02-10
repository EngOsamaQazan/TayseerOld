<?php
/**
 * إضافة عميل جديد
 */
use yii\helpers\Html;

$this->title = 'إضافة عميل جديد';
$this->params['breadcrumbs'][] = ['label' => 'العملاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="customers-create">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-user-plus"></i> <?= $this->title ?></h3>
            <div class="box-tools pull-left">
                <?= Html::a('<i class="fa fa-arrow-right"></i> العملاء', ['index'], ['class' => 'btn btn-default btn-sm']) ?>
            </div>
        </div>
        <div class="box-body">
            <?= $this->render('_form', [
                'model' => $model,
                'modelsAddress' => $modelsAddress,
                'modelsPhoneNumbers' => $modelsPhoneNumbers,
                'customerDocumentsModel' => $customerDocumentsModel,
                'modelRealEstate' => $modelRealEstate,
            ]) ?>
        </div>
    </div>
</div>
