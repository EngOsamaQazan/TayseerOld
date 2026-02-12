<?php
/**
 * Smart Customer Onboarding Form — نموذج العميل الذكي
 * Split layout: Form (8/12) + Risk Panel (4/12)
 * Mobile: Wizard + Bottom Risk Summary
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use kartik\select2\Select2;
use borales\extensions\phoneInput\PhoneInput;
use backend\widgets\ImageManagerInputWidget;

/* Register assets */
$this->registerCssFile('@web/css/smart-onboarding.css', ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerCssFile('@web/css/smart-media.css', ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerJsFile('@web/js/smart-onboarding.js', ['depends' => [\yii\web\JqueryAsset::class], 'position' => \yii\web\View::POS_END]);
$this->registerJsFile('@web/js/smart-media.js', ['depends' => [\yii\web\JqueryAsset::class], 'position' => \yii\web\View::POS_END]);

/* Smart Media URLs for JS */
$this->registerJs("window.smConfig = " . json_encode([
    'uploadUrl'      => Url::to(['/customers/smart-media/upload']),
    'webcamUrl'      => Url::to(['/customers/smart-media/webcam-capture']),
    'classifyUrl'    => Url::to(['/customers/smart-media/classify']),
    'usageUrl'       => Url::to(['/customers/smart-media/usage-stats']),
    'googleStatsUrl' => Url::to(['/customers/smart-media/google-stats']),
    'deleteUrl'      => Url::to(['/customers/smart-media/delete']),
]) . ";", \yii\web\View::POS_HEAD);

/* Hide AdminLTE content header */
$this->registerCss('.content-header { display: none !important; } .content-wrapper { padding-top: 0 !important; } .content { padding: 0 !important; }');

/* Config for JS */
$this->registerJs("window.soConfig = " . json_encode([
    'riskCalcUrl'      => Url::to(['calculate-risk']),
    'duplicateCheckUrl' => Url::to(['check-duplicate']),
    'customerViewUrl'   => Url::to(['view', 'id' => '__ID__']),
]) . ";", \yii\web\View::POS_HEAD);

/* Cache lookups */
$cache = Yii::$app->cache;
$p = Yii::$app->params;
$d = $p['time_duration'];
$db = Yii::$app->db;

$jobs = $cache->getOrSet($p['key_jobs'], fn() => $db->createCommand($p['jobs_query'])->queryAll(), $d);
$city = $cache->getOrSet($p['key_city'], fn() => $db->createCommand($p['city_query'])->queryAll(), $d);
$citizen = $cache->getOrSet($p['key_citizen'], fn() => $db->createCommand($p['citizen_query'])->queryAll(), $d);
$hearAboutUs = $cache->getOrSet($p['key_hear_about_us'], fn() => $db->createCommand($p['hear_about_us_query'])->queryAll(), $d);
$banks = $cache->getOrSet($p['key_banks'], fn() => $db->createCommand($p['banks_query'])->queryAll(), $d);
$cousins = ArrayHelper::map(
    \backend\modules\cousins\models\Cousins::find()->asArray()->all(),
    'id', 'name'
);

$isNew = $model->isNewRecord;
$imgRandId = rand(100000000, 1000000000);
if (empty($model->image_manager_id)) $model->image_manager_id = $imgRandId;

/* Previous contracts data */
$prevContracts = 0;
$hasDefaults = false;
if (!$isNew) {
    try {
        $prevContracts = (int)$db->createCommand("SELECT COUNT(*) FROM os_contracts_customers WHERE customer_id=:cid", [':cid' => $model->id])->queryScalar();
        if ($prevContracts > 0) {
            $hasDefaults = (int)$db->createCommand("SELECT COUNT(*) FROM os_contracts_customers cc JOIN os_contracts c ON c.id=cc.contract_id WHERE cc.customer_id=:cid AND c.status IN ('judiciary','canceled')", [':cid' => $model->id])->queryScalar() > 0;
        }
    } catch (\Exception $e) {}
}

/* Employment types */
$employmentTypes = [
    'government'    => 'حكومي',
    'military'      => 'عسكري',
    'private'       => 'قطاع خاص',
    'self_employed' => 'عمل حر',
    'retired'       => 'متقاعد',
    'unemployed'    => 'بدون عمل',
    'other'         => 'أخرى',
];

/* Customer financials (if exists) */
$financials = null;
if (!$isNew) {
    try {
        $financials = $db->createCommand("SELECT * FROM os_customer_financials WHERE customer_id=:cid", [':cid' => $model->id])->queryOne();
    } catch (\Exception $e) {}
}
?>

<div class="so-page">
    <!-- Header -->
    <div class="so-header">
        <h1><i class="fa fa-user-plus"></i> <?= $isNew ? 'إضافة عميل جديد' : 'تعديل بيانات العميل' ?></h1>
        <div class="so-header-actions">
            <a href="<?= Url::to(['index']) ?>" class="so-back-btn"><i class="fa fa-arrow-right"></i> العودة للقائمة</a>
        </div>
    </div>

    <div class="so-body">
        <!-- ═══════════════════════════════════════════
             LEFT: FORM AREA
             ═══════════════════════════════════════════ -->
        <div class="so-form-area">
            <?php
            $formConfig = [
                'options' => [
                    'enctype' => 'multipart/form-data',
                    'data-prev-contracts' => $prevContracts,
                    'data-has-defaults' => $hasDefaults ? '1' : '0',
                ],
                'id' => 'smart-onboarding-form',
            ];
            if (isset($id)) $formConfig['action'] = Url::to(['update', 'id' => $id]);
            $form = ActiveForm::begin($formConfig);
            ?>
            <?= $form->errorSummary($model, ['class' => 'alert alert-danger', 'style' => 'border-radius:8px; font-size:13px']) ?>

            <!-- Wizard Steps -->
            <div class="so-steps">
                <div class="so-step active" data-step="0">
                    <span class="so-step-num">1</span>
                    <span class="so-step-label">البيانات الشخصية</span>
                </div>
                <div class="so-step" data-step="1">
                    <span class="so-step-num">2</span>
                    <span class="so-step-label">الوظيفة والدخل</span>
                </div>
                <div class="so-step" data-step="2">
                    <span class="so-step-num">3</span>
                    <span class="so-step-label">البنك والضمانات</span>
                </div>
                <div class="so-step" data-step="3">
                    <span class="so-step-num">4</span>
                    <span class="so-step-label">المعرّفون والمستندات</span>
                </div>
                <div class="so-step" data-step="4">
                    <span class="so-step-num">5</span>
                    <span class="so-step-label">الصور والمراجعة</span>
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 STEP 1: البيانات الشخصية
                 ══════════════════════════════════════ -->
            <div class="so-section active" data-step="0">
                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-user"></i> البيانات الشخصية</h3>
                    <div class="so-grid so-grid-3">
                        <div><?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'الاسم الرباعي', 'required' => true])->label('اسم العميل') ?></div>
                        <div><?= $form->field($model, 'id_number')->textInput(['maxlength' => true, 'placeholder' => 'الرقم الوطني', 'required' => true])->label('الرقم الوطني') ?></div>
                        <div><?= $form->field($model, 'sex')->dropDownList([0 => 'ذكر', 1 => 'أنثى'])->label('الجنس') ?></div>
                    </div>
                    <div class="so-grid so-grid-3" style="margin-top: 16px">
                        <div><?= $form->field($model, 'birth_date')->widget(DatePicker::class, [
                            'options' => ['placeholder' => 'YYYY-MM-DD', 'required' => true],
                            'pluginOptions' => ['autoclose' => true, 'format' => 'yyyy-mm-dd'],
                        ])->label('تاريخ الميلاد') ?></div>
                        <div><?= $form->field($model, 'city')->dropDownList(ArrayHelper::map($city, 'id', 'name'), ['prompt' => '-- المدينة --'])->label('مدينة الولادة') ?></div>
                        <div><?= $form->field($model, 'citizen')->dropDownList(ArrayHelper::map($citizen, 'id', 'name'), ['prompt' => '-- الجنسية --'])->label('الجنسية') ?></div>
                    </div>
                </div>

                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-phone"></i> بيانات التواصل</h3>
                    <div class="so-grid so-grid-3">
                        <div><?= $form->field($model, 'primary_phone_number')->widget(PhoneInput::class, [
                            'jsOptions' => ['preferredCountries' => ['jo']],
                        ])->label('الهاتف الرئيسي') ?></div>
                        <div><?= $form->field($model, 'email')->textInput(['type' => 'email', 'placeholder' => 'example@email.com'])->label('البريد الإلكتروني') ?></div>
                        <div><?= $form->field($model, 'hear_about_us')->dropDownList(ArrayHelper::map($hearAboutUs, 'id', 'name'), ['prompt' => '-- كيف سمعت عنا --'])->label('كيف سمعت عنا') ?></div>
                    </div>
                </div>

                <div class="so-nav">
                    <span></span>
                    <button type="button" class="so-btn so-btn-primary so-next-btn"><span>الخطوة التالية</span> <i class="fa fa-arrow-left"></i></button>
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 STEP 2: الوظيفة والدخل
                 ══════════════════════════════════════ -->
            <div class="so-section" data-step="1">
                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-briefcase"></i> المعلومات المهنية</h3>
                    <div class="so-grid so-grid-3">
                        <div><?= $form->field($model, 'job_title')->widget(Select2::class, [
                            'data' => ArrayHelper::map($jobs, 'id', 'name'),
                            'options' => ['placeholder' => 'اختر جهة العمل'],
                            'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                        ])->label('جهة العمل') ?></div>
                        <div>
                            <div class="form-group">
                                <label for="fin-employer-name">المسمى الوظيفي</label>
                                <input type="text" id="fin-employer-name" name="CustomerFinancials[employer_name]" class="form-control" value="<?= Html::encode($financials['employer_name'] ?? '') ?>" placeholder="مثال: محاسب، سائق، مهندس">
                            </div>
                        </div>
                        <div>
                            <div class="form-group">
                                <label for="fin-employment-type">نوع التوظيف</label>
                                <select id="fin-employment-type" name="CustomerFinancials[employment_type]" class="form-control">
                                    <option value="">-- اختر --</option>
                                    <?php foreach ($employmentTypes as $k => $v): ?>
                                        <option value="<?= $k ?>" <?= ($financials['employment_type'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="so-grid so-grid-3" style="margin-top: 16px">
                        <div><?= $form->field($model, 'job_number')->textInput(['maxlength' => true, 'placeholder' => 'الرقم الوظيفي'])->label('الرقم الوظيفي') ?></div>
                        <div>
                            <div class="form-group">
                                <label for="fin-years-at-job">سنوات الخدمة</label>
                                <input type="number" id="fin-years-at-job" name="CustomerFinancials[years_at_current_job]" class="form-control" value="<?= $financials['years_at_current_job'] ?? '' ?>" step="0.5" min="0" placeholder="0">
                            </div>
                        </div>
                        <div><?= $form->field($model, 'last_job_query_date')->widget(DatePicker::class, [
                            'options' => ['placeholder' => 'آخر استعلام'],
                            'pluginOptions' => ['autoclose' => true, 'format' => 'yyyy-mm-dd'],
                        ])->label('آخر استعلام وظيفي') ?></div>
                    </div>
                </div>

                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-money"></i> الدخل والالتزامات</h3>
                    <div class="so-grid so-grid-4">
                        <div><?= $form->field($model, 'total_salary')->textInput(['type' => 'number', 'step' => '0.01', 'placeholder' => '0.00'])->label('الراتب الأساسي (شهري)') ?></div>
                        <div>
                            <div class="form-group">
                                <label for="fin-additional-income">دخل إضافي (شهري)</label>
                                <input type="number" id="fin-additional-income" name="CustomerFinancials[additional_income]" class="form-control" value="<?= $financials['additional_income'] ?? '0' ?>" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                        <div>
                            <div class="form-group">
                                <label for="fin-obligations">الالتزامات الشهرية</label>
                                <input type="number" id="fin-obligations" name="CustomerFinancials[monthly_obligations]" class="form-control" value="<?= $financials['monthly_obligations'] ?? '0' ?>" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                        <div>
                            <div class="form-group">
                                <label for="fin-dependents">عدد المعالين</label>
                                <input type="number" id="fin-dependents" name="CustomerFinancials[dependents_count]" class="form-control" value="<?= $financials['dependents_count'] ?? '0' ?>" min="0" placeholder="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="so-nav">
                    <button type="button" class="so-btn so-btn-outline so-prev-btn"><i class="fa fa-arrow-right"></i> <span>السابق</span></button>
                    <button type="button" class="so-btn so-btn-primary so-next-btn"><span>الخطوة التالية</span> <i class="fa fa-arrow-left"></i></button>
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 STEP 3: البنك والضمانات
                 ══════════════════════════════════════ -->
            <div class="so-section" data-step="2">
                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-university"></i> الحساب البنكي</h3>
                    <div class="so-grid so-grid-3">
                        <div><?= $form->field($model, 'bank_name')->widget(Select2::class, [
                            'data' => ArrayHelper::map($banks, 'id', 'name'),
                            'options' => ['placeholder' => 'اختر البنك'],
                            'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                        ])->label('البنك') ?></div>
                        <div><?= $form->field($model, 'bank_branch')->textInput(['maxlength' => true, 'placeholder' => 'اسم الفرع'])->label('الفرع') ?></div>
                        <div><?= $form->field($model, 'account_number')->textInput(['maxlength' => true, 'placeholder' => 'رقم الحساب'])->label('رقم الحساب') ?></div>
                    </div>
                    <div class="so-grid so-grid-2" style="margin-top: 16px">
                        <div><?= $form->field($model, 'facebook_account')->textInput(['maxlength' => true, 'placeholder' => 'حساب فيسبوك'])->label('فيسبوك') ?></div>
                    </div>
                </div>

                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-shield"></i> الضمان والتقاعد</h3>
                    <div class="so-grid so-grid-3">
                        <div><?= $form->field($model, 'is_social_security')->dropDownList([0 => 'لا', 1 => 'نعم'], ['prompt' => '--'])->label('مشترك بالضمان؟') ?></div>
                        <div class="js-social-field" style="display:<?= (!$isNew && $model->is_social_security == 1) ? 'block' : 'none' ?>">
                            <?= $form->field($model, 'social_security_number')->textInput(['placeholder' => 'رقم الضمان'])->label('رقم الضمان') ?>
                        </div>
                        <div><?= $form->field($model, 'has_social_security_salary')->dropDownList(['yes' => 'نعم', 'no' => 'لا'], ['prompt' => '--'])->label('راتب ضمان؟') ?></div>
                    </div>
                    <div class="so-grid so-grid-3" style="margin-top: 16px">
                        <div><?= $form->field($model, 'social_security_salary_source')->dropDownList(Yii::$app->params['socialSecuritySources'] ?? [], ['prompt' => '-- المصدر --'])->label('مصدر الراتب') ?></div>
                        <div><?= $form->field($model, 'retirement_status')->dropDownList(['effective' => 'فعّال', 'stopped' => 'متوقف'], ['prompt' => '--'])->label('حالة التقاعد') ?></div>
                        <div><?= $form->field($model, 'total_retirement_income')->textInput(['type' => 'number', 'step' => '0.01', 'placeholder' => '0.00'])->label('دخل التقاعد') ?></div>
                    </div>
                    <div class="so-grid so-grid-2" style="margin-top: 16px">
                        <div><?= $form->field($model, 'last_income_query_date')->widget(DatePicker::class, [
                            'options' => ['placeholder' => 'آخر استعلام دخل'],
                            'pluginOptions' => ['autoclose' => true, 'format' => 'yyyy-mm-dd'],
                        ])->label('آخر استعلام دخل') ?></div>
                        <div><?= $form->field($model, 'do_have_any_property')->dropDownList([0 => 'لا', 1 => 'نعم'], ['prompt' => '--'])->label('يملك عقارات؟') ?></div>
                    </div>
                </div>

                <!-- Real Estate (conditional) -->
                <div class="js-real-estate-section" style="display:<?= (!$isNew && $model->do_have_any_property == 1) ? 'block' : 'none' ?>">
                    <div class="so-fieldset">
                        <h3 class="so-fieldset-title"><i class="fa fa-building"></i> العقارات</h3>
                        <?= $this->render('partial/real_estate', ['form' => $form, 'modelRealEstate' => $modelRealEstate]) ?>
                    </div>
                </div>

                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-sticky-note"></i> ملاحظات</h3>
                    <?= $form->field($model, 'notes')->textarea(['rows' => 2, 'maxlength' => true, 'placeholder' => 'ملاحظات إضافية'])->label(false) ?>
                </div>

                <div class="so-nav">
                    <button type="button" class="so-btn so-btn-outline so-prev-btn"><i class="fa fa-arrow-right"></i> <span>السابق</span></button>
                    <button type="button" class="so-btn so-btn-primary so-next-btn"><span>الخطوة التالية</span> <i class="fa fa-arrow-left"></i></button>
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 STEP 4: المعرّفون والمستندات
                 ══════════════════════════════════════ -->
            <div class="so-section" data-step="3">
                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-map-marker"></i> العناوين</h3>
                    <?= $this->render('partial/address', ['form' => $form, 'modelsAddress' => $modelsAddress]) ?>
                </div>

                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-address-book"></i> المعرّفون</h3>
                    <?= $this->render('partial/phone_numbers', ['form' => $form, 'modelsPhoneNumbers' => $modelsPhoneNumbers, 'cousins' => $cousins]) ?>
                </div>

                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-file-o"></i> المستندات</h3>
                    <?= $this->render('partial/customer_documents', ['form' => $form, 'customerDocumentsModel' => $customerDocumentsModel]) ?>
                </div>

                <div class="so-nav">
                    <button type="button" class="so-btn so-btn-outline so-prev-btn"><i class="fa fa-arrow-right"></i> <span>السابق</span></button>
                    <button type="button" class="so-btn so-btn-primary so-next-btn"><span>الخطوة التالية</span> <i class="fa fa-arrow-left"></i></button>
                </div>
            </div>

            <!-- ══════════════════════════════════════
                 STEP 5: الصور والمراجعة النهائية
                 ══════════════════════════════════════ -->
            <div class="so-section" data-step="4">
                <div class="so-fieldset">
                    <h3 class="so-fieldset-title"><i class="fa fa-image"></i> الصور والمستندات الذكية</h3>

                    <!-- Hidden fields for backward compatibility -->
                    <?= $form->field($model, 'selected_image')->hiddenInput()->label(false) ?>
                    <?= $form->field($model, 'image_manager_id')->hiddenInput()->label(false) ?>
                    <input type="hidden" name="customer_id_for_media" value="<?= $isNew ? '' : $model->id ?>">

                    <?php if (!$isNew && !empty($model->selected_image)): ?>
                        <div style="margin-bottom:15px">
                            <img src="<?= $model->selectedImagePath ?>" style="max-width:350px;border-radius:8px" alt="صورة العميل">
                        </div>
                    <?php endif ?>

                    <!-- ═══ Smart Media: Drag & Drop Upload ═══ -->
                    <div class="sm-zone">
                        <input type="file" multiple accept="image/*,application/pdf">
                        <div class="sm-zone-icon"><i class="fa fa-cloud-upload"></i></div>
                        <div class="sm-zone-text">اسحب الملفات هنا أو اضغط للاختيار</div>
                        <div class="sm-zone-hint">يدعم: JPG, PNG, WebP, PDF — حد أقصى 20MB لكل ملف</div>
                    </div>

                    <!-- ═══ Action Buttons ═══ -->
                    <div class="sm-actions">
                        <button type="button" class="sm-action-btn sm-webcam-btn">
                            <i class="fa fa-camera"></i> التقاط من الكاميرا
                        </button>
                        <button type="button" class="sm-action-btn" onclick="$('.sm-zone input').click()">
                            <i class="fa fa-folder-open"></i> اختيار ملفات
                        </button>
                    </div>

                    <!-- ═══ WebCam Interface ═══ -->
                    <div class="sm-webcam">
                        <video autoplay playsinline muted></video>
                        <canvas></canvas>
                        <div class="sm-webcam-controls">
                            <button type="button" class="sm-cam-btn sm-cam-close" title="إغلاق"><i class="fa fa-times"></i></button>
                            <button type="button" class="sm-cam-btn sm-cam-capture" title="التقاط"><i class="fa fa-camera"></i></button>
                            <button type="button" class="sm-cam-btn sm-cam-switch" title="تبديل الكاميرا"><i class="fa fa-refresh"></i></button>
                        </div>
                    </div>

                    <!-- ═══ Gallery: Uploaded Files + AI Results ═══ -->
                    <div class="sm-gallery">
                        <!-- Cards added dynamically by smart-media.js -->
                    </div>

                    <!-- ═══ AI Usage Stats Widget — Dual Source ═══ -->
                    <div class="sm-usage" style="margin-top:20px">
                        <h4 class="sm-usage-title"><i class="fa fa-bar-chart"></i> إحصائيات التصنيف الذكي</h4>

                        <!-- Tab Toggle: Local vs Google -->
                        <div style="display:flex; gap:4px; margin-bottom:12px; border-bottom:2px solid #eee; padding-bottom:8px">
                            <button type="button" class="sm-tab-btn active" data-tab="local" style="padding:5px 14px; border:1px solid #ddd; border-radius:6px 6px 0 0; background:#800020; color:#fff; font-size:11px; font-weight:700; cursor:pointer; border-bottom:none">
                                <i class="fa fa-database"></i> تتبع النظام
                            </button>
                            <button type="button" class="sm-tab-btn" data-tab="google" style="padding:5px 14px; border:1px solid #ddd; border-radius:6px 6px 0 0; background:#fff; color:#555; font-size:11px; font-weight:700; cursor:pointer; border-bottom:none">
                                <i class="fa fa-google"></i> Google Cloud مباشر
                            </button>
                        </div>

                        <!-- LOCAL Stats Panel -->
                        <div class="sm-stats-panel" data-panel="local">
                            <div class="sm-usage-grid">
                                <div class="sm-usage-item">
                                    <div class="sm-usage-val sm-usage-total">0</div>
                                    <div class="sm-usage-label">إجمالي الطلبات</div>
                                </div>
                                <div class="sm-usage-item">
                                    <div class="sm-usage-val sm-usage-success">0</div>
                                    <div class="sm-usage-label">ناجحة</div>
                                </div>
                                <div class="sm-usage-item">
                                    <div class="sm-usage-val sm-usage-cost">$0</div>
                                    <div class="sm-usage-label">التكلفة (تقدير)</div>
                                </div>
                                <div class="sm-usage-item">
                                    <div class="sm-usage-val sm-usage-remaining">1000</div>
                                    <div class="sm-usage-label">المتبقي مجاني</div>
                                </div>
                            </div>
                            <div class="sm-usage-bar"><div class="sm-usage-bar-fill" style="width:0%"></div></div>
                            <div class="sm-usage-hint">تتبع محلي — من سجلات النظام</div>
                        </div>

                        <!-- GOOGLE Stats Panel -->
                        <div class="sm-stats-panel" data-panel="google" style="display:none">
                            <div class="sm-usage-grid">
                                <div class="sm-usage-item">
                                    <div class="sm-usage-val sm-g-total">—</div>
                                    <div class="sm-usage-label">طلبات Google</div>
                                </div>
                                <div class="sm-usage-item">
                                    <div class="sm-usage-val sm-g-billable">—</div>
                                    <div class="sm-usage-label">قابلة للفوترة</div>
                                </div>
                                <div class="sm-usage-item">
                                    <div class="sm-usage-val sm-g-cost">—</div>
                                    <div class="sm-usage-label">التكلفة الفعلية</div>
                                </div>
                                <div class="sm-usage-item">
                                    <div class="sm-usage-val sm-g-remaining">—</div>
                                    <div class="sm-usage-label">المتبقي مجاني</div>
                                </div>
                            </div>
                            <div class="sm-usage-bar"><div class="sm-g-bar-fill sm-usage-bar-fill" style="width:0%"></div></div>
                            <div class="sm-g-status" style="font-size:11px; color:#888; margin-top:6px">
                                <i class="fa fa-spinner fa-spin"></i> جاري الاتصال بـ Google Cloud...
                            </div>
                            <div class="sm-g-billing-status" style="margin-top:6px; font-size:11px"></div>
                        </div>
                    </div>

                    <!-- ═══ Old ImageManager (Fallback) ═══ -->
                    <details style="margin-top:18px; border:1px solid #e5e5e5; border-radius:8px; padding:12px">
                        <summary style="cursor:pointer; font-size:13px; color:#888; font-weight:600">
                            <i class="fa fa-history"></i> إدارة الصور (النظام القديم)
                        </summary>
                        <div style="margin-top:12px">
                            <?= $form->field($model, 'customer_images')->widget(ImageManagerInputWidget::class, [
                                'aspectRatio' => 16/9,
                                'cropViewMode' => 1,
                                'showPreview' => true,
                                'showDeletePickedImageConfirm' => true,
                                'groupName' => 'coustmers',
                                'contractId' => $isNew ? $imgRandId : $model->id,
                            ])->label('إدارة الصور — ImageManager') ?>
                        </div>
                    </details>
                </div>

                <!-- Decision Actions (Desktop — also in risk panel) -->
                <div class="so-fieldset" style="background: var(--clr-primary-50); border-color: var(--clr-primary-200)">
                    <h3 class="so-fieldset-title"><i class="fa fa-gavel"></i> اتخاذ القرار</h3>
                    <p style="font-size:13px; color: var(--clr-text-muted); margin-bottom: 16px">
                        راجع التقييم في اللوحة الجانبية ثم اختر الإجراء المناسب
                    </p>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap">
                        <button type="button" class="so-btn so-btn-success so-decision-btn" data-decision="approved">
                            <i class="fa fa-check-circle"></i> حفظ — مقبول
                        </button>
                        <button type="button" class="so-btn so-btn-warning so-decision-btn" data-decision="conditional">
                            <i class="fa fa-exclamation-circle"></i> حفظ — مشروط
                        </button>
                        <button type="button" class="so-btn so-btn-danger so-decision-btn" data-decision="rejected">
                            <i class="fa fa-times-circle"></i> حفظ — مرفوض
                        </button>
                        <button type="button" class="so-btn so-btn-ghost so-decision-btn" data-decision="draft">
                            <i class="fa fa-save"></i> حفظ كمسودة
                        </button>
                    </div>
                </div>

                <div class="so-nav">
                    <button type="button" class="so-btn so-btn-outline so-prev-btn"><i class="fa fa-arrow-right"></i> <span>السابق</span></button>
                    <span></span>
                </div>
            </div>

            <?php ActiveForm::end() ?>
        </div>

        <!-- ═══════════════════════════════════════════
             RIGHT: RISK ASSESSMENT PANEL
             ═══════════════════════════════════════════ -->
        <div class="so-risk-panel">
            <!-- Mobile Handle -->
            <div class="rp-mobile-handle">
                <div class="rp-mobile-summary">
                    <span class="rp-mobile-score" style="font-size:18px; font-weight:800">—</span>
                    <span class="rp-tier-badge rp-mobile-tier rp-tier-conditional" style="font-size:11px; padding:3px 10px">—</span>
                </div>
                <div class="rp-mobile-handle-bar"></div>
            </div>

            <h3 class="rp-title"><i class="fa fa-shield"></i> تقييم المخاطر</h3>

            <!-- Score Gauge -->
            <div class="rp-gauge">
                <div class="rp-gauge-ring">
                    <svg viewBox="0 0 128 128">
                        <circle class="rp-gauge-bg" cx="64" cy="64" r="58" stroke-dasharray="364.42" stroke-dashoffset="0"></circle>
                        <circle class="rp-gauge-fill approved" cx="64" cy="64" r="58" stroke-dasharray="364.42" stroke-dashoffset="364.42"></circle>
                    </svg>
                    <div class="rp-gauge-center">
                        <div class="rp-score-num">—</div>
                        <div class="rp-score-label">درجة المخاطر</div>
                    </div>
                </div>
            </div>

            <!-- Tier -->
            <div class="rp-tier">
                <span class="rp-tier-badge rp-tier-conditional">جاري التقييم...</span>
            </div>

            <!-- Completeness -->
            <div class="rp-completeness">
                <div class="rp-completeness-header">
                    <span class="rp-completeness-label">اكتمال الملف</span>
                    <span class="rp-completeness-val">0%</span>
                </div>
                <div class="rp-completeness-bar">
                    <div class="rp-completeness-fill" style="width: 0"></div>
                </div>
            </div>

            <!-- Top Factors -->
            <div class="rp-factors">
                <h4 class="rp-factors-title"><i class="fa fa-bar-chart"></i> أهم العوامل</h4>
                <div class="rp-factors-list">
                    <!-- Populated by JS -->
                    <div style="text-align:center; color:#999; font-size:12px; padding:12px 0">أدخل البيانات لبدء التقييم</div>
                </div>
            </div>

            <!-- Financing Recommendation -->
            <div class="rp-financing" style="display:none">
                <h4 class="rp-financing-title"><i class="fa fa-calculator"></i> توصية التمويل</h4>
                <div class="rp-fin-grid">
                    <div class="rp-fin-item">
                        <div class="rp-fin-val" id="rp-fin-max">0</div>
                        <div class="rp-fin-label">سقف التمويل</div>
                    </div>
                    <div class="rp-fin-item">
                        <div class="rp-fin-val" id="rp-fin-installment">0</div>
                        <div class="rp-fin-label">القسط الأقصى</div>
                    </div>
                    <div class="rp-fin-item">
                        <div class="rp-fin-val" id="rp-fin-months">—</div>
                        <div class="rp-fin-label">المدة القصوى</div>
                    </div>
                    <div class="rp-fin-item">
                        <div class="rp-fin-val" id="rp-fin-available">0</div>
                        <div class="rp-fin-label">المتاح شهريًا</div>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <div class="rp-alerts">
                <!-- Populated by JS -->
            </div>

            <!-- Reasons -->
            <button type="button" class="rp-toggle-reasons">عرض سبب التقييم</button>
            <div class="rp-reasons">
                <!-- Populated by JS -->
            </div>

            <!-- Decision Actions (Risk Panel) -->
            <div class="rp-actions">
                <button type="button" class="so-btn so-btn-success so-decision-btn" data-decision="approved">
                    <i class="fa fa-check-circle"></i> حفظ — مقبول
                </button>
                <button type="button" class="so-btn so-btn-warning so-decision-btn" data-decision="conditional">
                    <i class="fa fa-exclamation-circle"></i> حفظ — مشروط
                </button>
                <button type="button" class="so-btn so-btn-danger so-decision-btn" data-decision="rejected">
                    <i class="fa fa-times-circle"></i> حفظ — مرفوض
                </button>
                <button type="button" class="so-btn so-btn-ghost so-decision-btn" data-decision="draft">
                    <i class="fa fa-save"></i> حفظ كمسودة
                </button>
            </div>
        </div>
    </div>
</div>
