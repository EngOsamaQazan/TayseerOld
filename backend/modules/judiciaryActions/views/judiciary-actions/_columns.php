<?php
use yii\helpers\Url;
use yii\helpers\Html;
use backend\modules\judiciaryActions\models\JudiciaryActions;

return [
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'name',
        'label' => 'الاسم',
    ],
    [
        'class'=>'\kartik\grid\DataColumn',
        'attribute'=>'action_type',
        'label' => 'نوع الإجراء',
        'filter' => JudiciaryActions::getActionTypeList(),
        'value' => function($model) {
            return $model->getActionTypeLabel();
        },
        'contentOptions' => function($model) {
            $colors = [
                'request'        => ['background' => '#EFF6FF', 'color' => '#1D4ED8'],  // أزرق
                'court_letter'   => ['background' => '#FEF3C7', 'color' => '#92400E'],  // برتقالي
                'judge_decision' => ['background' => '#FEE2E2', 'color' => '#991B1B'],  // أحمر
                'party_petition' => ['background' => '#DCFCE7', 'color' => '#166534'],  // أخضر
                'incoming_info'  => ['background' => '#F3E8FF', 'color' => '#6B21A8'],  // بنفسجي
            ];
            $c = $colors[$model->action_type] ?? ['background' => '#F3F4F6', 'color' => '#374151'];
            return [
                'style' => "background:{$c['background']}; color:{$c['color']}; font-weight:bold; text-align:center; border-radius:4px;",
            ];
        },
    ],
    [
        'class' => 'kartik\grid\ActionColumn',
        'dropdown' => false,
        'vAlign'=>'middle',
        'urlCreator' => function($action, $model, $key, $index) { 
                return Url::to([$action,'id'=>$key]);
        },
        'viewOptions'=>['title'=>'عرض','data-toggle'=>'tooltip'],
        'updateOptions'=>['title'=>'تحديث', 'data-toggle'=>'tooltip'],
        'deleteOptions'=>['title'=>'حذف',
                          'data-confirm'=>false, 'data-method'=>false,
                          'data-request-method'=>'post',
                          'data-toggle'=>'tooltip',
                          'data-confirm-title'=>'هل أنت متأكد؟',
                          'data-confirm-message'=>'هل أنت متأكد من حذف هذا الإجراء؟'], 
    ],

];   