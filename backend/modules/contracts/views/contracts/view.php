<?php
/**
 * عرض تفاصيل العقد
 */
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\DetailView;
use common\helper\Permissions;

$this->title = 'العقد #' . $model->id;
$customerNames = implode('، ', ArrayHelper::map($model->customers, 'id', 'name'));
?>

<div class="contracts-view">
    <?= DetailView::widget([
        'model' => $model,
        'options' => ['class' => 'table table-striped table-bordered detail-view'],
        'attributes' => [
            ['attribute' => 'id', 'label' => 'رقم العقد'],
            ['label' => 'البائع', 'value' => $model->seller->name ?? '—'],
            ['label' => 'العميل', 'value' => $customerNames ?: '—'],
            ['attribute' => 'type', 'label' => 'النوع', 'value' => $model->type === 'normal' ? 'عادي' : 'تضامني'],
            ['label' => 'الشركة', 'value' => $model->company->name ?? '—'],
            ['attribute' => 'Date_of_sale', 'label' => 'تاريخ البيع'],
            ['attribute' => 'total_value', 'label' => 'القيمة الإجمالية', 'format' => ['decimal', 2]],
            ['attribute' => 'first_installment_value', 'label' => 'الدفعة الأولى', 'format' => ['decimal', 2]],
            ['attribute' => 'first_installment_date', 'label' => 'تاريخ أول قسط'],
            ['attribute' => 'monthly_installment_value', 'label' => 'القسط الشهري', 'format' => ['decimal', 2]],
            ['attribute' => 'commitment_discount', 'label' => 'خصم الالتزام', 'format' => ['decimal', 2]],
            [
                'attribute' => 'status', 'label' => 'الحالة', 'format' => 'raw',
                'value' => function () use ($model) {
                    $colors = ['active' => 'success', 'judiciary' => 'danger', 'legal_department' => 'info', 'settlement' => 'primary', 'finished' => 'default', 'canceled' => 'default'];
                    $labels = ['active' => 'نشط', 'judiciary' => 'قضاء', 'legal_department' => 'قانوني', 'finished' => 'منتهي', 'canceled' => 'ملغي', 'settlement' => 'تسوية'];
                    return '<span class="label label-' . ($colors[$model->status] ?? 'default') . '">' . ($labels[$model->status] ?? $model->status) . '</span>';
                },
            ],
            ['attribute' => 'notes', 'label' => 'ملاحظات', 'format' => 'ntext'],
        ],
    ]) ?>

    <?= $this->render('_adjustments', ['contract_id' => $model->id]) ?>
</div>
