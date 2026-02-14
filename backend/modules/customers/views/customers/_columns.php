<?php
/**
 * أعمدة جدول العملاء
 * تعريفات الأعمدة مع تحسين الأداء (لا N+1)
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\ButtonDropdown;

return [
    /* رقم العميل */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'id',
        'label' => '#',
        'contentOptions' => ['style' => 'width:50px'],
    ],

    /* الاسم */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'name',
        'label' => 'الاسم',
        'format' => 'raw',
        'value' => fn($m) => Html::a(Html::encode($m->name), ['update', 'id' => $m->id], ['class' => 'text-burgundy', 'style' => 'font-weight:600']),
    ],

    /* الهاتف */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'primary_phone_number',
        'label' => 'الهاتف',
        'contentOptions' => ['style' => 'direction:ltr;text-align:right;font-family:monospace'],
    ],

    /* الرقم الوطني */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'id_number',
        'label' => 'الرقم الوطني',
        'contentOptions' => ['style' => 'font-family:monospace'],
    ],

    /* مشتكى عليه - استعلام EXISTS للأداء */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'مشتكى عليه',
        'format' => 'raw',
        'value' => function ($m) {
            static $cache = [];
            if (!isset($cache[$m->id])) {
                $cache[$m->id] = \backend\modules\judiciary\models\Judiciary::find()
                    ->innerJoin('os_contracts_customers cc', 'os_judiciary.contract_id = cc.contract_id')
                    ->where(['cc.customer_id' => $m->id])
                    ->exists();
            }
            return $cache[$m->id]
                ? '<span class="label label-danger">نعم</span>'
                : '<span class="label label-success">لا</span>';
        },
        'contentOptions' => ['style' => 'text-align:center;width:70px'],
    ],

    /* العقود */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'العقود',
        'format' => 'raw',
        'value' => function ($m) {
            $contracts = \backend\modules\customers\models\ContractsCustomers::find()
                ->select('contract_id')
                ->where(['customer_id' => $m->id])
                ->column();
            if (empty($contracts)) return '<span class="text-muted">—</span>';
            $links = [];
            foreach ($contracts as $cid) {
                $links[] = Html::a(
                    '<span class="label label-info">' . $cid . '</span>',
                    ['/followUp/follow-up/index', 'contract_id' => $cid],
                    ['data-pjax' => '0', 'title' => "متابعة العقد $cid"]
                );
            }
            return implode(' ', $links);
        },
    ],

    /* الوظيفة */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'job_title',
        'label' => 'الوظيفة',
        'value' => 'jobs.name',
    ],

    /* الإجراءات */
    [
        'class' => 'yii\grid\ActionColumn',
        'contentOptions' => ['style' => 'width:100px;text-align:center'],
        'header' => 'إجراءات',
        'template' => '{all}',
        'buttons' => [
            'all' => fn($url, $m) => ButtonDropdown::widget([
                'encodeLabel' => false,
                'label' => '<i class="fa fa-ellipsis-v"></i>',
                'dropdown' => [
                    'encodeLabels' => false,
                    'items' => [
                        ['label' => '<i class="fa fa-pencil text-primary"></i> تعديل', 'url' => ['update', 'id' => $m->id]],
                        ['label' => '<i class="fa fa-eye text-info"></i> عرض', 'url' => ['view', 'id' => $m->id], 'linkOptions' => ['role' => 'modal-remote']],
                        ['label' => '<i class="fa fa-file-text-o text-success"></i> إضافة عقد', 'url' => ['/contracts/contracts/create', 'id' => $m->id]],
                        '<li class="divider"></li>',
                        ['label' => '<i class="fa fa-phone text-warning"></i> تحديث اتصال', 'url' => ['update-contact', 'id' => $m->id], 'linkOptions' => ['role' => 'modal-remote']],
                        '<li class="divider"></li>',
                        ['label' => '<i class="fa fa-trash text-danger"></i> حذف', 'url' => ['delete', 'id' => $m->id], 'linkOptions' => ['data' => ['method' => 'post', 'confirm' => 'هل أنت متأكد من حذف هذا العميل؟']]],
                    ],
                    'options' => ['class' => 'dropdown-menu-left'],
                ],
                'options' => ['class' => 'btn-default btn-xs'],
                'split' => false,
            ]),
        ],
    ],
];
