<?php
use yii\helpers\Url;
use yii\helpers\Html;
use backend\modules\judiciaryActions\models\JudiciaryActions;

$natureStyles = [
    'request'    => ['icon' => 'fa-file-text-o', 'color' => '#3B82F6', 'bg' => '#EFF6FF', 'label' => 'طلب'],
    'document'   => ['icon' => 'fa-file-o',      'color' => '#8B5CF6', 'bg' => '#F5F3FF', 'label' => 'كتاب'],
    'doc_status' => ['icon' => 'fa-exchange',     'color' => '#EA580C', 'bg' => '#FFF7ED', 'label' => 'حالة'],
    'process'    => ['icon' => 'fa-cog',          'color' => '#64748B', 'bg' => '#F1F5F9', 'label' => 'إداري'],
];

$allNames = (new \yii\db\Query())->select(['id', 'name'])->from('os_judiciary_actions')->all();
$nameMap = [];
foreach ($allNames as $an) $nameMap[$an['id']] = $an['name'];

return [
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'id',
        'label' => '#',
        'headerOptions' => ['style' => 'width:4%;text-align:center;padding:5px 2px'],
        'contentOptions' => ['style' => 'text-align:center;font-weight:700;color:#94A3B8;font-size:11px;padding:5px 2px'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'name',
        'label' => 'الإجراء',
        'format' => 'raw',
        'value' => function ($model) use ($natureStyles) {
            $n = $model->action_nature ?: 'process';
            $s = $natureStyles[$n] ?? $natureStyles['process'];
            return '<i class="fa ' . $s['icon'] . '" style="color:' . $s['color'] . ';margin-left:3px;font-size:10px"></i>'
                . '<span style="font-weight:600;color:#1E293B;font-size:12px">' . Html::encode($model->name) . '</span>';
        },
        'headerOptions' => ['style' => 'width:28%;padding:5px 4px'],
        'contentOptions' => ['style' => 'padding:5px 4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'action_nature',
        'label' => 'النوع',
        'filter' => JudiciaryActions::getNatureList(),
        'format' => 'raw',
        'value' => function ($model) use ($natureStyles) {
            $n = $model->action_nature ?: 'process';
            $s = $natureStyles[$n] ?? $natureStyles['process'];
            return '<span style="display:inline-block;padding:1px 5px;border-radius:5px;font-size:10px;font-weight:600;background:' . $s['bg'] . ';color:' . $s['color'] . ';white-space:nowrap">'
                . $s['label'] . '</span>';
        },
        'headerOptions' => ['style' => 'width:7%;padding:5px 2px;text-align:center'],
        'contentOptions' => ['style' => 'padding:5px 2px;text-align:center'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'action_type',
        'label' => 'المرحلة',
        'filter' => JudiciaryActions::getActionTypeList(),
        'format' => 'raw',
        'value' => function ($model) {
            $label = $model->getActionTypeLabel();
            return '<span title="' . Html::encode($label) . '" style="font-size:10px;padding:1px 5px;border-radius:5px;background:#F1F5F9;color:#475569;white-space:nowrap;display:inline-block;max-width:100%;overflow:hidden;text-overflow:ellipsis">' . Html::encode($label) . '</span>';
        },
        'headerOptions' => ['style' => 'width:14%;padding:5px 2px;text-align:center'],
        'contentOptions' => ['style' => 'padding:5px 2px;text-align:center;overflow:hidden'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'العلاقات',
        'format' => 'raw',
        'value' => function ($model) use ($nameMap) {
            $parts = [];

            $docIds = $model->getAllowedDocumentIds();
            if (!empty($docIds)) {
                $titles = [];
                foreach ($docIds as $id) $titles[] = $nameMap[$id] ?? '#' . $id;
                $parts[] = '<span title="كتب: ' . Html::encode(implode(', ', $titles)) . '" style="display:inline-block;padding:1px 4px;border-radius:4px;font-size:9px;background:#F5F3FF;color:#7C3AED;cursor:help"><i class="fa fa-file-o" style="font-size:8px"></i> ' . count($docIds) . '</span>';
            }

            $statusIds = $model->getAllowedStatusIds();
            if (!empty($statusIds)) {
                $titles = [];
                foreach ($statusIds as $id) $titles[] = $nameMap[$id] ?? '#' . $id;
                $parts[] = '<span title="حالات: ' . Html::encode(implode(', ', $titles)) . '" style="display:inline-block;padding:1px 4px;border-radius:4px;font-size:9px;background:#FFF7ED;color:#C2410C;cursor:help"><i class="fa fa-exchange" style="font-size:8px"></i> ' . count($statusIds) . '</span>';
            }

            $parentIds = $model->getParentRequestIdList();
            if (!empty($parentIds)) {
                $titles = [];
                foreach ($parentIds as $id) $titles[] = $nameMap[$id] ?? '#' . $id;
                $parts[] = '<span title="تبعيات: ' . Html::encode(implode(', ', $titles)) . '" style="display:inline-block;padding:1px 4px;border-radius:4px;font-size:9px;background:#EFF6FF;color:#2563EB;cursor:help"><i class="fa fa-level-up" style="font-size:8px"></i> ' . count($parentIds) . '</span>';
            }

            return empty($parts) ? '<span style="color:#CBD5E1;font-size:11px">—</span>' : implode(' ', $parts);
        },
        'headerOptions' => ['style' => 'width:12%;padding:5px 2px;text-align:center'],
        'contentOptions' => ['style' => 'padding:4px 2px;text-align:center;white-space:nowrap'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'استخدام',
        'format' => 'raw',
        'value' => function ($model) use ($usageCounts) {
            $count = (int)($usageCounts[$model->id] ?? 0);
            if ($count === 0) return '<span style="color:#CBD5E1;font-size:11px">0</span>';
            $color = $count > 50 ? '#16A34A' : ($count > 10 ? '#2563EB' : '#94A3B8');
            $url = Url::to(['usage-details', 'id' => $model->id]);
            return '<a href="' . $url . '" role="modal-remote" title="عرض القضايا المرتبطة" '
                . 'style="font-weight:700;font-size:11px;color:' . $color . ';cursor:pointer;text-decoration:none;'
                . 'display:inline-flex;align-items:center;gap:2px;padding:2px 6px;border-radius:5px;'
                . 'background:' . ($count > 50 ? '#F0FDF4' : ($count > 10 ? '#EFF6FF' : '#F8FAFC')) . '">'
                . number_format($count) . '</a>';
        },
        'contentOptions' => ['style' => 'text-align:center;padding:5px 2px'],
        'headerOptions' => ['style' => 'width:9%;text-align:center;padding:5px 2px'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => '',
        'format' => 'raw',
        'value' => function ($model) {
            $id = $model->id;
            $editUrl = Url::to(['update', 'id' => $id]);
            $viewUrl = Url::to(['view', 'id' => $id]);
            $delUrl  = Url::to(['confirm-delete', 'id' => $id]);
            return '<div class="ja-action-btns">'
                . '<a href="' . $viewUrl . '" role="modal-remote" title="عرض" class="ja-act ja-act-view"><i class="fa fa-eye"></i></a>'
                . '<a href="' . $editUrl . '" role="modal-remote" title="تعديل" class="ja-act ja-act-edit"><i class="fa fa-pencil"></i></a>'
                . '<a href="' . $delUrl . '" role="modal-remote" title="حذف" class="ja-act ja-act-del" data-confirm="false" data-method="false"><i class="fa fa-trash-o"></i></a>'
                . '</div>';
        },
        'headerOptions' => ['style' => 'width:10%;text-align:center;padding:5px 2px'],
        'contentOptions' => ['style' => 'padding:4px 2px;text-align:center'],
    ],
];
