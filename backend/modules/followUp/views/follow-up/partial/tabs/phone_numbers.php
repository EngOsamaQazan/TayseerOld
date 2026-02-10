<?php

use backend\modules\contracts\models\Contracts;
use yii\helpers\Url;
use yii\helpers\Html;
use kartik\grid\GridView;
use backend\modules\customers\models\ContractsCustomers;
?>


<div class="row">
    <div id="ajaxCrudDatatable">
        <?php
        $dataProvider = new yii\data\ArrayDataProvider([
            'key' => 'id',
            'allModels' => contracts::findOne($contract_id)->customersAndGuarantor,
        ]);
        ?>
        <?php
        echo GridView::widget([
            'id' => 'customers-table-crud-datatable',
            'dataProvider' => $dataProvider,
            'summary' => '',
            'pjax' => true,
            'pjaxSettings' => [
                'neverTimeout' => true,
                'options' => [
                    'id' => 'customers-table-crud-datatable',
                ]
            ],
            'columns' => [
                [
                    'class' => '\kartik\grid\DataColumn',
                    'attribute' => 'name',
                    'value' => function ($model) {
                        return '<button type="button" class="btn btn-primary custmer-popup"  data-target="#exampleModal12" data-toggle="modal" customer-id ="' . $model->id . '">
  ' . $model->name . '
</button>';
                    },
                    'format' => 'raw',
                ],
                [
                    'class' => '\kartik\grid\DataColumn',
                    'attribute' => 'primary_phone_number',
                    'value' => function ($model) {
                        return Html::a($model->primary_phone_number, Url::to(['/customers/customers/update', 'id' => $model->id]), ['data-pjax' => 0]);
                    },
                    'format' => 'raw',
                ],
                [
                    'label' => 'Contract Count',
                    'value' => function ($model) {
                        $count = 0;
                        $contracts = ContractsCustomers::find()->where('customer_id =' . $model->id)->all();
                        foreach ($contracts as $contract) {
                            $contractStatuc = Contracts::findOne($contract->contract_id);
                            if ($contractStatuc->status != 'finished' && $contractStatuc->status != 'canceled') {
                                $count = $count + 1;
                            }
                        }
                        return $count;
                    }
                ],
                'facebook_account',
                [
                    'format' => 'raw',
                    'value' => function ($model) {
                        return '<div itemscope itemtype="https://schema.org/LocalBusiness">
                                                <span itemprop="telephone">
                                                    <a class="btn btn-info btn-lg" href="tel:+' . $model->primary_phone_number . '">
                                                        <span class="glyphicon glyphicon-earphone"></span>
                                                    </a></span>
                                            </div>';
                    }
                ],
                [
                    'format' => 'raw',
                    'value' => function ($model) {
                        return '<a style="background-color: #60ca60;" target="_blank" class="btn btn-lg" href="https://wa.me/' . $model->primary_phone_number . '">
                                                        <span class="fa fa-whatsapp" style="color: white;"></span>
                                                    </a>
                                            ';
                    }
                ],
                [
                    'format' => 'raw',
                    'value' => function ($model) {
                        if (empty($model->facebook_account)) {
                            return '<a style="background-color: deepskyblue;border: 1px solid black" target="_blank"   class="btn btn-lg" >
                                                        <span class="fa fa-facebook" style="color: white; "></span>
                                                    </a>  ';
                        }
                        return '<a style="background-color: #4267B2;" target="_blank" class="btn btn-lg" href="https://m.me/' . $model->facebook_account . '">
                                                        <span class="fa fa-facebook" style="color: white;"></span>
                                                    </a>  ';
                    }
                ],
                [
                    'format' => 'raw',
                    'value' => function ($model) {
                        return '<button type="button" onclick="setPhoneNumebr(' . $model->primary_phone_number . ')" class="btn btn-primary glyphicon glyphicon-comment" data-toggle="modal" data-target="#exampleModalCenter"></button>';
                    }
                ],
                [
                    'class' => 'kartik\grid\ActionColumn',
                    'dropdown' => false,
                    'vAlign' => 'middle',
                    'template' => '{update}',
                    'urlCreator' => function ($action, $model, $key, $index) {
                        if ($action == "update") {

                            return Url::to(['/customers/customers/update-contact', 'id' => $model->id]);
                        }
                    },
                    'updateOptions' => ['role' => 'modal-remote', 'title' => 'Update', 'data-toggle' => 'tooltip'],
                ],
            ],
            'striped' => false,
            'condensed' => false,
            'responsive' => false,
            'export' => false,
            'panel' => [
                'type' => false,
                'heading' => false,
                'before' => '<h3>' . Yii::t('app', 'Coustmers Phone Numbers') . '</h3>',
                'after' => false,
                'footer' => false
            ]
        ])
        ?>
    </div>
