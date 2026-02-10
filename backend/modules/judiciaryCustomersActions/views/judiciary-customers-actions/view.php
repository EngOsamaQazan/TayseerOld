<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\modules\judiciary\models\JudiciaryCustomersActions */
?>
<div class="judiciary-customers-actions-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'judiciary_id',
            'customers_id',
            'judiciary_actions_id',
            'note:ntext',
            'created_at',
            'updated_at',
            'created_by',
            'last_update_by',
            'is_deleted',
        ],
    ]) ?>

</div>
