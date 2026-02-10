<?php
use kartik\tabs\TabsX;

/* @var $this yii\web\View */
/* @var $model backend\models\Employee */
?>
<div class="questions-bank box box-primary">

    <?php
    $items = [
        [
            'label' => '<i class="glyphicon glyphicon-user"></i>'.Yii::t('app', 'employee'),
            'content' => $this->render('_form', [
                'model' => $model,
            ]),
            'active' => true
        ], 
        [
            'label' => '<i class="glyphicon glyphicon-list-alt"></i> '.Yii::t('app', 'leave policy'),
            'content' => 'dont try to be smart :-)',
            'headerOptions' => ['class' => 'disabled']
        ], 
        [
            'label' => Yii::t('app', 'leave policy'),
            'content' => 'dont try to be smart :-)',
            'headerOptions' => ['class' => 'disabled']
        ]
    ];


    echo TabsX::widget([
        'position' => TabsX::POS_ABOVE,
        'items' => $items,
        'encodeLabels' => false,
    ]);
    ?>

</div>
