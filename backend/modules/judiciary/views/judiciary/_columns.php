<?php
/**
 * أعمدة جدول القضايا
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use common\helper\Permissions;

return [
    /* # */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'id',
        'label' => '#',
        'headerOptions' => ['style' => 'width:5%;text-align:center'],
        'contentOptions' => ['style' => 'text-align:center;font-weight:600;color:#94A3B8;font-size:11px'],
    ],

    /* رقم العقد */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'contract_id',
        'label' => 'العقد',
        'format' => 'raw',
        'value' => fn($m) => '<a href="' . Url::to(['/followUp/follow-up/index', 'contract_id' => $m->contract_id]) . '" style="font-weight:700;color:#800020;text-decoration:none">' . $m->contract_id . '</a>',
        'headerOptions' => ['style' => 'width:7%;text-align:center'],
        'contentOptions' => ['style' => 'text-align:center'],
    ],

    /* العميل */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'العميل',
        'format' => 'raw',
        'value' => function ($m) {
            $parts = [];
            foreach ($m->customersAndGuarantor as $customer) {
                $parts[] = '<span style="font-weight:600;color:#1E293B">' . Html::encode($customer->name) . '</span>';
            }
            return implode('<span style="color:#CBD5E1;margin:0 3px">|</span>', $parts) ?: '<span style="color:#CBD5E1">—</span>';
        },
        'headerOptions' => ['style' => 'width:22%'],
        'contentOptions' => ['style' => 'max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap'],
    ],

    /* الوظيفة */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'الوظيفة',
        'format' => 'raw',
        'value' => function ($m) {
            static $jobCache = [];
            $jobNames = [];
            foreach ($m->customersAndGuarantor as $customer) {
                $jid = $customer->job_title;
                if (!$jid) continue;
                if (!isset($jobCache[$jid])) {
                    $job = \backend\modules\jobs\models\Jobs::findOne($jid);
                    $jobCache[$jid] = $job ? $job->name : null;
                }
                if ($jobCache[$jid] && !in_array($jobCache[$jid], $jobNames)) {
                    $jobNames[] = $jobCache[$jid];
                }
            }
            if (empty($jobNames)) return '<span style="color:#CBD5E1;font-size:11px">—</span>';
            $out = [];
            foreach ($jobNames as $jn) {
                $out[] = '<span style="display:inline-block;padding:1px 6px;border-radius:5px;font-size:10px;font-weight:600;background:#F0FDF4;color:#15803D">' . Html::encode($jn) . '</span>';
            }
            return implode(' ', $out);
        },
        'headerOptions' => ['style' => 'width:16%'],
        'contentOptions' => ['style' => 'max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap'],
    ],

    /* المحكمة */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'court_id',
        'label' => 'المحكمة',
        'format' => 'raw',
        'value' => function ($m) {
            $name = $m->court->name ?? '—';
            return '<span style="font-size:11px;color:#475569">' . Html::encode($name) . '</span>';
        },
        'headerOptions' => ['style' => 'width:14%'],
        'contentOptions' => ['style' => 'max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap'],
    ],

    /* المحامي */
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'lawyer_id',
        'label' => 'المحامي',
        'format' => 'raw',
        'value' => function ($m) {
            $name = $m->lawyer->name ?? '—';
            return '<span style="font-size:11px;color:#475569">' . Html::encode($name) . '</span>';
        },
        'headerOptions' => ['style' => 'width:14%'],
        'contentOptions' => ['style' => 'max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap'],
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
            if ($year) {
                return '<span style="font-family:\'Courier New\',monospace;font-weight:700;color:#1E293B;font-size:12px">' . $num . '</span>'
                     . '<span style="color:#94A3B8;font-size:10px;margin:0 2px">-</span>'
                     . '<span style="font-family:\'Courier New\',monospace;color:#64748B;font-size:11px">' . $year . '</span>';
            }
            return '<span style="font-family:\'Courier New\',monospace;color:#64748B">' . $num . '</span>';
        },
        'headerOptions' => ['style' => 'width:12%;text-align:center'],
        'contentOptions' => ['style' => 'text-align:center;white-space:nowrap'],
    ],

    /* الإجراءات */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => '',
        'format' => 'raw',
        'value' => function ($m) {
            $addActionUrl = Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/create-followup-judicary-custamer-action', 'contractID' => $m->contract_id]);
            $editUrl  = Url::to(['/judiciary/judiciary/update', 'id' => $m->id, 'contract_id' => $m->contract_id]);
            $printUrl = Url::to(['/judiciary/judiciary/print-case', 'id' => $m->id]);
            $followUrl = Url::to(['/followUp/follow-up/index', 'contract_id' => $m->contract_id]);
            $delUrl   = Url::to(['/judiciary/judiciary/delete', 'id' => $m->id]);

            $menu = '';
            if (Permissions::can(Permissions::JUD_CREATE)) {
                $menu .= '<a href="' . $addActionUrl . '" role="modal-remote"><i class="fa fa-plus" style="color:#16A34A"></i> إضافة إجراء</a>';
                $menu .= '<div class="jud-act-divider"></div>';
            }
            if (Permissions::can(Permissions::JUD_UPDATE)) {
                $menu .= '<a href="' . $editUrl . '"><i class="fa fa-pencil" style="color:#3B82F6"></i> تعديل</a>';
            }
            $menu .= '<a href="' . $printUrl . '"><i class="fa fa-print" style="color:#0EA5E9"></i> طباعة</a>';
            $menu .= '<a href="' . $followUrl . '"><i class="fa fa-comments" style="color:#16A34A"></i> المتابعة</a>';
            if (Permissions::can(Permissions::JUD_DELETE)) {
                $menu .= '<div class="jud-act-divider"></div>';
                $menu .= '<a href="' . $delUrl . '" data-method="post" data-confirm="هل أنت متأكد من حذف هذه القضية؟"><i class="fa fa-trash" style="color:#EF4444"></i> حذف</a>';
            }

            return '<div class="jud-act-wrap">'
                . '<button type="button" class="jud-act-trigger"><i class="fa fa-ellipsis-v"></i></button>'
                . '<div class="jud-act-menu">' . $menu . '</div>'
                . '</div>';
        },
        'headerOptions' => ['style' => 'width:5%;text-align:center'],
        'contentOptions' => ['style' => 'text-align:center;overflow:visible;position:relative'],
    ],
];