</div>


<div class="row">


    <div id="ajaxCrudDatatable">
        <?php
        foreach ($contractCalculations->contract_model->contractsCustomers as $key => $value) {
            $dataProvider = new yii\data\ArrayDataProvider([
                'key' => 'id',
                'allModels' => $value->customer->phoneNumbers,
            ]);
            $pjaxGrideViewID = "customers-info-table-crud-datatable-{$value->customer->id}";
            echo GridView::widget([
                'id' => $pjaxGrideViewID, //'customers-info-table-crud-datatable',
                'dataProvider' => $dataProvider,
                'summary' => '',
                'pjax' => true,
                'pjaxSettings' => [
                    'neverTimeout' => true,
                    'options' => [
                        'id' => $pjaxGrideViewID,
                    ]
                ],
                'columns' => [
                    'phone_number',
                    [
                        'class' => '\kartik\grid\DataColumn',
                        'attribute' => 'owner_name',
                        'value' => function ($model) {
                            return Html::a($model->owner_name, Url::to(['/phoneNumbers/phone-numbers/update', 'id' => $model->id]), ['data-pjax' => 0]);
                        },
                        'format' => 'raw',
                    ],
                    [
                        'label' => Yii::t('app', 'phone number owner'),
                        'value' => function ($model) {

                            $relation = backend\modules\cousins\models\Cousins::findone(['id' => $model->phone_number_owner]);
                            return !empty($relation) ? $relation->name : '';
                        }
                    ],
                    [
                        'format' => 'raw',
                        'value' => function ($model) {
                            return '<div itemscope itemtype="https://schema.org/LocalBusiness">
                                                <span itemprop="telephone">
                                                    <a class="btn btn-info btn-lg" href="tel:+' . $model->phone_number . '">
                                                        <span class="glyphicon glyphicon-earphone"></span>
                                                    </a></span>
                                            </div>';
                        }
                    ],
                    [
                        'format' => 'raw',
                        'value' => function ($model) {
                            return '<a style="background-color: #60ca60;" target="_blank" class="btn btn-lg" href="https://wa.me/' . $model->phone_number . '">
                                                        <span class="fa fa-whatsapp" style="color: white;"></span>
                                                    </a>
                                            ';
                        }
                    ],
                    [
                        'format' => 'raw',
                        'value' => function ($model) {
                            if (empty($model->fb_account)) {
                                return '<a style="background-color: deepskyblue;border: 1px solid black" target="_blank"   class="btn btn-lg" >
                                                        <span class="fa fa-facebook" style="color: white; "></span>
                                                    </a>  ';
                            }
                            return '<a style="background-color: #4267B2;" target="_blank" class="btn btn-lg" href="https://m.me/' . $model->fb_account . '">
                                                        <span class="fa fa-facebook" style="color: white;"></span>
                                                    </a>  ';
                        }
                    ],
                    [
                        'format' => 'raw',
                        'value' => function ($model) {
                            return '<button type="button" onclick="setPhoneNumebr(' . $model->phone_number . ')" class="btn btn-primary glyphicon glyphicon-comment" data-toggle="modal" data-target="#exampleModalCenter"></button>';
                        }
                    ],
                    [
                        'class' => 'kartik\grid\ActionColumn',
                        'dropdown' => false,
                        'vAlign' => 'middle',
                        'template' => '{delete} {update}',
                        'urlCreator' => function ($action, $model, $key, $index) {
                            return Url::to(['/phoneNumbers/phone-numbers/' . $action, 'id' => $key]);
                        },
                        'updateOptions' => ['role' => 'modal-remote', 'title' => 'Update', 'data-toggle' => 'tooltip'],
                        'deleteOptions' => [
                            'role' => 'modal-remote', 'title' => 'Delete',
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
                        Html::a('<i class="glyphicon glyphicon-plus"></i>', ['/phoneNumbers/phone-numbers/create?contract_id=' . $value->customer->name . '&customers_id=' . $value->customer->id], ['role' => 'modal-remote', 'title' => 'Create new Phone Numbers', 'class' => 'btn btn-default']) .
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
                    'before' => '<h4>' . $value->customer->name . '</h4>',
                    'after' => false,
                    'footer' => false
                ]
            ]);
        ?>

        <?php
        }
        ?>

    </div>
</div>