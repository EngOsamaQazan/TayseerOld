<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 *  نموذج إنشاء / تعديل تسوية — تصميم نظيف للمودال
 * ═══════════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;

/* @var $model backend\modules\loanScheduling\models\LoanScheduling */

/* قالب موحد بدون نجمة */
$tpl = '{label}{input}{error}';
?>

<style>
.loan-modal-form .form-group{margin-bottom:18px}
.loan-modal-form .control-label{font-size:13px;font-weight:600;color:#555;margin-bottom:6px;display:block}
.loan-modal-form .control-label .fa{margin-left:5px;color:var(--fin-primary,#800020);font-size:12px}
.loan-modal-form .form-control{border-radius:6px;height:42px;font-size:14px;border:1.5px solid #ddd;transition:border-color .2s}
.loan-modal-form .form-control:focus{border-color:var(--fin-primary,#800020);box-shadow:0 0 0 3px rgba(128,0,32,.08)}
.loan-modal-form .input-group .form-control{border-radius:0 6px 6px 0}
.loan-modal-form .input-group-addon{border-radius:6px 0 0 6px;border:1.5px solid #ddd;border-right:0;background:#f8f9fa}
.loan-modal-form .help-block{font-size:12px;margin-top:4px}
.loan-modal-form .has-error .form-control{border-color:#e74c3c}
.loan-modal-form .has-error .help-block{color:#e74c3c}
.loan-modal-form hr{margin:16px 0;border-color:#f0f0f0}
.loan-modal-form .loan-section-title{font-size:12px;font-weight:700;color:#999;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;padding-bottom:6px;border-bottom:2px solid #f0f0f0}
.loan-modal-form .loan-contract-input{font-size:18px!important;font-weight:700!important;text-align:center;height:52px!important;letter-spacing:1px}
.loan-modal-form .loan-amount-input{font-weight:600;text-align:center;font-size:16px!important}
.loan-modal-form .btn-submit{width:100%;height:48px;font-size:15px;font-weight:700;border-radius:8px;border:0;letter-spacing:.3px}
.loan-modal-form .btn-submit-create{background:var(--fin-primary,#800020);color:#fff}
.loan-modal-form .btn-submit-create:hover{background:#600018;color:#fff}
.loan-modal-form .btn-submit-update{background:#2563eb;color:#fff}
.loan-modal-form .btn-submit-update:hover{background:#1d4ed8;color:#fff}
</style>

<div class="loan-modal-form">
<?php $form = ActiveForm::begin([
    'id' => 'loan-form',
    'fieldConfig' => ['template' => $tpl, 'options' => ['tag' => 'div']],
]); ?>

<?php if ($model->isNewRecord && empty($model->contract_id)): ?>
<!-- ═══ رقم العقد ═══ -->
<div class="loan-section-title">بيانات العقد</div>
<div class="row">
    <div class="col-xs-12">
        <?= $form->field($model, 'contract_id')->textInput([
            'type' => 'number',
            'class' => 'form-control loan-contract-input',
            'placeholder' => 'أدخل رقم العقد',
        ])->label('<i class="fa fa-file-text-o"></i> رقم العقد') ?>
    </div>
</div>
<hr>
<?php endif ?>

<!-- ═══ تفاصيل التسوية ═══ -->
<div class="loan-section-title">تفاصيل التسوية</div>
<div class="row">
    <div class="col-sm-6">
        <?= $form->field($model, 'monthly_installment')->textInput([
            'type' => 'number',
            'step' => '0.01',
            'class' => 'form-control loan-amount-input',
            'placeholder' => '0.00',
        ])->label('<i class="fa fa-money"></i> القسط الشهري') ?>
    </div>
    <div class="col-sm-6">
        <?= $form->field($model, 'first_installment_date')->widget(DatePicker::class, [
            'pluginOptions' => ['autoclose' => true, 'format' => 'yyyy-mm-dd', 'todayHighlight' => true],
            'options' => ['placeholder' => 'yyyy-mm-dd', 'class' => 'form-control'],
        ])->label('<i class="fa fa-calendar"></i> تاريخ أول قسط') ?>
    </div>
</div>
<div class="row">
    <div class="col-sm-6">
        <?= $form->field($model, 'new_installment_date')->widget(DatePicker::class, [
            'pluginOptions' => ['autoclose' => true, 'format' => 'yyyy-mm-dd', 'todayHighlight' => true],
            'options' => ['placeholder' => 'yyyy-mm-dd', 'class' => 'form-control'],
        ])->label('<i class="fa fa-calendar-plus-o"></i> تاريخ القسط الجديد') ?>
    </div>
</div>

<!-- ═══ زر الحفظ ═══ -->
<hr>
<div class="form-group" style="margin-bottom:0">
    <?= Html::submitButton(
        $model->isNewRecord
            ? '<i class="fa fa-plus-circle"></i> إنشاء التسوية'
            : '<i class="fa fa-check-circle"></i> حفظ التعديلات',
        [
            'class' => 'btn btn-submit ' . ($model->isNewRecord ? 'btn-submit-create' : 'btn-submit-update'),
        ]
    ) ?>
</div>

<?php ActiveForm::end(); ?>
</div>
