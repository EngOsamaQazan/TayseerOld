<?php
/**
 * أعمدة جدول إجراءات العملاء القضائية - بناء من الصفر
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\ButtonDropdown;

return [
    /* رقم القضية */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'judiciary_id',
        'label' => 'القضية',
        'format' => 'raw',
        'value' => function ($m) {
            $jud = $m->judiciary;
            $label = $jud ? ($jud->judiciary_number . '/' . $jud->year) : '#' . $m->judiciary_id;
            return Html::a($label, ['/judiciary/judiciary/update', 'id' => $m->judiciary_id, 'contract_id' => $jud->contract_id ?? 0], ['class' => 'text-burgundy', 'style' => 'font-weight:600']);
        },
    ],

    /* العميل */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'المحكوم عليه',
        'value' => 'customers.name',
    ],

    /* الإجراء */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'judiciary_actions_id',
        'label' => 'الإجراء',
        'value' => 'judiciaryActions.name',
    ],

    /* الملاحظات */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'note',
        'label' => 'ملاحظات',
        'contentOptions' => ['style' => 'max-width:200px;word-wrap:break-word;direction:rtl'],
    ],

    /* المنشئ */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'created_by',
        'label' => 'المنشئ',
        'value' => 'createdBy.username',
    ],

    /* المحامي */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'المحامي',
        'value' => fn($m) => \common\helper\FindJudicary::findLawyerJudicary($m->judiciary_id),
    ],

    /* المحكمة */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'المحكمة',
        'value' => fn($m) => \common\helper\FindJudicary::findCourtJudicary($m->judiciary_id),
    ],

    /* العقد */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'contract_id',
        'label' => 'العقد',
        'format' => 'raw',
        'value' => function ($m) {
            $cid = \common\helper\FindJudicary::findJudiciaryContract($m->judiciary_id);
            return $cid ? Html::a($cid, ['/followUp/follow-up/index', 'contract_id' => $cid], ['class' => 'text-burgundy']) : '—';
        },
    ],

    /* تاريخ الإجراء */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'action_date',
        'label' => 'تاريخ الإجراء',
        'contentOptions' => ['style' => 'white-space:nowrap'],
    ],

    /* الإجراءات */
    [
        'class' => 'yii\grid\ActionColumn',
        'contentOptions' => ['style' => 'width:80px;text-align:center'],
        'header' => 'إجراءات',
        'template' => '{all}',
        'buttons' => [
            'all' => fn($url, $m) => ButtonDropdown::widget([
                'encodeLabel' => false,
                'label' => '<i class="fa fa-cogs"></i>',
                'dropdown' => [
                    'encodeLabels' => false,
                    'items' => [
                        ['label' => '<i class="fa fa-eye text-info"></i> عرض', 'url' => ['view', 'id' => $m->id], 'linkOptions' => ['role' => 'modal-remote']],
                        ['label' => '<i class="fa fa-pencil text-primary"></i> تعديل', 'url' => ['update', 'id' => $m->id], 'linkOptions' => ['role' => 'modal-remote']],
                        '<li class="divider"></li>',
                        ['label' => '<i class="fa fa-trash text-danger"></i> حذف', 'url' => ['delete', 'id' => $m->id], 'linkOptions' => [
                            'data' => ['method' => 'post', 'confirm' => 'هل أنت متأكد من حذف هذا الإجراء؟'],
                        ]],
                    ],
                    'options' => ['class' => 'dropdown-menu-right'],
                ],
                'options' => ['class' => 'btn-default btn-xs'],
                'split' => false,
            ]),
        ],
    ],
];
