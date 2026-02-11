<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\modules\judiciaryActions\models\JudiciaryActions */
?>
<div class="judiciary-actions-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            [
                'attribute' => 'action_type',
                'label' => 'نوع الإجراء',
                'value' => $model->getActionTypeLabel(),
            ],
        ],
    ]) ?>
</div>
