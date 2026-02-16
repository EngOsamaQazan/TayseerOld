<?php
/**
 * بحث متقدم — العقود — V2
 * Advanced Search — Contracts — V2 (Grid layout)
 */

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use kartik\select2\Select2;

$cache = Yii::$app->cache;
$p     = Yii::$app->params;
$d     = $p['time_duration'];
$db    = Yii::$app->db;

$users   = $cache->getOrSet($p['key_users'], fn() => $db->createCommand($p['users_query'])->queryAll(), $d);
$jobType = $cache->getOrSet($p['key_job_type'], fn() => $db->createCommand($p['job_type_query'])->queryAll(), $d);

$statusList = [
    '' => '-- جميع الحالات --',
    'active' => 'نشط',
    'pending' => 'معلّق',
    'legal_department' => 'قانوني',
    'judiciary' => 'قضاء',
    'settlement' => 'تسوية',
    'finished' => 'منتهي',
    'canceled' => 'ملغي',
    'refused' => 'مرفوض',
];
?>

<?php $form = ActiveForm::begin([
    'id'      => 'contracts-search',
    'method'  => 'get',
    'action'  => ['index'],
    'options' => ['class' => 'ct-search-form'],
]) ?>

<div class="ct-filter-grid">

    <!-- رقم العقد -->
    <div class="ct-filter-group">
        <label for="contractssearch-id">رقم العقد</label>
        <?= $form->field($model, 'id', ['template' => '{input}'])->textInput([
            'placeholder' => 'أدخل رقم العقد',
            'type' => 'number',
            'class' => 'form-control',
            'aria-label' => 'رقم العقد',
        ]) ?>
    </div>

    <!-- العميل -->
    <div class="ct-filter-group ct-filter-customer">
        <label for="contractssearch-customer_name">العميل</label>
        <?= $form->field($model, 'customer_name', ['template' => '{input}'])->widget(Select2::class, [
            'initValueText' => $model->customer_name,
            'options' => [
                'placeholder' => 'ابحث بالاسم أو الرقم الوطني...',
                'aria-label' => 'بحث العميل',
            ],
            'pluginOptions' => [
                'allowClear' => true,
                'dir' => 'rtl',
                'minimumInputLength' => 1,
                'ajax' => [
                    'url' => Url::to(['/customers/customers/search-customers', 'mode' => 'name']),
                    'dataType' => 'json',
                    'delay' => 300,
                    'data' => new \yii\web\JsExpression('function(p){return{q:p.term}}'),
                    'processResults' => new \yii\web\JsExpression('function(d){return d}'),
                    'cache' => true,
                ],
                'templateResult' => new \yii\web\JsExpression(
                    "function(i){if(i.loading)return i.text;" .
                    "var h='<div><b>'+i.text+'</b>';" .
                    "if(i.id_number)h+=' <small style=\"color:#64748b\">· '+i.id_number+'</small>';" .
                    "if(i.phone)h+=' <small style=\"color:#0891b2\">☎ '+i.phone+'</small>';" .
                    "return $(h+'</div>')}"
                ),
                'templateSelection' => new \yii\web\JsExpression("function(i){return i.text||i.id}"),
            ],
        ]) ?>
    </div>

    <!-- الحالة -->
    <div class="ct-filter-group">
        <label for="contractssearch-status">الحالة</label>
        <?= $form->field($model, 'status', ['template' => '{input}'])->dropDownList($statusList, [
            'class' => 'form-control',
            'aria-label' => 'الحالة',
        ]) ?>
    </div>

    <!-- من تاريخ -->
    <div class="ct-filter-group">
        <label>من تاريخ</label>
        <?= $form->field($model, 'from_date', ['template' => '{input}'])->widget(DatePicker::class, [
            'options' => ['placeholder' => 'من تاريخ', 'aria-label' => 'من تاريخ', 'autocomplete' => 'off'],
            'pluginOptions' => ['autoclose' => true, 'format' => 'yyyy-mm-dd', 'todayHighlight' => true],
        ]) ?>
    </div>

    <!-- إلى تاريخ -->
    <div class="ct-filter-group">
        <label>إلى تاريخ</label>
        <?= $form->field($model, 'to_date', ['template' => '{input}'])->widget(DatePicker::class, [
            'options' => ['placeholder' => 'إلى تاريخ', 'aria-label' => 'إلى تاريخ', 'autocomplete' => 'off'],
            'pluginOptions' => ['autoclose' => true, 'format' => 'yyyy-mm-dd', 'todayHighlight' => true],
        ]) ?>
    </div>

    <!-- البائع -->
    <div class="ct-filter-group">
        <label>البائع</label>
        <?= $form->field($model, 'seller_id', ['template' => '{input}'])->widget(Select2::class, [
            'data' => ArrayHelper::map($users, 'id', 'username'),
            'options' => ['placeholder' => 'اختر البائع', 'aria-label' => 'البائع'],
            'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
        ]) ?>
    </div>

    <!-- المتابع -->
    <div class="ct-filter-group">
        <label>المتابع</label>
        <?= $form->field($model, 'followed_by', ['template' => '{input}'])->widget(Select2::class, [
            'data' => ArrayHelper::map($users, 'id', 'username'),
            'options' => ['placeholder' => 'اختر المتابع', 'aria-label' => 'المتابع'],
            'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
        ]) ?>
    </div>

    <!-- الهاتف -->
    <div class="ct-filter-group">
        <label>الهاتف</label>
        <?= $form->field($model, 'phone_number', ['template' => '{input}'])->textInput([
            'placeholder' => 'رقم الهاتف',
            'class' => 'form-control',
            'aria-label' => 'الهاتف',
        ]) ?>
    </div>

    <!-- نوع الوظيفة -->
    <div class="ct-filter-group">
        <label>نوع الوظيفة</label>
        <?= $form->field($model, 'job_Type', ['template' => '{input}'])->widget(Select2::class, [
            'data' => ArrayHelper::map($jobType, 'id', 'name'),
            'options' => ['placeholder' => 'نوع الوظيفة', 'aria-label' => 'نوع الوظيفة'],
            'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
        ]) ?>
    </div>

    <!-- عدد النتائج -->
    <div class="ct-filter-group">
        <label>نتائج/صفحة</label>
        <?= $form->field($model, 'number_row', ['template' => '{input}'])->textInput([
            'placeholder' => '20',
            'type' => 'number',
            'class' => 'form-control',
            'min' => 5,
            'max' => 200,
            'aria-label' => 'عدد النتائج في الصفحة',
        ]) ?>
    </div>

    <!-- Actions -->
    <div class="ct-filter-actions">
        <?= Html::submitButton('<i class="fa fa-search"></i> بحث', [
            'class' => 'ct-btn ct-btn-primary',
        ]) ?>
        <a href="<?= Url::to(['index']) ?>" class="ct-btn ct-btn-outline">
            <i class="fa fa-refresh"></i> <span class="ct-hide-xs">إعادة تعيين</span>
        </a>
    </div>
</div>

<?php ActiveForm::end() ?>
