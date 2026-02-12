<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 *  نموذج إنشاء / تعديل تسوية — يدعم شهري + أسبوعي + جدولة دين
 * ═══════════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;

/* @var $model backend\modules\loanScheduling\models\LoanScheduling */

$tpl = '{label}{input}{error}';
?>

<style>
.loan-modal-form .form-group{margin-bottom:18px}
.loan-modal-form .control-label{font-size:13px;font-weight:600;color:#555;margin-bottom:6px;display:block}
.loan-modal-form .control-label .fa{margin-left:5px;color:var(--fin-primary,#800020);font-size:12px}
.loan-modal-form .form-control{border-radius:6px;height:42px;font-size:14px;border:1.5px solid #ddd;transition:border-color .2s}
.loan-modal-form .form-control:focus{border-color:var(--fin-primary,#800020);box-shadow:0 0 0 3px rgba(128,0,32,.08)}
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

/* ── Settlement Type Toggle ── */
.ls-type-toggle{display:flex;gap:8px;margin-bottom:16px}
.ls-type-btn{flex:1;padding:12px 16px;border:2px solid #e2e8f0;border-radius:8px;text-align:center;cursor:pointer;transition:all .2s;background:#f8f9fa;font-weight:600;font-size:13px}
.ls-type-btn:hover{border-color:#800020;background:#fff}
.ls-type-btn.active{border-color:#800020;background:#800020;color:#fff}
.ls-type-btn i{display:block;font-size:20px;margin-bottom:4px}

/* ── Debt Schedule Preview ── */
.ls-schedule-preview{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin-top:12px}
.ls-schedule-row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f0f0f0;font-size:13px}
.ls-schedule-row:last-child{border-bottom:none}
.ls-schedule-row .label-text{color:#64748b}
.ls-schedule-row .value-text{font-weight:700;color:#1e293b}
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

<!-- ═══ نوع التسوية ═══ -->
<div class="loan-section-title">نوع التسوية</div>
<div class="ls-type-toggle">
    <div class="ls-type-btn <?= ($model->settlement_type ?: 'monthly') == 'monthly' ? 'active' : '' ?>" data-type="monthly" onclick="LoanForm.setType('monthly')">
        <i class="fa fa-calendar"></i> شهري
    </div>
    <div class="ls-type-btn <?= $model->settlement_type == 'weekly' ? 'active' : '' ?>" data-type="weekly" onclick="LoanForm.setType('weekly')">
        <i class="fa fa-calendar-o"></i> أسبوعي
    </div>
</div>
<?= $form->field($model, 'settlement_type')->hiddenInput(['id' => 'ls-settlement-type', 'value' => $model->settlement_type ?: 'monthly'])->label(false) ?>

<!-- ═══ تفاصيل التسوية ═══ -->
<div class="loan-section-title">تفاصيل التسوية</div>
<div class="row">
    <div class="col-sm-6">
        <?= $form->field($model, 'total_debt')->textInput([
            'type' => 'number',
            'step' => '0.01',
            'class' => 'form-control loan-amount-input',
            'placeholder' => '0.00',
            'id' => 'ls-total-debt',
            'oninput' => 'LoanForm.calculate()',
        ])->label('<i class="fa fa-calculator"></i> إجمالي الدين') ?>
    </div>
    <div class="col-sm-6">
        <?= $form->field($model, 'monthly_installment')->textInput([
            'type' => 'number',
            'step' => '0.01',
            'class' => 'form-control loan-amount-input',
            'placeholder' => '0.00',
            'id' => 'ls-installment',
            'oninput' => 'LoanForm.calculate()',
        ])->label('<i class="fa fa-money"></i> <span id="ls-installment-label">قيمة القسط</span>') ?>
    </div>
</div>
<div class="row">
    <div class="col-sm-6">
        <?= $form->field($model, 'first_installment_date')->widget(DatePicker::class, [
            'pluginOptions' => ['autoclose' => true, 'format' => 'yyyy-mm-dd', 'todayHighlight' => true],
            'options' => ['placeholder' => 'yyyy-mm-dd', 'class' => 'form-control'],
        ])->label('<i class="fa fa-calendar"></i> تاريخ أول قسط') ?>
    </div>
    <div class="col-sm-6">
        <?= $form->field($model, 'new_installment_date')->widget(DatePicker::class, [
            'pluginOptions' => ['autoclose' => true, 'format' => 'yyyy-mm-dd', 'todayHighlight' => true],
            'options' => ['placeholder' => 'yyyy-mm-dd', 'class' => 'form-control'],
        ])->label('<i class="fa fa-calendar-plus-o"></i> تاريخ القسط الجديد') ?>
    </div>
</div>

<!-- ═══ معاينة جدولة الدين ═══ -->
<div class="ls-schedule-preview" id="ls-schedule-box" style="display:none">
    <div class="loan-section-title" style="border-bottom:none;margin-bottom:8px">معاينة الجدولة</div>
    <div class="ls-schedule-row"><span class="label-text">إجمالي الدين</span><span class="value-text" id="ls-preview-debt">—</span></div>
    <div class="ls-schedule-row"><span class="label-text">قيمة القسط</span><span class="value-text" id="ls-preview-installment">—</span></div>
    <div class="ls-schedule-row"><span class="label-text">عدد الأقساط</span><span class="value-text" id="ls-preview-count">—</span></div>
    <div class="ls-schedule-row"><span class="label-text">آخر قسط (تقريبي)</span><span class="value-text" id="ls-preview-last-date">—</span></div>
    <div class="ls-schedule-row"><span class="label-text">المبلغ المتبقي بعد الأقساط</span><span class="value-text" id="ls-preview-remainder">—</span></div>
</div>

<?= $form->field($model, 'installments_count')->hiddenInput(['id' => 'ls-installments-count'])->label(false) ?>
<?= $form->field($model, 'remaining_debt')->hiddenInput(['id' => 'ls-remaining-debt'])->label(false) ?>

<!-- ═══ ملاحظات ═══ -->
<div class="row" style="margin-top:12px">
    <div class="col-xs-12">
        <?= $form->field($model, 'notes')->textarea([
            'rows' => 2,
            'placeholder' => 'ملاحظات إضافية (اختياري)...',
            'style' => 'height:auto',
        ])->label('<i class="fa fa-sticky-note-o"></i> ملاحظات') ?>
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

<script>
var LoanForm = (function(){
    function setType(type) {
        document.getElementById('ls-settlement-type').value = type;
        document.querySelectorAll('.ls-type-btn').forEach(function(btn){
            btn.classList.toggle('active', btn.getAttribute('data-type') === type);
        });
        var label = document.getElementById('ls-installment-label');
        label.textContent = type === 'weekly' ? 'القسط الأسبوعي' : 'القسط الشهري';
        calculate();
    }

    function calculate() {
        var debt = parseFloat(document.getElementById('ls-total-debt').value) || 0;
        var installment = parseFloat(document.getElementById('ls-installment').value) || 0;
        var box = document.getElementById('ls-schedule-box');

        if (debt > 0 && installment > 0) {
            var count = Math.ceil(debt / installment);
            var remainder = Math.round((debt - (count * installment)) * -100) / 100;
            var type = document.getElementById('ls-settlement-type').value;

            // حساب تاريخ آخر قسط
            var firstDate = document.querySelector('[name="LoanScheduling[first_installment_date]"]');
            var lastDate = '—';
            if (firstDate && firstDate.value) {
                var d = new Date(firstDate.value);
                if (type === 'weekly') {
                    d.setDate(d.getDate() + (count - 1) * 7);
                } else {
                    d.setMonth(d.getMonth() + (count - 1));
                }
                lastDate = d.toISOString().split('T')[0];
            }

            document.getElementById('ls-preview-debt').textContent = debt.toLocaleString('ar-JO') + ' د.أ';
            document.getElementById('ls-preview-installment').textContent = installment.toLocaleString('ar-JO') + ' د.أ ' + (type === 'weekly' ? '(أسبوعي)' : '(شهري)');
            document.getElementById('ls-preview-count').textContent = count + ' قسط';
            document.getElementById('ls-preview-last-date').textContent = lastDate;
            document.getElementById('ls-preview-remainder').textContent = remainder > 0 ? remainder.toLocaleString('ar-JO') + ' د.أ (زيادة)' : '0';

            document.getElementById('ls-installments-count').value = count;
            document.getElementById('ls-remaining-debt').value = Math.max(0, debt - (count * installment));

            box.style.display = 'block';
        } else {
            box.style.display = 'none';
        }
    }

    return { setType: setType, calculate: calculate };
})();
</script>
