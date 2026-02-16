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
/* كل استعلام محمي بـ try/catch حتى لا تنهار الصفحة بسبب فشل قائمة منسدلة واحدة */

if (!isset($grades)) {
    try {
        $grades = ArrayHelper::map(
            (new Query())->select(['id', 'name'])->from('{{%hr_grade}}')->where(['is_deleted' => 0])->all(),
            'id', 'name'
        );
    } catch (\Exception $e) {
        $grades = [];
        Yii::warning('فشل جلب الدرجات الوظيفية: ' . $e->getMessage(), __METHOD__);
    }
}

if (!isset($branches)) {
    try {
        $branches = ArrayHelper::map(
            (new Query())->select(['id', 'name'])->from('{{%location}}')->all(),
            'id', 'name'
        );
    } catch (\Exception $e) {
        $branches = [];
        Yii::warning('فشل جلب الفروع: ' . $e->getMessage(), __METHOD__);
    }
}

if (!isset($shifts)) {
    try {
        $shifts = ArrayHelper::map(
            (new Query())->select(['id', 'name'])->from('{{%hr_work_shift}}')->where(['is_deleted' => 0])->all(),
            'id', 'name'
        );
    } catch (\Exception $e) {
        $shifts = [];
        Yii::warning('فشل جلب الورديات: ' . $e->getMessage(), __METHOD__);
    }
}

if (!isset($cities)) {
    try {
        $cities = ArrayHelper::map(
            (new Query())->select(['id', 'name'])->from('{{%city}}')->all(),
            'id', 'name'
        );
    } catch (\Exception $e) {
        $cities = [];
        Yii::warning('فشل جلب المدن: ' . $e->getMessage(), __METHOD__);
    }
}

if (!isset($banks)) {
    try {
        $banks = ArrayHelper::map(
            (new Query())->select(['id', 'name'])->from('{{%bancks}}')->all(),
            'id', 'name'
        );
    } catch (\Exception $e) {
        $banks = [];
        Yii::warning('فشل جلب البنوك: ' . $e->getMessage(), __METHOD__);
    }
}

/* ─── خريطة أنواع العقود ─── */
$contractTypes = [
    'permanent' => 'دائم',
    'contract'  => 'مؤقت/عقد',
    'probation' => 'تحت التجربة',
    'freelance' => 'عمل حر',
];

