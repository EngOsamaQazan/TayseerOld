<?php
/**
 * بحث متقدم - القضايا — تصميم احترافي مدمج
 */
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use backend\helpers\FlatpickrWidget;
use backend\modules\court\models\Court;
use backend\modules\lawyers\models\Lawyers;
use backend\modules\judiciaryType\models\JudiciaryType;

$cache = Yii::$app->cache;
$courts  = $cache->getOrSet('lookup_courts', fn() => ArrayHelper::map(Court::find()->asArray()->all(), 'id', 'name'), 3600);
$types   = $cache->getOrSet('lookup_judiciary_types', fn() => ArrayHelper::map(JudiciaryType::find()->asArray()->all(), 'id', 'name'), 3600);
$lawyers = $cache->getOrSet('lookup_lawyers', fn() => ArrayHelper::map(Lawyers::find()->asArray()->all(), 'id', 'name'), 3600);
$hasFilters = $model->judiciary_number || $model->contract_id || $model->court_id || $model->type_id || $model->lawyer_id || $model->year || $model->from_income_date || $model->to_income_date;
?>

<style>
.jud-search { background:#fff; border:1px solid #E2E8F0; border-radius:10px; margin-bottom:14px; }
.jud-search-header {
    display:flex; align-items:center; justify-content:space-between; padding:10px 16px;
    cursor:pointer; user-select:none;
}
.jud-search-header h4 { margin:0; font-size:13px; font-weight:700; color:#475569; display:flex; align-items:center; gap:8px; }
.jud-search-header h4 i { color:#800020; }
.jud-search-toggle { color:#94A3B8; font-size:12px; transition:transform .2s; }
.jud-search.collapsed .jud-search-toggle { transform:rotate(-90deg); }
.jud-search-body { padding:0 16px 14px; }
.jud-search.collapsed .jud-search-body { display:none; }
.jud-search-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); gap:10px; }
.jud-search-grid .form-group { margin-bottom:0; }
.jud-search-grid .form-group label { font-size:11px; color:#64748B; font-weight:600; margin-bottom:3px; }
.jud-search-grid .form-control, .jud-search-grid .select2-container { font-size:12px !important; }
.jud-search-grid .select2-container .select2-selection--single { height:32px !important; min-height:32px !important; }
.jud-search-grid .select2-container .select2-selection--single .select2-selection__rendered { line-height:30px !important; font-size:12px !important; padding-right:8px !important; }
.jud-search-grid .form-control { height:32px; padding:4px 8px; }
.jud-search-actions { display:flex; gap:8px; align-items:end; }
.jud-search-actions .btn { height:32px; font-size:12px; padding:0 14px; border-radius:6px; display:flex; align-items:center; gap:4px; }
</style>

<div class="jud-search <?= $hasFilters ? '' : '' ?>">
    <div class="jud-search-header" onclick="$(this).closest('.jud-search').toggleClass('collapsed')">
        <h4><i class="fa fa-search"></i> فلاتر البحث <?= $hasFilters ? '<span style="background:#800020;color:#fff;font-size:10px;padding:1px 8px;border-radius:10px">نشط</span>' : '' ?></h4>
        <i class="fa fa-chevron-down jud-search-toggle"></i>
    </div>
    <div class="jud-search-body">
        <?php $form = ActiveForm::begin([
            'id' => 'judiciary-search',
            'method' => 'get',
            'action' => ['index'],
            'options' => ['class' => 'jud-search-form'],
        ]) ?>

        <div class="jud-search-grid">
            <?= $form->field($model, 'judiciary_number')->textInput(['placeholder' => 'مثل 2313'])->label('رقم القضية') ?>
            <?= $form->field($model, 'contract_id')->textInput(['placeholder' => 'رقم العقد', 'type' => 'number'])->label('رقم العقد') ?>
            <?= $form->field($model, 'court_id')->widget(Select2::class, [
                'data' => $courts, 'options' => ['placeholder' => 'الكل'],
                'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl', 'dropdownAutoWidth' => true],
            ])->label('المحكمة') ?>
            <?= $form->field($model, 'type_id')->widget(Select2::class, [
                'data' => $types, 'options' => ['placeholder' => 'الكل'],
                'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl', 'dropdownAutoWidth' => true],
            ])->label('النوع') ?>
            <?= $form->field($model, 'lawyer_id')->widget(Select2::class, [
                'data' => $lawyers, 'options' => ['placeholder' => 'الكل'],
                'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl', 'dropdownAutoWidth' => true],
            ])->label('المحامي') ?>
            <?= $form->field($model, 'year')->dropDownList($model->year(), ['prompt' => 'الكل'])->label('السنة') ?>
            <?= $form->field($model, 'from_income_date')->widget(FlatpickrWidget::class, [
                'options' => ['placeholder' => 'من', 'style' => 'font-size:12px'],
                'pluginOptions' => ['dateFormat' => 'Y-m-d'],
            ])->label('ورود من') ?>
            <?= $form->field($model, 'to_income_date')->widget(FlatpickrWidget::class, [
                'options' => ['placeholder' => 'إلى', 'style' => 'font-size:12px'],
                'pluginOptions' => ['dateFormat' => 'Y-m-d'],
            ])->label('ورود إلى') ?>
            <div class="jud-search-actions">
                <?= Html::submitButton('<i class="fa fa-search"></i> بحث', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('<i class="fa fa-times"></i>', ['index'], ['class' => 'btn btn-default', 'title' => 'مسح الفلاتر']) ?>
            </div>
        </div>

        <?php ActiveForm::end() ?>
    </div>
</div>
