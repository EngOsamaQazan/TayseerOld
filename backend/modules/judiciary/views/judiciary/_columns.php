<?php
/**
 * أعمدة جدول القضايا
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

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

    /* العميل — يستخدم العلاقة المحمّلة مسبقاً (Eager Loaded) بدلاً من استعلام لكل سطر */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'العميل',
        'value' => function ($m) {
            $names = [];
            foreach ($m->customersAndGuarantor as $customer) {
                $names[] = $customer->name;
            }
            return implode('، ', $names) ?: '—';
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

    /* رقم القضية - السنة */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'judiciary_number',
        'label' => 'رقم القضية',
        'format' => 'raw',
        'value' => function ($m) {
            $num = $m->judiciary_number ?: '—';
            $year = $m->year ?: '';
            return $year ? "<span style='font-family:monospace;font-weight:600'>{$num}-{$year}</span>" : "<span style='font-family:monospace'>{$num}</span>";
        },
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
        'contentOptions' => ['style' => 'width:50px;text-align:center;overflow:visible;position:relative'],
        'header' => '',
        'template' => '{all}',
        'buttons' => [
            'all' => function($url, $m) {
                $addActionUrl = Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/create-followup-judicary-custamer-action', 'contractID' => $m->contract_id]);
                $editUrl  = Url::to(['update', 'id' => $m->id, 'contract_id' => $m->contract_id]);
                $printUrl = Url::to(['print-case', 'id' => $m->id]);
                $followUrl = Url::to(['/followUp/follow-up/index', 'contract_id' => $m->contract_id]);
                $delUrl   = Url::to(['delete', 'id' => $m->id]);

                return '<div class="jud-act-wrap">'
                    . '<button type="button" class="jud-act-trigger"><i class="fa fa-ellipsis-v"></i></button>'
                    . '<div class="jud-act-menu">'
                    .   '<a href="' . $addActionUrl . '" role="modal-remote"><i class="fa fa-plus text-success"></i> إضافة إجراء</a>'
                    .   '<div class="jud-act-divider"></div>'
                    .   '<a href="' . $editUrl . '"><i class="fa fa-pencil text-primary"></i> تعديل</a>'
                    .   '<a href="' . $printUrl . '"><i class="fa fa-print text-info"></i> طباعة</a>'
                    .   '<a href="' . $followUrl . '"><i class="fa fa-comments text-success"></i> المتابعة</a>'
                    .   '<div class="jud-act-divider"></div>'
                    .   '<a href="' . $delUrl . '" data-method="post" data-confirm="هل أنت متأكد من حذف هذه القضية؟"><i class="fa fa-trash text-danger"></i> حذف</a>'
                    . '</div>'
                    . '</div>';
            },
        ],
    ],
];
