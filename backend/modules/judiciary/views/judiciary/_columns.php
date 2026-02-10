<?php
/**
 * أعمدة جدول القضايا
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\bootstrap\ButtonDropdown;

return [
    /* # */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'id',
        'label' => '#',
        'contentOptions' => ['style' => 'width:45px'],
    ],

    /* رقم العقد */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'contract_id',
        'label' => 'العقد',
        'format' => 'raw',
        'value' => fn($m) => Html::a($m->contract_id, ['/followUp/follow-up/index', 'contract_id' => $m->contract_id], ['class' => 'text-burgundy', 'style' => 'font-weight:600']),
    ],

    /* العميل */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'العميل',
        'value' => function ($m) {
            $customers = \backend\modules\customers\models\ContractsCustomers::find()
                ->select(['c.name'])->alias('cc')
                ->innerJoin('{{%customers}} c', 'c.id=cc.customer_id')
                ->where(['cc.contract_id' => $m->contract_id])
                ->column();
            return implode('، ', $customers) ?: '—';
        },
    ],

    /* المحكمة */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'court_id',
        'label' => 'المحكمة',
        'value' => 'court.name',
    ],

    /* نوع القضية */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'type_id',
        'label' => 'النوع',
        'value' => 'type.name',
    ],

    /* المحامي */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'lawyer_id',
        'label' => 'المحامي',
        'value' => 'lawyer.name',
    ],

    /* رقم القضية */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'judiciary_number',
        'label' => 'رقم القضية',
        'contentOptions' => ['style' => 'font-family:monospace'],
    ],

    /* أتعاب المحامي */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'lawyer_cost',
        'label' => 'أتعاب المحامي',
        'format' => ['decimal', 0],
    ],

    /* رسوم القضية */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'case_cost',
        'label' => 'رسوم القضية',
        'format' => ['decimal', 0],
    ],

    /* الإجراءات */
    [
        'class' => 'yii\grid\ActionColumn',
        'contentOptions' => ['style' => 'width:90px;text-align:center'],
        'header' => 'إجراءات',
        'template' => '{all}',
        'buttons' => [
            'all' => fn($url, $m) => ButtonDropdown::widget([
                'encodeLabel' => false,
                'label' => '<i class="fa fa-cogs"></i>',
                'dropdown' => [
                    'encodeLabels' => false,
                    'items' => [
                        ['label' => '<i class="fa fa-pencil text-primary"></i> تعديل', 'url' => ['update', 'id' => $m->id, 'contract_id' => $m->contract_id]],
                        ['label' => '<i class="fa fa-print text-info"></i> طباعة', 'url' => ['print-case', 'id' => $m->id]],
                        ['label' => '<i class="fa fa-comments text-success"></i> المتابعة', 'url' => ['/followUp/follow-up/index', 'contract_id' => $m->contract_id]],
                        '<li class="divider"></li>',
                        ['label' => '<i class="fa fa-trash text-danger"></i> حذف', 'url' => ['delete', 'id' => $m->id], 'linkOptions' => ['data' => ['method' => 'post', 'confirm' => 'هل أنت متأكد من حذف هذه القضية؟']]],
                    ],
                    'options' => ['class' => 'dropdown-menu-right'],
                ],
                'options' => ['class' => 'btn-default btn-xs'],
                'split' => false,
            ]),
        ],
    ],
];
