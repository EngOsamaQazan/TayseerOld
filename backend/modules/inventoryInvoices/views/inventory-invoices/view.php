<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\modules\inventoryInvoices\models\InventoryInvoices */
?>
<div class="inventory-invoices-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'inventory_items_id',
            'company_id',
            'total_amount',
            'number',
            'single_price',
            'type',
            'suppliers_id',
            'created_at',
            'updated_at',
            'created_by',
            'last_updated_by',
            'is_deleted',
            'date',
        ],
    ]) ?>

</div>
