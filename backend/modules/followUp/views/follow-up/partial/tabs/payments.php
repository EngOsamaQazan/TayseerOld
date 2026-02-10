<?php

use kartik\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
?>
<div class="card card-body">
    <?php
    $data = new yii\data\ArrayDataProvider([
        'key' => 'id',
        'allModels' => \backend\modules\income\models\Income::find()->Where(['contract_id' => $contract_id])->orderBy(['date' => SORT_DESC])->all(),
    ]);

    echo GridView::widget([
        'id' => 'income-table-crud-datatable',
        'dataProvider' => $data,
        'summary' => '',
        'pjax' => true,
        'columns' => [
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => '_by',
                'label' => Yii::t('app', 'By'),
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'contract_id',
                'value' => function ($model) {
                    return Html::a($model->contract_id, Url::to(['/contracts/contracts/update', 'id' => $model->contract_id]), ['data-pjax' => 0, 'target' => '_blank']);
                },
                'label' => Yii::t('app', 'Contract ID'),
                'format' => 'raw',
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'date',
                'label' => Yii::t('app', 'Date'),
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'amount',
                'label' => Yii::t('app', 'Amount'),
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'created_by',
                'value' => 'created.username',
                'label' => Yii::t('app', 'Created By'),
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'value' => function ($model) {
                    $type = \backend\modules\paymentType\models\PaymentType::findOne(['id' => $model->payment_type]);
                    return $type->name;
                },
                'label' => Yii::t('app', 'Payment Type'),
            ],

        ],
        'striped' => false,
        'condensed' => false,
        'responsive' => false,
        'export' => false,
    ]);

    echo "<hr/>";
    $sql=\backend\modules\expenses\models\Expenses::find()->Where(['contract_id' => $contract_id])->orderBy(['expenses_date' => SORT_DESC])->all();
    $data = new yii\data\ArrayDataProvider([
        'key' => 'id',
        'allModels' => \backend\modules\expenses\models\Expenses::find()->Where(['contract_id' => $contract_id])->orderBy(['expenses_date' => SORT_DESC])->all(),
    ]);
    echo GridView::widget([
        'id' => 'income-table-crud-datatable',
        'dataProvider' => $data,
        'summary' => '',
        'pjax' => true,
        'columns' => [

            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'category_id',
                'value' => 'category.name'
            ],
            // [
            // 'class'=>'\kartik\grid\DataColumn',
            // 'attribute'=>'created_at',
            // ],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'created_by',
                'value' => 'createdBy.username'
            ],
            // [
            // 'class'=>'\kartik\grid\DataColumn',
            // 'attribute'=>'updated_at',
            // ],
            //  [
            //    'class'=>'\kartik\grid\DataColumn',
            //  'attribute'=>'last_updated_by',
            //],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'description',
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'amount',
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'receiver_number',
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'expenses_date',
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'notes',
                'label' => Yii::t('app', 'Notes'),
            ],
            [
                'class' => '\kartik\grid\DataColumn',
                'attribute' => 'document_number',
                'label' => Yii::t('app', 'Document Number'),
            ],


        ],
        'striped' => false,
        'condensed' => false,
        'responsive' => false,
        'export' => false,
    ]);
    ?>
</div>