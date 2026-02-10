<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\InventoryItems */
?>
<div class="inventory-items-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'item_name',
            'item_barcode',
            'created_at',
            'updated_at',
            'created_by',
            'last_update_by',
            'is_deleted',
        ],
    ]) ?>

</div>