/* ─── خريطة فصائل الدم ─── */
$bloodTypes = [
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
    'collector' => 'محصّل',
    'inspector' => 'مفتش',
    'driver'    => 'سائق',
    'messenger' => 'مراسل',
    'lawyer'    => 'محامي',
    'other'     => 'أخرى',
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

    <?php if ($model->hasErrors()): ?>
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

            <?php if ($isNewRecord): ?>
                <!-- Toggle: مستخدم موجود أو إنشاء جديد -->
                <input type="hidden" name="create_new_user" id="hr-create-new-user" value="0">

                <div class="hr-user-mode-toggle">
                    <button type="button" class="hr-mode-btn active" id="hr-mode-existing" data-mode="existing">
                        <i class="fa fa-user"></i> اختيار مستخدم موجود
                    </button>
                    <button type="button" class="hr-mode-btn" id="hr-mode-new" data-mode="new">
                        <i class="fa fa-user-plus"></i> إنشاء مستخدم جديد
                    </button>
                </div>

                <!-- Mode 1: Select existing user -->
                <div id="hr-existing-user-panel">
                    <?php if (!empty($userList)): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <?= $form->field($model, 'user_id')->widget(Select2::class, [
                                'data' => $userList,
                                'options' => ['placeholder' => 'اختر الموظف...', 'dir' => 'rtl', 'id' => 'hr-existing-user-select'],
                                'pluginOptions' => [
                                    'allowClear' => true,
                                    'dir' => 'rtl',
                                ],
                            ])->label('الموظف <span class="text-danger">*</span>') ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning" style="border-radius:10px;border:none;margin:12px 0">
                        <i class="fa fa-info-circle"></i>
                        لا يوجد مستخدمون بدون ملفات موظفين موسعة. استخدم "إنشاء مستخدم جديد" لإضافة موظف.
                    </div>
                    <?php endif ?>
                </div>

                <!-- Mode 2: Create new user inline -->
                <div id="hr-new-user-panel" style="display:none">
                    <div class="hr-new-user-card">
                        <div class="hr-new-user-card-header">
                            <i class="fa fa-user-plus"></i> بيانات المستخدم الجديد
                        </div>
                        <div class="hr-new-user-card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hr-form-label">الاسم الكامل <span class="text-danger">*</span></label>
                                        <input type="text" name="new_user_name" id="new_user_name" class="form-control hr-form-input"
                                               placeholder="مثال: أحمد محمد" dir="rtl">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hr-form-label">اسم المستخدم <span class="text-danger">*</span></label>
                                        <input type="text" name="new_user_username" id="new_user_username" class="form-control hr-form-input"
                                               placeholder="مثال: ahmed.m" dir="ltr" style="text-align:left">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hr-form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                                        <input type="email" name="new_user_email" id="new_user_email" class="form-control hr-form-input"
                                               placeholder="مثال: ahmed@company.com" dir="ltr" style="text-align:left">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hr-form-label">كلمة المرور <span class="text-danger">*</span></label>
                                        <input type="password" name="new_user_password" id="new_user_password" class="form-control hr-form-input"
                                               placeholder="6 أحرف على الأقل" dir="ltr" style="text-align:left">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="hr-form-label">رقم الجوال</label>
                                        <input type="text" name="new_user_mobile" id="new_user_mobile" class="form-control hr-form-input"
                                               placeholder="07XXXXXXXX" dir="ltr" style="text-align:left">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif ?>

            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'employee_code')->textInput([
                        'maxlength' => 20,
                        'placeholder' => 'مثال: EMP-0001',
                        'dir' => 'ltr',
                        'class' => 'form-control hr-form-input text-left',
                    ])->hint('اتركه فارغاً للتوليد التلقائي') ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'national_id')->textInput([
                        'maxlength' => 20,
                        'placeholder' => 'رقم الهوية الوطنية',
                        'dir' => 'ltr',
                        'class' => 'form-control hr-form-input text-left',
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <!-- placeholder for alignment -->
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
                    <?= $form->field($model, 'blood_type')->dropDownList($bloodTypes, [
                        'prompt' => '— اختر فصيلة الدم —',
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <!-- placeholder for alignment -->
                </div>
            </div>

            <div class="row">
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
                    <?= $form->field($model, 'bank_id')->widget(Select2::class, [
                        'data' => $banks,
                        'options' => ['placeholder' => 'اختر البنك...', 'dir' => 'rtl'],
                        'pluginOptions' => [
                            'allowClear' => true,
                            'dir' => 'rtl',
                        ],
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'bank_account_no')->textInput([
                        'maxlength' => 50,
                        'placeholder' => 'رقم الحساب البنكي',
                        'dir' => 'ltr',
                        'class' => 'form-control hr-form-input text-left',
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'social_security_no')->textInput([
                        'maxlength' => 50,
                        'placeholder' => 'رقم الضمان الاجتماعي',
                        'dir' => 'ltr',
                        'class' => 'form-control hr-form-input text-left',
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'tax_number')->textInput([
                        'maxlength' => 50,
                        'placeholder' => 'الرقم الضريبي',
                        'dir' => 'ltr',
                        'class' => 'form-control hr-form-input text-left',
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'city_id')->widget(Select2::class, [
                        'data' => $cities,
                        'options' => ['placeholder' => 'اختر المدينة...', 'dir' => 'rtl'],
                        'pluginOptions' => [
                            'allowClear' => true,
                            'dir' => 'rtl',
                        ],
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'address_text')->textarea([
                        'rows' => 2,
                        'placeholder' => 'العنوان التفصيلي',
                        'class' => 'form-control hr-form-textarea',
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'iban')->textInput([
                        'maxlength' => 34,
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
 *  JavaScript — Toggle field role visibility + New user mode
 * ═══════════════════════════════════════════════════════════════ */
$js = <<<'JS'
// Field staff toggle
$(document).on('change', '#is-field-staff-checkbox', function(){
    if ($(this).is(':checked')) {
        $('#field-role-wrapper').slideDown(200);
    } else {
        $('#field-role-wrapper').slideUp(200);
        $('#field-role-select').val('');
    }
});

// User creation mode toggle
$(document).on('click', '.hr-mode-btn', function(){
    var mode = $(this).data('mode');
    $('.hr-mode-btn').removeClass('active');
    $(this).addClass('active');

    if (mode === 'new') {
        $('#hr-existing-user-panel').slideUp(200);
        $('#hr-new-user-panel').slideDown(200);
        $('#hr-create-new-user').val('1');
        // Clear existing user selection
        if ($('#hr-existing-user-select').length) {
            $('#hr-existing-user-select').val('').trigger('change');
        }
    } else {
        $('#hr-new-user-panel').slideUp(200);
        $('#hr-existing-user-panel').slideDown(200);
        $('#hr-create-new-user').val('0');
        // Clear new user fields
        $('#new_user_name, #new_user_username, #new_user_email, #new_user_password, #new_user_mobile').val('');
    }
});

// Form validation before submit
$('#hr-employee-form').on('beforeSubmit', function(){
    var isNew = $('#hr-create-new-user').val() === '1';
    if (isNew) {
        var name = $('#new_user_name').val().trim();
        var username = $('#new_user_username').val().trim();
        var email = $('#new_user_email').val().trim();
        var pass = $('#new_user_password').val();
        var errors = [];
        if (!name) errors.push('الاسم الكامل مطلوب');
        if (!username) errors.push('اسم المستخدم مطلوب');
        if (!email) errors.push('البريد الإلكتروني مطلوب');
        if (pass.length < 6) errors.push('كلمة المرور يجب أن تكون 6 أحرف على الأقل');
        if (errors.length > 0) {
            alert(errors.join('\n'));
            return false;
        }
    }
    return true;
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

/* ─── User Mode Toggle ─── */
.hr-user-mode-toggle {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    padding: 4px;
    background: #f1f5f9;
    border-radius: 10px;
}
.hr-mode-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    border: 2px solid transparent;
    border-radius: 8px;
    background: transparent;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s;
}
.hr-mode-btn:hover {
    background: #fff;
    color: #334155;
}
.hr-mode-btn.active {
    background: #fff;
    color: #800020;
    border-color: #800020;
    box-shadow: 0 1px 4px rgba(128,0,32,0.12);
}
.hr-mode-btn i {
    font-size: 15px;
}

/* ─── New User Card ─── */
.hr-new-user-card {
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 8px;
    background: #fefce8;
}
.hr-new-user-card-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 18px;
    background: #fef9c3;
    border-bottom: 1px solid #fde68a;
    font-size: 14px;
    font-weight: 700;
    color: #92400e;
}
.hr-new-user-card-body {
    padding: 18px;
}

CSS;

$this->registerCss($css);
?>
