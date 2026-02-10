<?php
/**
 * إنشاء قضية جديدة
 */
use yii\helpers\Html;
use backend\modules\followUp\helper\ContractCalculations;

$this->title = 'إنشاء قضية - عقد #' . $contract_id;
$this->params['breadcrumbs'][] = ['label' => 'القضاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$calc = new ContractCalculations($contract_id);
?>

<div class="judiciary-create">
    <!-- ملخص مالي -->
    <div class="row" style="margin-bottom:15px">
        <div class="col-md-3">
            <div class="small-box bg-aqua">
                <div class="inner"><h4><?= number_format($calc->getContractTotal(), 0) ?></h4><p>إجمالي العقد</p></div>
                <div class="icon"><i class="fa fa-money"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-green">
                <div class="inner"><h4><?= number_format($calc->paidAmount(), 0) ?></h4><p>المدفوع</p></div>
                <div class="icon"><i class="fa fa-check"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-yellow">
                <div class="inner"><h4><?= number_format($calc->deservedAmount(), 0) ?></h4><p>المستحق</p></div>
                <div class="icon"><i class="fa fa-clock-o"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-red">
                <div class="inner"><h4><?= number_format($calc->remainingAmount(), 0) ?></h4><p>المتبقي</p></div>
                <div class="icon"><i class="fa fa-exclamation"></i></div>
            </div>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-gavel"></i> <?= $this->title ?></h3>
            <div class="box-tools pull-left">
                <?= Html::a('<i class="fa fa-arrow-right"></i> القضايا', ['index'], ['class' => 'btn btn-default btn-sm']) ?>
            </div>
        </div>
        <div class="box-body">
            <?= $this->render('_form', ['model' => $model, 'modelCustomerAction' => $modelCustomerAction, 'contract_id' => $contract_id]) ?>
        </div>
    </div>
</div>
