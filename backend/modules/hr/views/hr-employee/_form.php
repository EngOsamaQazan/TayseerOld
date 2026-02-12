<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  نموذج بيانات الموظف الموسعة — إنشاء / تعديل
 *  ──────────────────────────────────────
 *  بيانات أساسية | مالية | وظيفية | ميدانية | ملاحظات
 * ═══════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use yii\db\Query;
use kartik\date\DatePicker;
use kartik\select2\Select2;

/** @var yii\web\View $this */
/** @var backend\modules\hr\models\HrEmployeeExtended $model */
/** @var array|null $userList */

/* ─── تسجيل CSS ─── */
$this->registerCssFile(Yii::getAlias('@web') . '/css/hr.css', ['depends' => ['yii\web\YiiAsset']]);

/* ─── جلب بيانات القوائم المنسدلة إن لم تمرر من المتحكم ─── */
if (!isset($grades)) {
    $grades = ArrayHelper::map(
        (new Query())->select(['id', 'name'])->from('{{%hr_grade}}')->where(['is_deleted' => 0])->all(),
        'id', 'name'
    );
}

if (!isset($branches)) {
    $branches = ArrayHelper::map(
        (new Query())->select(['id', 'name'])->from('{{%location}}')->all(),
        'id', 'name'
    );
}

if (!isset($shifts)) {
    $shifts = ArrayHelper::map(
        (new Query())->select(['id', 'name'])->from('{{%hr_work_shift}}')->where(['is_deleted' => 0])->all(),
        'id', 'name'
    );
}

if (!isset($cities)) {
    $cities = ArrayHelper::map(
        (new Query())->select(['id', 'name'])->from('{{%city}}')->all(),
        'id', 'name'
    );
}

if (!isset($banks)) {
    $banks = ArrayHelper::map(
        (new Query())->select(['id', 'name'])->from('{{%bank}}')->all(),
        'id', 'name'
    );
}

/* ─── خريطة أنواع العقود ─── */
$contractTypes = [
    'permanent'  => 'دائم',
    'temporary'  => 'مؤقت',
    'seasonal'   => 'موسمي',
    'part_time'  => 'دوام جزئي',
    'freelance'  => 'عمل حر',
];

/* ─── خريطة فصائل الدم ─── */
$bloodGroups = [
    'A+'  => 'A+',
    'A-'  => 'A-',
    'B+'  => 'B+',
    'B-'  => 'B-',
    'AB+' => 'AB+',
    'AB-' => 'AB-',
    'O+'  => 'O+',
    'O-'  => 'O-',
];

/* ─── خريطة الأدوار الميدانية ─── */
$fieldRoles = [
    'driver'      => 'سائق',
    'technician'  => 'فني',
    'supervisor'  => 'مشرف ميداني',
    'sales_rep'   => 'مندوب مبيعات',
    'collector'   => 'محصّل',
    'inspector'   => 'مفتش',
    'other'       => 'أخرى',
];

$isNewRecord = $model->isNewRecord;
?>

