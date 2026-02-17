<?php

use yii\widgets\DetailView;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model backend\modules\inventoryInvoices\models\InventoryInvoices */
?>
<div class="inventory-invoices-view">

    <?php if (!$model->isNewRecord): ?>
    <div class="invoice-actions" style="margin-bottom:16px;">
        <?php
        $user = Yii::$app->user->identity;
        $isBranchSalesForThis = $user && (int)$user->location === (int)$model->branch_id;
        if ($model->status === \backend\modules\inventoryInvoices\models\InventoryInvoices::STATUS_PENDING_RECEPTION && $isBranchSalesForThis):
            echo Html::a('موافقة استلام (الفرع)', ['approve-reception', 'id' => $model->id], [
                'class' => 'btn btn-success',
                'data-method' => 'post',
                'data-confirm' => 'تأكيد الموافقة على استلام الفاتورة؟',
            ]);
            echo ' ' . Html::a('رفض استلام', ['reject-reception', 'id' => $model->id], [
                'class' => 'btn btn-warning',
            ]);
        endif;
        if ($model->status === \backend\modules\inventoryInvoices\models\InventoryInvoices::STATUS_PENDING_MANAGER):
            echo ' ' . Html::a('موافقة المدير', ['approve-manager', 'id' => $model->id], [
                'class' => 'btn btn-primary',
                'data-method' => 'post',
                'data-confirm' => 'تأكيد الموافقة النهائية وترحيل الفاتورة إلى المخزون؟',
            ]);
            echo ' ' . Html::a('رفض المدير', ['reject-manager', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data-method' => 'post',
                'data-confirm' => 'تأكيد رفض الفاتورة؟',
            ]);
        endif;
        ?>
    </div>
    <?php endif; ?>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'invoice_number',
            ['attribute' => 'status', 'value' => $model->getStatusList()[$model->status] ?? $model->status],
            ['attribute' => 'branch_id', 'value' => $model->branch ? $model->branch->location : null],
            'company_id',
            'total_amount',
            'discount_amount',
            'type',
            'suppliers_id',
            'date',
            'approved_by',
            'approved_at',
            'rejection_reason',
            'posted_at',
            'invoice_notes',
            'created_at',
            'updated_at',
            'created_by',
            'last_updated_by',
        ],
    ]) ?>

</div>
