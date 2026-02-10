<?php

use yii\helpers\Html;
use yii\data\ActiveDataProvider;
use kartik\grid\GridView;
use yii\helpers\Url;

?>


<div class="card card-body">
    <?php
    $LoanScheduling = new ActiveDataProvider([
        'query' => \backend\modules\loanScheduling\models\LoanScheduling::find()->where(['contract_id' => $contractCalculations->contract_model->id])
    ]);
    ?>

    <?php
    $pjaxGrideViewID = "table-crud-datatable-{$contractCalculations->contract_model->id}";
    echo GridView::widget([
        'id' => $pjaxGrideViewID,
        'dataProvider' => $LoanScheduling,

        'pjax' => true, 'columns' => [
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'contract_id',
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'new_installment_date',
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'monthly_installment',
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'first_installment_date',
            ],
            // [
            // 'class'=>'\kartik\grid\DataColumn',
            // 'attribute'=>'status',
            // ],
            // [
            // 'class'=>'\kartik\grid\DataColumn',
            // 'attribute'=>'status_action_by',
            // ],
            // [
            // 'class'=>'\kartik\grid\DataColumn',
            // 'attribute'=>'created_at',
            // ],
            // [
            // 'class'=>'\kartik\grid\DataColumn',
            // 'attribute'=>'updated_at',
            // ],
            // [
            // 'class'=>'\kartik\grid\DataColumn',
            // 'attribute'=>'created_by',
            // ],
            // [
            // 'class'=>'\kartik\grid\DataColumn',
            // 'attribute'=>'last_update_by',
            // ],
            // [
            // 'class'=>'\kartik\grid\DataColumn',
            // 'attribute'=>'is_deleted',
            // ],
            [
                'class' => 'kartik\grid\ActionColumn',
                'dropdown' => false,
                'vAlign' => 'middle',
                'template' => '{delete}{update}',
                'urlCreator' => function ($action, $model, $key, $index) {
                    if ($action == 'delete') {
                        return Url::to(['/loanScheduling/loan-scheduling/delete-from-follow-up', 'id' => $model->id, 'contract_id' => $model->contract_id]);
                    }else{
                        return Url::to(['/loanScheduling/loan-scheduling/update', 'id' => $model->id, 'contract_id' => $model->contract_id]);
                    }
                    return Url::to([$action, 'id' => $key]);
                },
                'viewOptions' => ['title' => 'View', 'data-toggle' => 'tooltip'],
                'updateOptions' => ['role'=>'modal-remote','title' => 'Update', 'data-toggle' => 'tooltip'],
                'deleteOptions' => [
                    'title' => 'Delete',
                    'data-confirm' => false, 'data-method' => false, // for overide yii data api
                    'data-request-method' => 'post',
                    'data-toggle' => 'tooltip',
                    'data-confirm-title' => 'Are you sure?',
                    'data-confirm-message' => 'Are you sure want to delete this item'
                ],
            ],

        ],
        'toolbar' => [
            [
                'content' =>
                Html::a('<i class="glyphicon glyphicon-plus"></i>', ['/loanScheduling/loan-scheduling/create-from-follow-up?contract_id=' . $contractCalculations->contract_model->id], ['role' => 'modal-remote', 'title' => 'Create new Phone Numbers', 'class' => 'btn btn-default']) .
                    '{toggleData}' .
                    '{export}'
            ],
        ],
        'striped' => false,
        'condensed' => false,
        'responsive' => false,
        'export' => false,
        'panel' => [
            'type' => false,
            'heading' => false,
            'after' => false,
            'footer' => false
        ]
    ]) ?>

</div>