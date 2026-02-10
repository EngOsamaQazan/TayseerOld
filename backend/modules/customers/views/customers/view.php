<?php
/**
 * عرض تفاصيل العميل
 */
use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = 'العميل: ' . $model->name;
?>

<div class="customers-view">
    <?= DetailView::widget([
        'model' => $model,
        'options' => ['class' => 'table table-striped table-bordered detail-view'],
        'attributes' => [
            ['attribute' => 'id', 'label' => '#'],
            ['attribute' => 'name', 'label' => 'الاسم'],
            ['attribute' => 'id_number', 'label' => 'الرقم الوطني'],
            [
                'attribute' => 'sex',
                'label' => 'الجنس',
                'value' => $model->sex == 0 ? 'ذكر' : 'أنثى',
            ],
            ['attribute' => 'primary_phone_number', 'label' => 'الهاتف'],
            ['attribute' => 'email', 'label' => 'البريد', 'format' => 'email'],
            ['attribute' => 'facebook_account', 'label' => 'فيسبوك'],
            ['attribute' => 'city', 'label' => 'المدينة'],
            ['attribute' => 'job_title', 'label' => 'الوظيفة', 'value' => $model->jobs ? $model->jobs->name : '—'],
            ['attribute' => 'total_salary', 'label' => 'الراتب', 'format' => ['decimal', 2]],
            ['attribute' => 'bank_name', 'label' => 'البنك'],
            ['attribute' => 'bank_branch', 'label' => 'الفرع'],
            ['attribute' => 'account_number', 'label' => 'رقم الحساب'],
            [
                'attribute' => 'is_social_security',
                'label' => 'ضمان اجتماعي',
                'format' => 'raw',
                'value' => $model->is_social_security ? '<span class="label label-success">نعم</span>' : '<span class="label label-default">لا</span>',
            ],
            ['attribute' => 'social_security_number', 'label' => 'رقم الضمان', 'visible' => (bool)$model->is_social_security],
            ['attribute' => 'notes', 'label' => 'ملاحظات'],
        ],
    ]) ?>
</div>
