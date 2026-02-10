<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\modules\companies\models\Companies */
?>
<div class="companies-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'phone_number',
            'bank_info',
            'logo',
            'created_by',
            'created_at',
            'updated_at',
        ],
    ]) ?>

</div>
