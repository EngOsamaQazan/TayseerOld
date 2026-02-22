<?php
/**
 * بحث متقدم — الدائرة القانونية — V2
 * Advanced Search — Legal Department — V2 (Grid layout, same as _search.php)
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

$users    = $cache->getOrSet($p['key_users'], fn() => $db->createCommand($p['users_query'])->queryAll(), $d);
$jobs     = $cache->getOrSet($p['key_jobs'], fn() => $db->createCommand($p['jobs_query'])->queryAll(), $d);
$jobTypes = \backend\modules\jobs\models\JobsType::find()->select(['id', 'name'])->asArray()->all();

/* عقود الدائرة القانونية للقائمة المنسدلة */
$legalContracts = ArrayHelper::map(
    \backend\modules\contracts\models\Contracts::find()
        ->select(['id'])->where(['status' => 'legal_department', 'is_deleted' => 0])
        ->asArray()->all(),
    'id', 'id'
);
?>

<?php $form = ActiveForm::begin([
    'id'      => 'legal-search-v2',
    'method'  => 'get',
    'action'  => ['contracts/legal-department'],
    'options' => ['class' => 'ct-search-form'],
]) ?>

<div class="ct-filter-grid">

    <!-- رقم العقد -->
    <div class="ct-filter-group">
        <label>رقم العقد</label>
        <?= $form->field($model, 'id', ['template' => '{input}'])->widget(Select2::class, [
            'data' => $legalContracts,
            'options' => ['placeholder' => 'اختر رقم العقد', 'aria-label' => 'رقم العقد'],
            'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
        ]) ?>
    </div>

    <!-- العميل -->
    <div class="ct-filter-group" style="grid-column: span 2">
        <label>العميل</label>
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
                    'delay' => 250,
                    'data' => new \yii\web\JsExpression('function(p){return{q:p.term}}'),
                    'processResults' => 'function(d){return d}',
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

    <!-- نوع العقد -->
    <div class="ct-filter-group">
        <label>نوع العقد</label>
        <?= $form->field($model, 'type', ['template' => '{input}'])->dropDownList(
            ['normal' => 'عادي', 'solidarity' => 'تضامني'],
            ['class' => 'form-control', 'prompt' => '-- جميع الأنواع --', 'aria-label' => 'نوع العقد']
        ) ?>
    </div>

    <!-- الوظيفة -->
    <div class="ct-filter-group">
        <label>الوظيفة</label>
        <?= $form->field($model, 'job_title', ['template' => '{input}'])->widget(Select2::class, [
            'data' => ArrayHelper::map($jobs, 'id', 'name'),
            'options' => ['placeholder' => 'الوظيفة', 'aria-label' => 'الوظيفة'],
            'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
        ]) ?>
    </div>

    <!-- نوع الوظيفة -->
    <div class="ct-filter-group">
        <label>نوع الوظيفة</label>
        <?= $form->field($model, 'job_Type', ['template' => '{input}'])->widget(Select2::class, [
            'data' => ArrayHelper::map($jobTypes, 'id', 'name'),
            'options' => ['placeholder' => 'نوع الوظيفة', 'aria-label' => 'نوع الوظيفة'],
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

    <!-- Actions -->
    <div class="ct-filter-actions">
        <?= Html::submitButton('<i class="fa fa-search"></i> بحث', [
            'class' => 'ct-btn ct-btn-primary',
        ]) ?>
        <a href="<?= Url::to(['legal-department']) ?>" class="ct-btn ct-btn-outline">
            <i class="fa fa-refresh"></i> <span class="ct-hide-xs">إعادة تعيين</span>
        </a>
    </div>
</div>

<?php ActiveForm::end() ?>
