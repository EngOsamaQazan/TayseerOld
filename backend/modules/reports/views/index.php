<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

$this->title = Yii::t('app', 'monthly Income');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="row">
    <div class="col-md-6">
        <h1>مجموع الاقساط غير المدفوعة: <?= $totalUnpaidInstallment; ?></h1>
    </div>
    <div class="col-md-6">
        <h1>مجموع الاقساط المتأخرة: <?= $totalDueInstallment; ?></h1>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <h1>مجموع الاقساط المدفوعة: <?= $totalPaidInstallment; ?></h1>
    </div>
    <div class="col-md-6">
        <h1>مجموع الاقساط الكلي: <?= $totalInstallment; ?></h1>
    </div>
</div>
<div class="row">
    <div class="col-md-3">
        <?= Html::a(Yii::t('app', 'monthly Income beer user'), ['reports/monthly-Income-beer-user'], ['class' => 'btn btn-success']) ?>

    </div>
    <div class="col-md-3">
        <?= Html::a(Yii::t('app', 'monthly Income'), ['reports/monthly-Income'], ['class' => 'btn btn-success']) ?>

    </div>
    <div class="col-md-3">
        <?= Html::a(Yii::t('app', 'due Income'), ['reports/due-Income'], ['class' => 'btn btn-success']) ?>

    </div>
    <div class="col-md-3">
        <?= Html::a(Yii::t('app', 'customers installments according to month'), ['reports/this-month-installments'], ['class' => 'btn btn-success']) ?>

    </div>
</div>