<div class="hr-employee-form">

    <?php $form = ActiveForm::begin([
        'id' => 'hr-employee-form',
        'options' => ['class' => 'hr-form'],
        'enableClientValidation' => true,
        'enableAjaxValidation' => false,
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{hint}\n{error}",
            'labelOptions' => ['class' => 'hr-form-label'],
            'inputOptions' => ['class' => 'form-control hr-form-input'],
            'errorOptions' => ['class' => 'help-block hr-form-error'],
        ],
    ]); ?>

    <?php if ($form->errorSummary($model) !== ''): ?>
        <div class="alert alert-danger hr-alert">
            <i class="fa fa-exclamation-triangle"></i>
            <?= $form->errorSummary($model) ?>
        </div>
    <?php endif ?>

    <!-- ═════════════════════════════════════════════════
         القسم 1: بيانات أساسية
         ═════════════════════════════════════════════════ -->
    <div class="hr-form-section">
        <div class="hr-form-section-header">
            <i class="fa fa-user"></i>
            <span>بيانات أساسية</span>
        </div>
        <div class="hr-form-section-body">

            <?php if ($isNewRecord && !empty($userList)): ?>
                <div class="row">
                    <div class="col-md-12">
                        <?= $form->field($model, 'user_id')->widget(Select2::class, [
                            'data' => $userList,
                            'options' => ['placeholder' => 'اختر الموظف...', 'dir' => 'rtl'],
                            'pluginOptions' => [
                                'allowClear' => true,
                                'dir' => 'rtl',
                            ],
                        ])->label('الموظف <span class="text-danger">*</span>') ?>
                    </div>
                </div>
            <?php elseif ($isNewRecord): ?>
                <?= $form->field($model, 'user_id')->hiddenInput()->label(false) ?>
            <?php endif ?>

            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'employee_code')->textInput([
                        'maxlength' => 50,
                        'placeholder' => 'مثال: EMP-0001',
                        'dir' => 'ltr',
                        'class' => 'form-control hr-form-input text-left',
                    ])->hint('اتركه فارغاً للتوليد التلقائي') ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'national_id')->textInput([
                        'maxlength' => 50,
                        'placeholder' => 'رقم الهوية الوطنية',
                        'dir' => 'ltr',
                        'class' => 'form-control hr-form-input text-left',
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'national_id_expiry')->widget(DatePicker::class, [
                        'options' => ['placeholder' => 'تاريخ انتهاء الهوية', 'dir' => 'ltr', 'autocomplete' => 'off'],
                        'type' => DatePicker::TYPE_INPUT,
                        'pluginOptions' => [
                            'format' => 'yyyy-mm-dd',
                            'autoclose' => true,
                            'todayHighlight' => true,
                            'rtl' => true,
                        ],
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'date_of_birth')->widget(DatePicker::class, [
                        'options' => ['placeholder' => 'تاريخ الميلاد', 'dir' => 'ltr', 'autocomplete' => 'off'],
                        'type' => DatePicker::TYPE_INPUT,
                        'pluginOptions' => [
                            'format' => 'yyyy-mm-dd',
                            'autoclose' => true,
                            'todayHighlight' => true,
                            'rtl' => true,
                        ],
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'blood_group')->dropDownList($bloodGroups, [
                        'prompt' => '— اختر فصيلة الدم —',
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'passport_number')->textInput([
                        'maxlength' => 50,
                        'placeholder' => 'رقم جواز السفر',
                        'dir' => 'ltr',
                        'class' => 'form-control hr-form-input text-left',
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'passport_expiry')->widget(DatePicker::class, [
                        'options' => ['placeholder' => 'تاريخ انتهاء الجواز', 'dir' => 'ltr', 'autocomplete' => 'off'],
                        'type' => DatePicker::TYPE_INPUT,
                        'pluginOptions' => [
                            'format' => 'yyyy-mm-dd',
                            'autoclose' => true,
                            'todayHighlight' => true,
                            'rtl' => true,
                        ],
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'contract_type')->dropDownList($contractTypes, [
                        'prompt' => '— اختر نوع العقد —',
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'contract_start')->widget(DatePicker::class, [
                        'options' => ['placeholder' => 'بداية العقد', 'dir' => 'ltr', 'autocomplete' => 'off'],
                        'type' => DatePicker::TYPE_INPUT,
                        'pluginOptions' => [
                            'format' => 'yyyy-mm-dd',
                            'autoclose' => true,
                            'todayHighlight' => true,
                            'rtl' => true,
                        ],
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'contract_end')->widget(DatePicker::class, [
                        'options' => ['placeholder' => 'نهاية العقد', 'dir' => 'ltr', 'autocomplete' => 'off'],
                        'type' => DatePicker::TYPE_INPUT,
                        'pluginOptions' => [
                            'format' => 'yyyy-mm-dd',
                            'autoclose' => true,
                            'todayHighlight' => true,
                            'rtl' => true,
                        ],
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'probation_end')->widget(DatePicker::class, [
                        'options' => ['placeholder' => 'نهاية فترة التجربة', 'dir' => 'ltr', 'autocomplete' => 'off'],
                        'type' => DatePicker::TYPE_INPUT,
                        'pluginOptions' => [
                            'format' => 'yyyy-mm-dd',
                            'autoclose' => true,
                            'todayHighlight' => true,
                            'rtl' => true,
                        ],
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <!-- placeholder for alignment -->
                </div>
            </div>
        </div>
    </div>

    <!-- ═════════════════════════════════════════════════
         القسم 2: بيانات مالية
         ═════════════════════════════════════════════════ -->
    <div class="hr-form-section">
        <div class="hr-form-section-header">
            <i class="fa fa-university"></i>
            <span>بيانات مالية</span>
        </div>
        <div class="hr-form-section-body">
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'basic_salary')->textInput([
                        'type' => 'number',
                        'step' => '0.01',
                        'min' => '0',
                        'placeholder' => '0.00',
                        'dir' => 'ltr',
                        'class' => 'form-control hr-form-input text-left',
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'salary_currency')->dropDownList([
                        'SAR' => 'ريال سعودي (ر.س)',
                        'AED' => 'درهم إماراتي',
                        'KWD' => 'دينار كويتي',
                        'QAR' => 'ريال قطري',
                        'BHD' => 'دينار بحريني',
                        'OMR' => 'ريال عماني',
                        'EGP' => 'جنيه مصري',
                        'JOD' => 'دينار أردني',
                        'USD' => 'دولار أمريكي',
                    ], [
                        'prompt' => '— اختر العملة —',
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'bank_name')->textInput([
                        'maxlength' => 100,
                        'placeholder' => 'اسم البنك',
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'iban')->textInput([
                        'maxlength' => 100,
                        'placeholder' => 'مثال: SA0000000000000000000000',
                        'dir' => 'ltr',
                        'class' => 'form-control hr-form-input text-left',
                        'style' => 'letter-spacing:1px;font-family:monospace',
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <!-- مساحة إضافية ─ يمكن إضافة حقول مستقبلية -->
                </div>
            </div>
        </div>
    </div>

    <!-- ═════════════════════════════════════════════════
         القسم 3: بيانات وظيفية
         ═════════════════════════════════════════════════ -->
    <div class="hr-form-section">
        <div class="hr-form-section-header">
            <i class="fa fa-briefcase"></i>
            <span>بيانات وظيفية</span>
        </div>
        <div class="hr-form-section-body">
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'grade_id')->widget(Select2::class, [
                        'data' => $grades,
                        'options' => ['placeholder' => 'اختر الدرجة الوظيفية...', 'dir' => 'rtl'],
                        'pluginOptions' => [
                            'allowClear' => true,
                            'dir' => 'rtl',
                        ],
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'branch_id')->widget(Select2::class, [
                        'data' => $branches,
                        'options' => ['placeholder' => 'اختر الفرع...', 'dir' => 'rtl'],
                        'pluginOptions' => [
                            'allowClear' => true,
                            'dir' => 'rtl',
                        ],
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'shift_id')->widget(Select2::class, [
                        'data' => $shifts,
                        'options' => ['placeholder' => 'اختر الوردية...', 'dir' => 'rtl'],
                        'pluginOptions' => [
                            'allowClear' => true,
                            'dir' => 'rtl',
                        ],
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═════════════════════════════════════════════════
         القسم 4: بيانات ميدانية
         ═════════════════════════════════════════════════ -->
    <div class="hr-form-section">
        <div class="hr-form-section-header">
            <i class="fa fa-map-marker"></i>
            <span>بيانات ميدانية</span>
        </div>
        <div class="hr-form-section-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="hr-form-label">موظف ميداني</label>
                        <div class="hr-checkbox-wrap">
                            <?= Html::activeCheckbox($model, 'is_field_staff', [
                                'id' => 'is-field-staff-checkbox',
                                'label' => 'هذا الموظف يعمل في الميدان',
                                'class' => 'hr-checkbox',
                            ]) ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" id="field-role-wrapper" style="<?= $model->is_field_staff ? '' : 'display:none' ?>">
                    <div class="form-group">
                        <label class="hr-form-label">الدور الميداني</label>
                        <?= Html::activeDropDownList($model, 'field_role', $fieldRoles, [
                            'class' => 'form-control hr-form-input',
                            'prompt' => '— اختر الدور —',
                            'id' => 'field-role-select',
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═════════════════════════════════════════════════
         القسم 5: ملاحظات
         ═════════════════════════════════════════════════ -->
    <div class="hr-form-section">
        <div class="hr-form-section-header">
            <i class="fa fa-sticky-note-o"></i>
            <span>ملاحظات</span>
        </div>
        <div class="hr-form-section-body">
            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'notes')->textarea([
                        'rows' => 4,
                        'placeholder' => 'أي ملاحظات إضافية حول الموظف...',
                        'class' => 'form-control hr-form-textarea',
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═════════════════════════════════════════════════
         أزرار الإرسال
         ═════════════════════════════════════════════════ -->
    <div class="hr-form-actions">
        <?= Html::submitButton(
            $isNewRecord
                ? '<i class="fa fa-plus-circle"></i> إنشاء ملف الموظف'
                : '<i class="fa fa-check-circle"></i> حفظ التعديلات',
            [
                'class' => 'btn hr-btn-primary hr-btn-lg',
                'id' => 'btn-submit-form',
            ]
        ) ?>
        <?= Html::a(
            '<i class="fa fa-times"></i> إلغاء',
            ['index'],
            ['class' => 'btn btn-default hr-btn-lg']
        ) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div><!-- /.hr-employee-form -->


<?php
/* ═══════════════════════════════════════════════════════════════
 *  JavaScript — Toggle field role visibility
 * ═══════════════════════════════════════════════════════════════ */
$js = <<<JS
$(document).on('change', '#is-field-staff-checkbox', function(){
    if ($(this).is(':checked')) {
        $('#field-role-wrapper').slideDown(200);
    } else {
        $('#field-role-wrapper').slideUp(200);
        $('#field-role-select').val('');
    }
});
JS;
$this->registerJs($js, \yii\web\View::POS_READY);

/* ═══════════════════════════════════════════════════════════════
 *  CSS
 * ═══════════════════════════════════════════════════════════════ */
$css = <<<CSS

/* ─── Form Sections ─── */
.hr-form-section {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 20px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.hr-form-section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
}
.hr-form-section-header i {
    color: #800020;
    font-size: 16px;
    width: 20px;
    text-align: center;
}
.hr-form-section-body {
    padding: 20px;
}

/* ─── Form Fields ─── */
.hr-form-label {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}
.hr-form-input {
    border-radius: 8px !important;
    border: 1px solid #d1d5db !important;
    font-size: 13px !important;
    padding: 8px 12px !important;
    height: auto !important;
    min-height: 38px;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.hr-form-input:focus {
    border-color: #800020 !important;
    box-shadow: 0 0 0 3px rgba(128,0,32,0.08) !important;
}
.hr-form-textarea {
    border-radius: 8px !important;
    border: 1px solid #d1d5db !important;
    font-size: 13px !important;
    padding: 10px 14px !important;
    resize: vertical;
    min-height: 80px;
}
.hr-form-textarea:focus {
    border-color: #800020 !important;
    box-shadow: 0 0 0 3px rgba(128,0,32,0.08) !important;
}
.hr-form-error {
    font-size: 12px;
    color: #dc2626;
    margin-top: 4px;
}
.hr-form .form-group {
    margin-bottom: 16px;
}
.hr-form .hint-block {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 4px;
}

/* ─── Checkbox ─── */
.hr-checkbox-wrap {
    padding: 10px 0;
}
.hr-checkbox-wrap label {
    font-size: 13px;
    font-weight: 500;
    color: #475569;
    cursor: pointer;
}

/* ─── Select2 overrides for RTL ─── */
.select2-container .select2-selection--single {
    border-radius: 8px !important;
    border: 1px solid #d1d5db !important;
    height: 38px !important;
    padding: 4px 12px !important;
}
.select2-container--focus .select2-selection--single,
.select2-container--open .select2-selection--single {
    border-color: #800020 !important;
    box-shadow: 0 0 0 3px rgba(128,0,32,0.08) !important;
}

/* ─── DatePicker overrides ─── */
.kv-date-picker {
    border-radius: 8px !important;
}

/* ─── Form Actions ─── */
.hr-form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-start;
    padding: 16px 0;
    margin-top: 8px;
}
.hr-btn-lg {
    padding: 10px 24px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 10px;
}

/* ─── Alert ─── */
.hr-alert {
    border-radius: 10px;
    border: none;
    font-size: 13px;
}
.hr-alert i {
    margin-left: 6px;
}

/* ─── Text alignment for LTR fields in RTL ─── */
.text-left {
    text-align: left !important;
    direction: ltr !important;
}

CSS;

$this->registerCss($css);
?>
