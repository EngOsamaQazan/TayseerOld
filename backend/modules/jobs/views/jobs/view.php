<?php

use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\modules\jobs\models\Jobs */
?>
<div class="jobs-view">
 
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'job_type',
        ],
    ]) ?>

</div>
