<?php
/**
 * تعديل بيانات العميل
 */
use yii\helpers\Html;

$this->title = 'تعديل: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'العملاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="customers-update">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-pencil"></i> <?= Html::encode($this->title) ?></h3>
            <div class="box-tools pull-left">
                <?= Html::a('<i class="fa fa-eye"></i> عرض', ['view', 'id' => $model->id], ['class' => 'btn btn-info btn-sm']) ?>
                <?= Html::a('<i class="fa fa-arrow-right"></i> العملاء', ['index'], ['class' => 'btn btn-default btn-sm']) ?>
            </div>
        </div>
        <div class="box-body">
            <?= $this->render('_form', [
                'model' => $model,
                'id' => $model->id,
                'modelsAddress' => $modelsAddress,
                'modelsPhoneNumbers' => $modelsPhoneNumbers,
                'customerDocumentsModel' => $customerDocumentsModel,
                'modelRealEstate' => $modelRealEstate,
            ]) ?>
        </div>
    </div>
</div>
