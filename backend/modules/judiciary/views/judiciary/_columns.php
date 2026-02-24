<?php
/**
 * أعمدة جدول القضايا
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use common\helper\Permissions;
use backend\helpers\NameHelper;

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

    /* الأطراف (عميل + وظيفة) */
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'الأطراف',
        'format' => 'raw',
        'value' => function ($m) {
            static $jobCache = [];
            $customers = $m->customersAndGuarantor;
            if (empty($customers)) return '<span style="color:#CBD5E1">—</span>';

            $rows = [];
            foreach ($customers as $c) {
                $full = $c->name;
                $short = NameHelper::short($full);
                $jid = $c->job_title;
                $jobName = '';
                if ($jid) {
                    if (!isset($jobCache[$jid])) {
                        $job = \backend\modules\jobs\models\Jobs::findOne($jid);
                        $jobCache[$jid] = $job ? $job->name : '';
                    }
                    $jobName = $jobCache[$jid];
                }
                $nameHtml = '<span style="font-weight:600;color:#1E293B;font-size:11px" title="' . Html::encode($full) . '">' . Html::encode($short) . '</span>';
                if ($jobName) {
                    $nameHtml .= ' <span style="display:inline-block;padding:0 5px;border-radius:4px;font-size:9px;font-weight:600;background:#F0FDF4;color:#15803D;vertical-align:middle">' . Html::encode($jobName) . '</span>';
                }
                $rows[] = $nameHtml;
            }
            return '<div style="display:flex;flex-direction:column;gap:2px;max-height:60px;overflow-y:auto;scrollbar-width:thin">' . implode('', array_map(fn($r) => '<div style="white-space:nowrap">' . $r . '</div>', $rows)) . '</div>';
        },
        'headerOptions' => ['style' => 'width:30%'],
        'contentOptions' => ['style' => 'max-width:280px;padding:4px 6px'],
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
            $full = $m->lawyer->name ?? '—';
            if ($full === '—') return '<span style="color:#CBD5E1">—</span>';
            $short = NameHelper::short($full);
            return '<span style="font-size:11px;color:#475569" title="' . Html::encode($full) . '">' . Html::encode($short) . '</span>';
        },
        'headerOptions' => ['style' => 'width:10%'],
        'contentOptions' => ['style' => 'white-space:nowrap'],
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
