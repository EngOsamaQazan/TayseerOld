<?php
use yii\helpers\Url;
use yii\helpers\Html;
use backend\modules\judiciaryActions\models\JudiciaryActions;

$natureStyles = [
    'request'    => ['icon' => 'fa-file-text-o', 'color' => '#3B82F6', 'bg' => '#EFF6FF', 'label' => 'طلب إجرائي'],
    'document'   => ['icon' => 'fa-file-o',      'color' => '#8B5CF6', 'bg' => '#F5F3FF', 'label' => 'كتاب / مذكرة'],
    'doc_status' => ['icon' => 'fa-exchange',     'color' => '#EA580C', 'bg' => '#FFF7ED', 'label' => 'حالة كتاب'],
    'process'    => ['icon' => 'fa-cog',          'color' => '#64748B', 'bg' => '#F1F5F9', 'label' => 'إجراء إداري'],
];

// Load all action names for relationship display
$allNames = (new \yii\db\Query())->select(['id', 'name'])->from('os_judiciary_actions')->all();
$nameMap = [];
foreach ($allNames as $an) $nameMap[$an['id']] = $an['name'];

return [
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'id',
        'label' => '#',
        'headerOptions' => ['style' => 'width:50px'],
        'contentOptions' => ['style' => 'text-align:center;font-weight:700;color:#94A3B8;font-size:12px'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'name',
        'label' => 'اسم الإجراء',
        'format' => 'raw',
        'value' => function ($model) use ($natureStyles) {
            $n = $model->action_nature ?: 'process';
            $s = $natureStyles[$n] ?? $natureStyles['process'];
            $icon = '<i class="fa ' . $s['icon'] . '" style="color:' . $s['color'] . ';margin-left:6px"></i>';
            $name = '<span style="font-weight:600;color:#1E293B">' . Html::encode($model->name) . '</span>';
            return $icon . $name;
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'action_nature',
        'label' => 'الطبيعة',
        'filter' => JudiciaryActions::getNatureList(),
        'format' => 'raw',
        'value' => function ($model) use ($natureStyles) {
            $n = $model->action_nature ?: 'process';
            $s = $natureStyles[$n] ?? $natureStyles['process'];
            return '<span class="ja-nature-badge" style="background:' . $s['bg'] . ';color:' . $s['color'] . '">'
                . '<i class="fa ' . $s['icon'] . '"></i> ' . $s['label'] . '</span>';
        },
        'contentOptions' => ['style' => 'white-space:nowrap'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'attribute' => 'action_type',
        'label' => 'المرحلة',
        'filter' => JudiciaryActions::getActionTypeList(),
        'format' => 'raw',
        'value' => function ($model) {
            $label = $model->getActionTypeLabel();
            return '<span style="font-size:11px;padding:2px 8px;border-radius:6px;background:#F1F5F9;color:#475569">' . Html::encode($label) . '</span>';
        },
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'الكتب المسموحة',
        'format' => 'raw',
        'value' => function ($model) use ($nameMap) {
            $ids = $model->getAllowedDocumentIds();
            if (empty($ids)) return '<span style="color:#CBD5E1">—</span>';
            $pills = [];
            foreach ($ids as $id) {
                $n = $nameMap[$id] ?? '#' . $id;
                $pills[] = '<span class="ja-rel-pill" style="background:#F5F3FF;color:#7C3AED">' . Html::encode($n) . '</span>';
            }
            return implode('', $pills);
        },
        'headerOptions' => ['style' => 'min-width:120px'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'الحالات المسموحة',
        'format' => 'raw',
        'value' => function ($model) use ($nameMap) {
            $ids = $model->getAllowedStatusIds();
            if (empty($ids)) return '<span style="color:#CBD5E1">—</span>';
            $pills = [];
            foreach ($ids as $id) {
                $n = $nameMap[$id] ?? '#' . $id;
                $pills[] = '<span class="ja-rel-pill" style="background:#FFF7ED;color:#C2410C">' . Html::encode($n) . '</span>';
            }
            return implode('', $pills);
        },
        'headerOptions' => ['style' => 'min-width:120px'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'يتبع لطلبات',
        'format' => 'raw',
        'value' => function ($model) use ($nameMap) {
            $ids = $model->getParentRequestIdList();
            if (empty($ids)) return '<span style="color:#CBD5E1">—</span>';
            $pills = [];
            foreach ($ids as $id) {
                $n = $nameMap[$id] ?? '#' . $id;
                $pills[] = '<span class="ja-rel-pill" style="background:#EFF6FF;color:#2563EB">' . Html::encode($n) . '</span>';
            }
            return implode('', $pills);
        },
        'headerOptions' => ['style' => 'min-width:120px'],
    ],
    [
        'class' => '\kartik\grid\DataColumn',
        'label' => 'استخدام',
        'format' => 'raw',
        'value' => function ($model) {
            $count = (int)(new \yii\db\Query())
                ->from('os_judiciary_customers_actions')
                ->where(['judiciary_actions_id' => $model->id, 'is_deleted' => 0])
                ->count();
            if ($count === 0) return '<span style="color:#CBD5E1">0</span>';
            $color = $count > 50 ? '#16A34A' : ($count > 10 ? '#2563EB' : '#94A3B8');
            return '<span style="font-weight:700;color:' . $color . '">' . $count . '</span>';
        },
        'contentOptions' => ['style' => 'text-align:center'],
        'headerOptions' => ['style' => 'width:70px;text-align:center'],
    ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'dropdown' => false,
        'vAlign' => 'middle',
        'urlCreator' => function ($action, $model, $key, $index) {
            return Url::to([$action, 'id' => $key]);
        },
        'template' => '{update} {view} {delete}',
        'viewOptions' => ['title' => 'عرض', 'data-toggle' => 'tooltip', 'role' => 'modal-remote'],
        'updateOptions' => ['title' => 'تعديل', 'data-toggle' => 'tooltip', 'role' => 'modal-remote'],
        'deleteOptions' => [
            'title' => 'حذف',
            'data-toggle' => 'tooltip',
            'data-confirm' => false,
            'data-method' => false,
            'data-request-method' => 'post',
            'data-confirm-title' => 'تأكيد الحذف',
            'data-confirm-message' => 'هل أنت متأكد من حذف هذا الإجراء؟',
        ],
        'headerOptions' => ['style' => 'width:90px;text-align:center'],
    ],
];
