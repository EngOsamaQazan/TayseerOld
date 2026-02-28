<?php
/**
 * نموذج العقد — تصميم ERP عالمي
 * تخطيط ثنائي الأعمدة · ملخص جانبي ثابت · سكانر سيريال · Samsung Fold Ready
 */
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\web\JsExpression;

$isNew = $model->isNewRecord;

/* ── AJAX Select2 — إعدادات مشتركة ── */
$custAjaxUrl = Url::to(['/customers/customers/search-customers']);
$custAjax = [
    'url'      => $custAjaxUrl,
    'dataType' => 'json',
    'delay'    => 250,
    'data'     => new JsExpression('function(p){return{q:p.term}}'),
    'processResults' => new JsExpression('function(d){return d}'),
    'cache'    => true,
];
$custTemplateResult = new JsExpression("function(i){if(i.loading)return i.text;var h='<div style=\"padding:4px 0\"><b>'+i.text+'</b>';if(i.id_number)h+=' <small style=\"color:#64748b\">· '+i.id_number+'</small>';if(i.phone)h+=' <small style=\"color:#0891b2\">☎ '+i.phone+'</small>';return $(h+'</div>')}");
$custTemplateSelection = new JsExpression("function(i){return i.text||i.id}");

/* بيانات أولية للتعديل — العميل والكفلاء والتضامني */
$custInitText = '';
$guarData = [];
$solData  = [];
if (!$isNew) {
    if ($model->customer_id && !is_array($model->customer_id)) {
        $c = \backend\modules\customers\models\Customers::findOne($model->customer_id);
        $custInitText = $c ? $c->name : '';
    }
    if (!empty($model->guarantors_ids)) {
        $ids = is_array($model->guarantors_ids) ? $model->guarantors_ids : [$model->guarantors_ids];
        $guarData = ArrayHelper::map(\backend\modules\customers\models\Customers::find()->select(['id','name'])->where(['id'=>$ids])->asArray()->all(), 'id', 'name');
    }
    if ($model->type === 'solidarity' && !empty($model->customers_ids)) {
        $ids = is_array($model->customers_ids) ? $model->customers_ids : [$model->customers_ids];
        $solData = ArrayHelper::map(\backend\modules\customers\models\Customers::find()->select(['id','name'])->where(['id'=>$ids])->asArray()->all(), 'id', 'name');
    }
}
?>

<style>
/* ═══════════════════════════════════════════════════════════════
   نظام ألوان — Financial Trust Theme
   ═══════════════════════════════════════════════════════════════ */
.nf{
    --nf-navy:#1a365d;--nf-blue:#2b6cb0;--nf-blue-l:#ebf4ff;
    --nf-teal:#0891b2;--nf-teal-l:#ecfeff;
    --nf-ok:#059669;--nf-ok-l:#ecfdf5;--nf-ok-b:#a7f3d0;
    --nf-warn:#d97706;--nf-warn-l:#fffbeb;--nf-warn-b:#fde68a;
    --nf-err:#dc2626;--nf-err-l:#fef2f2;--nf-err-b:#fecaca;
    --nf-text:#1a202c;--nf-text2:#718096;--nf-text3:#a0aec0;
    --nf-border:#e2e8f0;--nf-bg:#f7fafc;--nf-surface:#fff;
    --nf-r:10px;--nf-r-sm:6px;
    --nf-shadow:0 1px 3px rgba(0,0,0,.06),0 1px 2px rgba(0,0,0,.04);
    --nf-shadow-md:0 4px 14px rgba(0,0,0,.07);
    font-family:'Cairo','Segoe UI',sans-serif;color:var(--nf-text);line-height:1.6;
}

/* ═══ شريط التنقل بين الأقسام ═══ */
.nf-nav{display:flex;gap:4px;padding:10px 0 14px;position:sticky;top:0;z-index:50;background:var(--nf-bg);border-bottom:1px solid var(--nf-border);margin:-10px -5px 16px;padding:10px 5px 12px}
.nf-nav-pill{display:flex;align-items:center;gap:5px;padding:7px 14px;border-radius:20px;font-size:12px;font-weight:600;color:var(--nf-text2);background:var(--nf-surface);border:1px solid var(--nf-border);cursor:pointer;text-decoration:none!important;white-space:nowrap;transition:all .2s}
.nf-nav-pill:hover{color:var(--nf-navy);border-color:var(--nf-blue);background:var(--nf-blue-l)}
.nf-nav-pill.active{color:#fff;background:var(--nf-navy);border-color:var(--nf-navy)}
.nf-nav-pill i{font-size:12px}
.nf-nav-badge{background:var(--nf-teal);color:#fff;font-size:10px;font-weight:800;padding:1px 6px;border-radius:10px;margin-right:-2px}

/* ═══ التخطيط الثنائي ═══ */
.nf-layout{display:grid;grid-template-columns:1fr 340px;gap:18px;align-items:start}
.nf-main{min-width:0}
.nf-sidebar{position:sticky;top:60px}

/* ═══ البطاقات ═══ */
.nf-card{background:var(--nf-surface);border:1px solid var(--nf-border);border-radius:var(--nf-r);margin-bottom:16px;overflow:hidden;box-shadow:var(--nf-shadow)}
.nf-card-hd{padding:11px 16px;background:var(--nf-bg);border-bottom:1px solid var(--nf-border);display:flex;align-items:center;gap:8px}
.nf-card-hd i{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0}
.nf-card-hd .nf-ic-customer{background:#e9d5ff;color:#7c3aed}
.nf-card-hd .nf-ic-contract{background:var(--nf-blue-l);color:var(--nf-blue)}
.nf-card-hd .nf-ic-device{background:var(--nf-teal-l);color:var(--nf-teal)}
.nf-card-hd .nf-ic-finance{background:#fef3c7;color:#b45309}
.nf-card-hd .nf-ic-schedule{background:var(--nf-ok-l);color:var(--nf-ok)}
.nf-card-title{font-size:14px;font-weight:700;color:var(--nf-text);flex:1}
.nf-card-bd{padding:16px}

/* ═══ حقول الإدخال ═══ */
.nf .form-group{margin-bottom:12px}
.nf .control-label{font-size:11.5px;font-weight:700;color:var(--nf-text2);margin-bottom:3px;letter-spacing:.2px}
.nf .form-control{border:1.5px solid var(--nf-border);border-radius:var(--nf-r-sm);font-size:13.5px;color:var(--nf-text);height:38px;transition:border-color .2s}
.nf .form-control:focus{border-color:var(--nf-blue);box-shadow:0 0 0 3px rgba(43,108,176,.1)}
.nf textarea.form-control{height:auto;min-height:60px}

/* ═══ بيانات العميل ═══ */
.nf-cust-bar{display:none;padding:10px 16px;background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border-top:1px solid #bae6fd}
.nf-cust-bar.active{display:flex;flex-wrap:wrap;gap:16px;align-items:center}
.nf-cust-chip{display:flex;flex-direction:column}
.nf-cust-chip small{font-size:10px;font-weight:700;color:var(--nf-text3);text-transform:uppercase;letter-spacing:.5px}
.nf-cust-chip b{font-size:13px;font-weight:600;color:var(--nf-text)}

/* ═══ السكانر ═══ */
.nf-scanner{display:flex;gap:8px}
.nf-scanner-input{flex:1;height:46px!important;font-size:15px!important;font-family:'Courier New',monospace;letter-spacing:.8px;direction:ltr;text-align:left;border:2px solid var(--nf-teal)!important;border-radius:var(--nf-r)!important;background:var(--nf-teal-l)!important}
.nf-scanner-input:focus{border-color:var(--nf-navy)!important;background:#fff!important;box-shadow:0 0 0 4px rgba(8,145,178,.1)!important}
.nf-scanner-input::placeholder{color:var(--nf-text3);font-family:'Cairo',sans-serif;font-size:13px;letter-spacing:0}
.nf-scan-btn{height:46px;padding:0 20px;background:var(--nf-navy);color:#fff;border:none;border-radius:var(--nf-r);font-size:13px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap;transition:all .2s}
.nf-scan-btn:hover{background:#2a4a7f;box-shadow:var(--nf-shadow-md)}
.nf-scan-hint{font-size:11px;color:var(--nf-text3);margin-top:4px}
.nf-scan-hint kbd{background:var(--nf-bg);border:1px solid var(--nf-border);border-radius:3px;padding:1px 5px;font-size:10px;font-family:inherit}

/* ═══ رسالة السكانر ═══ */
.nf-scan-msg{padding:8px 12px;border-radius:var(--nf-r-sm);font-size:12px;font-weight:600;display:none;margin-top:8px;animation:nf-fadeIn .2s}
.nf-scan-msg.ok{display:flex;align-items:center;gap:6px;background:var(--nf-ok-l);color:var(--nf-ok);border:1px solid var(--nf-ok-b)}
.nf-scan-msg.err{display:flex;align-items:center;gap:6px;background:var(--nf-err-l);color:var(--nf-err);border:1px solid var(--nf-err-b)}

/* ═══ جدول الأجهزة ═══ */
.nf-dev-table{width:100%;border-collapse:collapse;margin-top:12px;font-size:13px}
.nf-dev-table th{background:var(--nf-bg);padding:8px 10px;font-size:11px;font-weight:700;color:var(--nf-text2);text-align:right;border-bottom:2px solid var(--nf-border);text-transform:uppercase;letter-spacing:.3px}
.nf-dev-table td{padding:9px 10px;border-bottom:1px solid #f1f5f9;vertical-align:middle;color:var(--nf-text)}
.nf-dev-table tr:hover td{background:#f8faff}
.nf-dev-table .nf-td-num{text-align:center;width:36px;color:var(--nf-text3);font-weight:700}
.nf-dev-table .nf-td-serial{direction:ltr;font-family:'Courier New',monospace;font-weight:600;font-size:12.5px;letter-spacing:.5px;color:var(--nf-navy)}
.nf-dev-table .nf-td-act{width:36px;text-align:center}
.nf-dev-rm{width:26px;height:26px;border-radius:50%;border:none;background:var(--nf-err-l);color:var(--nf-err);font-size:11px;cursor:pointer;transition:all .15s}
.nf-dev-rm:hover{background:var(--nf-err-b);transform:scale(1.15)}
.nf-dev-empty{text-align:center;padding:24px;color:var(--nf-text3);font-size:13px}
.nf-dev-empty i{font-size:26px;display:block;margin-bottom:6px;opacity:.3}
.nf-dev-badge{display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:700}
.nf-dev-badge.serial{background:var(--nf-ok-l);color:var(--nf-ok)}
.nf-dev-badge.manual{background:var(--nf-warn-l);color:var(--nf-warn)}

/* ═══ Fallback يدوي ═══ */
.nf-manual-link{font-size:11.5px;color:var(--nf-text3);cursor:pointer;margin-top:10px;display:inline-flex;align-items:center;gap:4px}
.nf-manual-link:hover{color:var(--nf-blue)}
.nf-manual-box{display:none;margin-top:10px;padding:10px;background:var(--nf-bg);border-radius:var(--nf-r-sm);border:1px dashed var(--nf-border)}
.nf-manual-box.open{display:flex;gap:8px;align-items:center}

/* ═══ المالية — أرقام بارزة ═══ */
.nf-money-row{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:14px}
.nf-money-card{background:var(--nf-bg);border:1.5px solid var(--nf-border);border-radius:var(--nf-r);padding:12px;text-align:center;transition:border-color .2s}
.nf-money-card:focus-within{border-color:var(--nf-blue);background:#fff}
.nf-money-card label{display:block;font-size:11px;font-weight:700;color:var(--nf-text2);margin-bottom:6px;letter-spacing:.3px}
.nf-money-card input{border:none!important;background:transparent!important;text-align:center;font-size:22px!important;font-weight:800!important;color:var(--nf-navy)!important;height:auto!important;padding:0!important;box-shadow:none!important;width:100%;font-family:'Cairo',sans-serif}
.nf-money-card input::placeholder{color:var(--nf-text3);font-size:16px;font-weight:400}
.nf-money-card .nf-currency{font-size:12px;font-weight:600;color:var(--nf-text3);margin-top:2px}

/* ═══ الملخص الجانبي ═══ */
.nf-summary{background:var(--nf-surface);border:1px solid var(--nf-border);border-radius:var(--nf-r);box-shadow:var(--nf-shadow-md);overflow:hidden}
.nf-sum-hd{padding:14px 16px;background:linear-gradient(135deg,var(--nf-navy),#2a4a7f);color:#fff;text-align:center}
.nf-sum-hd h4{margin:0;font-size:14px;font-weight:700;opacity:.9}
.nf-sum-bd{padding:16px}
.nf-sum-row{display:flex;justify-content:space-between;align-items:baseline;padding:8px 0;border-bottom:1px solid #f1f5f9}
.nf-sum-row:last-child{border-bottom:none}
.nf-sum-label{font-size:12px;font-weight:600;color:var(--nf-text2)}
.nf-sum-val{font-size:14px;font-weight:800;color:var(--nf-text)}
.nf-sum-val.big{font-size:22px;color:var(--nf-navy)}
.nf-sum-val.teal{color:var(--nf-teal)}
.nf-sum-val.ok{color:var(--nf-ok)}
.nf-sum-divider{height:1px;background:var(--nf-border);margin:10px 0}
.nf-sum-devices{font-size:12px;color:var(--nf-text2);text-align:center;padding:6px;background:var(--nf-bg);border-radius:var(--nf-r-sm);margin-bottom:10px}
.nf-sum-devices b{color:var(--nf-teal)}
.nf-sum-actions{display:flex;flex-direction:column;gap:8px;padding:14px 16px;border-top:1px solid var(--nf-border);background:var(--nf-bg)}
.nf-sum-actions .btn{width:100%;height:44px;font-size:13.5px;font-weight:700;border-radius:var(--nf-r-sm);display:flex;align-items:center;justify-content:center;gap:6px;border:none;cursor:pointer;transition:all .2s}
.nf-btn-print{background:var(--nf-navy);color:#fff}
.nf-btn-print:hover{background:#2a4a7f;color:#fff;box-shadow:var(--nf-shadow-md)}
.nf-btn-save{background:var(--nf-ok);color:#fff}
.nf-btn-save:hover{background:#047857;color:#fff}

/* ═══ أزرار الجوال (تظهر فقط على الشاشات الصغيرة) ═══ */
.nf-mobile-actions{display:none;position:sticky;bottom:0;padding:12px 16px;background:var(--nf-surface);border-top:1px solid var(--nf-border);box-shadow:0 -2px 10px rgba(0,0,0,.06);z-index:40;gap:8px}
.nf-mobile-actions .btn{flex:1;height:46px;font-size:14px;font-weight:700;border-radius:var(--nf-r-sm);display:flex;align-items:center;justify-content:center;gap:6px;border:none;cursor:pointer}

/* ═══ جدول الأقساط ═══ */
.nf-inst-table{max-height:260px;overflow-y:auto}
.nf-inst-table table{width:100%;border-collapse:collapse;font-size:12.5px}
.nf-inst-table th{background:var(--nf-bg);padding:7px 10px;font-weight:700;color:var(--nf-text2);text-align:center;border-bottom:2px solid var(--nf-border);position:sticky;top:0;font-size:11px}
.nf-inst-table td{padding:7px 10px;text-align:center;border-bottom:1px solid #f7fafc;color:var(--nf-text)}
.nf-inst-table tr:nth-child(even) td{background:#fafcfe}

/* ═══ Animations ═══ */
@keyframes nf-fadeIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
@keyframes nf-pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.05)}}

/* ═══════════════════════════════════════════════════════════════
   Responsive — Desktop → Laptop → Tablet → Fold Open → Mobile → Fold Closed
   ═══════════════════════════════════════════════════════════════ */

/* Laptop / شاشة مع sidebar ضيق */
@media(max-width:1200px){
    .nf-layout{grid-template-columns:1fr 300px}
    .nf-money-card input{font-size:19px!important}
}

/* Tablet / Fold مفتوح */
@media(max-width:991px){
    .nf-layout{grid-template-columns:1fr;gap:14px}
    .nf-sidebar{position:static;order:99}
    .nf-mobile-actions{display:flex}
    .nf-sum-actions{display:none}
    .nf-money-row{grid-template-columns:repeat(3,1fr)}
}

/* Tablet صغير */
@media(max-width:768px){
    .nf-nav{gap:2px;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;flex-wrap:nowrap}
    .nf-nav::-webkit-scrollbar{display:none}
    .nf-nav-pill{padding:6px 10px;font-size:11px}
    .nf-nav-pill span{display:none}
    .nf-card-bd{padding:12px}
    .nf-scanner{flex-direction:column}
    .nf-scan-btn{justify-content:center}
    .nf-money-row{grid-template-columns:1fr 1fr 1fr;gap:8px}
    .nf-money-card input{font-size:17px!important}
    .nf-money-card{padding:10px 8px}
    .nf-dev-table .nf-td-serial{font-size:11px}
}

/* Mobile */
@media(max-width:576px){
    .nf-money-row{grid-template-columns:1fr}
    .nf-money-card{display:flex;align-items:center;gap:10px;text-align:right;padding:10px 14px}
    .nf-money-card label{margin-bottom:0;min-width:70px}
    .nf-money-card input{text-align:right;font-size:18px!important}
    .nf-money-card .nf-currency{display:none}
    .nf-manual-box.open{flex-direction:column}
    .nf-mobile-actions{padding:10px 12px}
}

/* Samsung Fold مطوي + هواتف صغيرة */
@media(max-width:380px){
    .nf-nav-pill{padding:5px 8px;font-size:10px}
    .nf-card-hd{padding:9px 12px}
    .nf-card-hd .nf-card-title{font-size:13px}
    .nf-card-bd{padding:10px}
    .nf-scanner-input{height:42px!important;font-size:14px!important}
    .nf-scan-btn{height:42px;font-size:12px}
    .nf-money-card input{font-size:16px!important}
    .nf-mobile-actions .btn{height:42px;font-size:13px}
    .nf-sum-val.big{font-size:18px}
}

/* Fold Gen 1-4 */
@media(max-width:300px){
    .nf-nav{display:none}
    .nf-money-card{padding:8px 10px}
    .nf-money-card input{font-size:15px!important}
}
</style>

<div class="nf">
<?php $form = ActiveForm::begin(['id' => 'contract-form', 'options' => ['autocomplete' => 'off']]) ?>

<!-- ═══ شريط التنقل ═══ -->
<nav class="nf-nav" id="nf-nav">
    <a class="nf-nav-pill active" href="#sec-customer"><i class="fa fa-user"></i> <span>العميل</span></a>
    <a class="nf-nav-pill" href="#sec-contract"><i class="fa fa-file-text-o"></i> <span>العقد</span></a>
    <a class="nf-nav-pill" href="#sec-devices"><i class="fa fa-barcode"></i> <span>الأجهزة</span> <em class="nf-nav-badge" id="nf-dev-count" style="display:none">0</em></a>
    <a class="nf-nav-pill" href="#sec-finance"><i class="fa fa-money"></i> <span>المالية</span></a>
    <a class="nf-nav-pill" href="#sec-schedule"><i class="fa fa-calendar"></i> <span>الأقساط</span></a>
</nav>

<div class="nf-layout">
<!-- ══════════════════════ العمود الرئيسي ══════════════════════ -->
<div class="nf-main">

<!-- ─── 1. العميل ─── -->
<div class="nf-card" id="sec-customer">
    <div class="nf-card-hd"><i class="fa fa-user nf-ic-customer"></i><span class="nf-card-title">العميل والكفلاء</span></div>
    <div class="nf-card-bd">
        <div class="row">
            <div class="col-md-5" id="nf-normal-cust">
                <?= $form->field($model, 'customer_id')->widget(Select2::class, [
                    'initValueText' => $custInitText,
                    'options' => ['placeholder' => 'ابحث بالاسم أو الرقم الوطني أو الهاتف...', 'id' => 'nf-cust-id'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl', 'minimumInputLength' => 1,
                        'ajax' => $custAjax,
                        'templateResult' => $custTemplateResult,
                        'templateSelection' => $custTemplateSelection,
                    ],
                ])->label('العميل') ?>
            </div>
            <div class="col-md-5" id="nf-sol-cust" style="display:none">
                <?= $form->field($model, 'customers_ids')->widget(Select2::class, [
                    'data' => $solData,
                    'options' => ['placeholder' => 'ابحث واختر العملاء...', 'multiple' => true],
                    'pluginOptions' => ['allowClear' => true, 'minimumSelectionLength' => 2, 'dir' => 'rtl', 'minimumInputLength' => 1,
                        'ajax' => $custAjax,
                        'templateResult' => $custTemplateResult,
                        'templateSelection' => $custTemplateSelection,
                    ],
                ])->label('العملاء (تضامني)') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'type', ['inputOptions' => ['id' => 'nf-type']])->dropDownList(
                    ['normal' => 'عادي', 'solidarity' => 'تضامني'],
                    ['class' => 'form-control']
                )->label('النوع') ?>
            </div>
            <div class="col-md-5">
                <?= $form->field($model, 'guarantors_ids')->widget(Select2::class, [
                    'data' => $guarData,
                    'options' => ['placeholder' => 'ابحث واختر الكفلاء...', 'multiple' => true],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl', 'minimumInputLength' => 1,
                        'ajax' => $custAjax,
                        'templateResult' => $custTemplateResult,
                        'templateSelection' => $custTemplateSelection,
                    ],
                ])->label('الكفلاء') ?>
            </div>
        </div>
    </div>
    <div class="nf-cust-bar" id="nf-cust-bar">
        <div class="nf-cust-chip"><small>الاسم</small><b id="nc-name">—</b></div>
        <div class="nf-cust-chip"><small>الوطني</small><b id="nc-id">—</b></div>
        <div class="nf-cust-chip"><small>الميلاد</small><b id="nc-birth">—</b></div>
        <div class="nf-cust-chip"><small>الوظيفة</small><b id="nc-job">—</b></div>
        <div class="nf-cust-chip"><small>العقود</small><b id="nc-cnt" style="color:var(--nf-teal)">—</b></div>
        <button type="button" id="nf-edit-cust" class="btn btn-xs btn-default" title="تعديل العميل"><i class="fa fa-external-link"></i></button>
    </div>
</div>

<!-- ─── 2. معلومات العقد ─── -->
<div class="nf-card" id="sec-contract">
    <div class="nf-card-hd"><i class="fa fa-file-text-o nf-ic-contract"></i><span class="nf-card-title">معلومات العقد</span></div>
    <div class="nf-card-bd">
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'company_id')->dropDownList($companies, [
                    'prompt' => '— اختر الشركة —', 'class' => 'form-control',
                ])->label('الشركة') ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'Date_of_sale')->input('date', ['class' => 'form-control'])->label('تاريخ البيع') ?>
            </div>
        </div>
    </div>
</div>

<!-- ─── 3. الأجهزة ─── -->
<div class="nf-card" id="sec-devices">
    <div class="nf-card-hd"><i class="fa fa-barcode nf-ic-device"></i><span class="nf-card-title">الأجهزة</span></div>
    <div class="nf-card-bd">
        <div class="nf-scanner">
            <input type="text" id="nf-serial-in" class="form-control nf-scanner-input" placeholder="امسح أو اكتب الرقم التسلسلي..." autocomplete="off">
            <button type="button" id="nf-scan-btn" class="nf-scan-btn"><i class="fa fa-bolt"></i> مسح</button>
        </div>
        <div class="nf-scan-hint"><kbd>Enter</kbd> للمسح السريع · السكانر يعمل تلقائياً</div>
        <div id="nf-scan-msg" class="nf-scan-msg"></div>

        <table class="nf-dev-table" id="nf-dev-table" style="display:none">
            <thead><tr><th>#</th><th>الجهاز</th><th>الرقم التسلسلي</th><th>النوع</th><th></th></tr></thead>
            <tbody id="nf-dev-body"></tbody>
        </table>
        <div class="nf-dev-empty" id="nf-dev-empty">
            <i class="fa fa-mobile"></i>
            امسح الرقم التسلسلي لإضافة جهاز للعقد
        </div>

        <?php foreach ($scannedSerials as $s): ?>
            <input type="hidden" name="serial_ids[]" value="<?= $s['id'] ?>" class="nf-sh" data-sid="<?= $s['id'] ?>" data-sn="<?= Html::encode($s['serial_number']) ?>">
        <?php endforeach ?>

        <span class="nf-manual-link" id="nf-manual-link"><i class="fa fa-list-ul"></i> إضافة يدوية بدون سيريال</span>
        <div class="nf-manual-box" id="nf-manual-box">
            <select id="nf-manual-sel" class="form-control" style="flex:1">
                <option value="">— اختر الصنف —</option>
                <?php foreach ($inventoryItems as $id => $name): ?>
                    <option value="<?= $id ?>"><?= Html::encode($name) ?></option>
                <?php endforeach ?>
            </select>
            <button type="button" id="nf-manual-add" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> أضف</button>
        </div>
    </div>
</div>

<!-- ─── 4. المعلومات المالية ─── -->
<div class="nf-card" id="sec-finance">
    <div class="nf-card-hd"><i class="fa fa-money nf-ic-finance"></i><span class="nf-card-title">المعلومات المالية</span></div>
    <div class="nf-card-bd">
        <!-- الأرقام البارزة -->
        <div class="nf-money-row">
            <div class="nf-money-card">
                <label>الدفعة الأولى</label>
                <?= Html::activeTextInput($model, 'first_installment_value', [
                    'type'=>'number','step'=>'1','min'=>'0','placeholder'=>'0',
                    'class'=>'form-control nf-calc','id'=>'nf-fv',
                ]) ?>
                <div class="nf-currency">د.أ</div>
            </div>
            <div class="nf-money-card">
                <label>إجمالي العقد</label>
                <?= Html::activeTextInput($model, 'total_value', [
                    'type'=>'number','step'=>'1','min'=>'0','placeholder'=>'0',
                    'class'=>'form-control nf-calc','id'=>'nf-tv',
                ]) ?>
                <div class="nf-currency">د.أ</div>
            </div>
            <div class="nf-money-card">
                <label>القسط الشهري</label>
                <?= Html::activeTextInput($model, 'monthly_installment_value', [
                    'type'=>'number','step'=>'1','min'=>'0','placeholder'=>'0',
                    'class'=>'form-control nf-calc','id'=>'nf-mv',
                ]) ?>
                <div class="nf-currency">د.أ</div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'first_installment_date')->input('date', [
                    'class'=>'form-control nf-calc','id'=>'nf-fd',
                ])->label('تاريخ أول قسط') ?>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="control-label">خصم الالتزام</label>
                    <div class="input-group">
                        <?= Html::activeTextInput($model, 'commitment_discount', [
                            'type'=>'number','step'=>'1','min'=>'0','placeholder'=>'0',
                            'class'=>'form-control',
                        ]) ?>
                        <span class="input-group-addon" style="background:var(--nf-bg);border:1.5px solid var(--nf-border);color:var(--nf-text2);font-size:12px;font-weight:700">د.أ</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'loss_commitment')->textInput([
                    'type'=>'number','placeholder'=>'0','class'=>'form-control',
                ])->label('التزام الخسارة') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <?= $form->field($model, 'notes')->textarea([
                    'rows'=>2,'placeholder'=>'ملاحظات إضافية...','class'=>'form-control',
                ])->label('ملاحظات') ?>
            </div>
        </div>
    </div>
</div>

<!-- ─── 5. جدول الأقساط ─── -->
<div class="nf-card" id="sec-schedule" style="display:none">
    <div class="nf-card-hd"><i class="fa fa-calendar nf-ic-schedule"></i><span class="nf-card-title">جدول الأقساط المتوقع</span></div>
    <div class="nf-card-bd" style="padding:0">
        <div class="nf-inst-table">
            <table><thead><tr><th>#</th><th>المبلغ</th><th>الشهر</th><th>السنة</th></tr></thead>
            <tbody id="nf-inst-body"></tbody></table>
        </div>
    </div>
</div>

</div><!-- /nf-main -->

<!-- ══════════════════════ الملخص الجانبي ══════════════════════ -->
<aside class="nf-sidebar">
    <div class="nf-summary">
        <div class="nf-sum-hd"><h4><i class="fa fa-calculator"></i> ملخص العقد</h4></div>
        <div class="nf-sum-bd">
            <div class="nf-sum-devices" id="nf-sum-devs"><i class="fa fa-mobile"></i> الأجهزة: <b>0</b></div>
            <div class="nf-sum-row"><span class="nf-sum-label">إجمالي العقد</span><span class="nf-sum-val big" id="ns-total">0 د.أ</span></div>
            <div class="nf-sum-row"><span class="nf-sum-label">الدفعة الأولى</span><span class="nf-sum-val" id="ns-first">0 د.أ</span></div>
            <div class="nf-sum-row"><span class="nf-sum-label">المتبقي بالتقسيط</span><span class="nf-sum-val teal" id="ns-remaining">0 د.أ</span></div>
            <div class="nf-sum-divider"></div>
            <div class="nf-sum-row"><span class="nf-sum-label">القسط الشهري</span><span class="nf-sum-val ok" id="ns-monthly">0 د.أ</span></div>
            <div class="nf-sum-row"><span class="nf-sum-label">عدد الأقساط</span><span class="nf-sum-val" id="ns-count">—</span></div>
            <div class="nf-sum-row"><span class="nf-sum-label">تاريخ أول قسط</span><span class="nf-sum-val" id="ns-date">—</span></div>
        </div>
        <div class="nf-sum-actions">
            <?= Html::submitButton('<i class="fa fa-print"></i> '.($isNew?'إنشاء وطباعة':'حفظ وطباعة'), [
                'name'=>'print','class'=>'btn nf-btn-print',
            ]) ?>
            <?= Html::submitButton('<i class="fa '.($isNew?'fa-plus':'fa-save').'"></i> '.($isNew?'إنشاء العقد':'حفظ التعديلات'), [
                'class'=>'btn nf-btn-save',
            ]) ?>
        </div>
    </div>
</aside>

</div><!-- /nf-layout -->

<!-- أزرار الجوال -->
<div class="nf-mobile-actions">
    <?= Html::submitButton('<i class="fa fa-print"></i> '.($isNew?'إنشاء وطباعة':'حفظ وطباعة'), [
        'name'=>'print','class'=>'btn nf-btn-print',
    ]) ?>
    <?= Html::submitButton('<i class="fa '.($isNew?'fa-plus':'fa-save').'"></i> '.($isNew?'حفظ':'حفظ'), [
        'class'=>'btn nf-btn-save',
    ]) ?>
</div>

<?php ActiveForm::end() ?>
</div>

<script>
(function(){
'use strict';
var L=<?=json_encode(Url::to(['/contracts/contracts/lookup-serial']))?>,
    C=<?=json_encode(Url::to(['/customers/customers/customer-data']))?>,
    E=<?=json_encode(Url::to(['/customers/customers/update']))?>,
    pre=<?=json_encode($scannedSerials)?>,
    ids={},devNum=0;

/* ══════ تحميل الأجهزة الموجودة ══════ */
if(pre.length){
    pre.forEach(function(s){ids[s.id]=1;addRow(s.id,s.item_name,s.serial_number,'serial')});
    syncDevUI();
}

/* ══════ التنقل بين الأقسام ══════ */
document.querySelectorAll('.nf-nav-pill').forEach(function(p){
    p.addEventListener('click',function(e){
        e.preventDefault();
        var t=document.querySelector(this.getAttribute('href'));
        if(t)t.scrollIntoView({behavior:'smooth',block:'start'});
        document.querySelectorAll('.nf-nav-pill').forEach(function(x){x.classList.remove('active')});
        this.classList.add('active');
    });
});

/* ══════ السكانر ══════ */
var inp=document.getElementById('nf-serial-in'),btn=document.getElementById('nf-scan-btn');
inp.addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();scan()}});
btn.addEventListener('click',scan);

function scan(){
    var s=inp.value.trim();if(!s)return;
    // تكرار؟
    if(document.querySelector('.nf-sh[data-sn="'+CSS.escape(s)+'"]')){msg('مضاف مسبقاً',0);inp.select();return}
    btn.disabled=1;btn.innerHTML='<i class="fa fa-spinner fa-spin"></i>';
    $.get(L,{serial:s},function(r){
        btn.disabled=0;btn.innerHTML='<i class="fa fa-bolt"></i> مسح';
        if(r.success){
            var d=r.data;
            if(ids[d.id]){msg('الجهاز مضاف مسبقاً',0)}
            else{ids[d.id]=1;addRow(d.id,d.item_name,d.serial_number,'serial');addHidden(d.id,d.serial_number);msg(d.item_name+' — تمت الإضافة',1);syncDevUI()}
            inp.value='';inp.focus()
        }else{msg(r.message,0);inp.select()}
    },'json').fail(function(){btn.disabled=0;btn.innerHTML='<i class="fa fa-bolt"></i> مسح';msg('خطأ في الاتصال',0)})
}

function msg(t,ok){
    var el=document.getElementById('nf-scan-msg');
    el.className='nf-scan-msg '+(ok?'ok':'err');
    el.innerHTML='<i class="fa '+(ok?'fa-check-circle':'fa-exclamation-circle')+'"></i> '+t;
    clearTimeout(el._t);el._t=setTimeout(function(){el.className='nf-scan-msg';el.innerHTML=''},4000)
}

function addRow(id,name,serial,type){
    devNum++;
    var badge=type==='serial'?'<span class="nf-dev-badge serial"><i class="fa fa-barcode"></i> سيريال</span>':'<span class="nf-dev-badge manual"><i class="fa fa-cube"></i> يدوي</span>';
    var sn=type==='serial'?'<span class="nf-td-serial">'+esc(serial)+'</span>':'<span style="color:var(--nf-text3)">—</span>';
    var tr=document.createElement('tr');tr.id='nf-row-'+id;tr.setAttribute('data-dev-id',id);
    tr.innerHTML='<td class="nf-td-num">'+devNum+'</td><td>'+esc(name)+'</td><td>'+sn+'</td><td>'+badge+'</td><td class="nf-td-act"><button type="button" class="nf-dev-rm" onclick="window._nfRm(\''+id+'\')"><i class="fa fa-times"></i></button></td>';
    document.getElementById('nf-dev-body').appendChild(tr)
}

function addHidden(id,sn){
    var h=document.createElement('input');h.type='hidden';h.name='serial_ids[]';h.value=id;
    h.className='nf-sh';h.setAttribute('data-sid',id);h.setAttribute('data-sn',sn);
    document.getElementById('nf-dev-body').parentElement.appendChild(h)
}

window._nfRm=function(id){
    delete ids[id];
    var r=document.getElementById('nf-row-'+id);if(r)r.remove();
    document.querySelectorAll('.nf-sh[data-sid="'+id+'"]').forEach(function(h){h.remove()});
    // إعادة ترقيم
    devNum=0;document.querySelectorAll('#nf-dev-body tr').forEach(function(tr){tr.querySelector('.nf-td-num').textContent=++devNum});
    syncDevUI();inp.focus()
};

function syncDevUI(){
    var c=document.querySelectorAll('#nf-dev-body tr').length;
    document.getElementById('nf-dev-table').style.display=c?'':'none';
    document.getElementById('nf-dev-empty').style.display=c?'none':'';
    var badge=document.getElementById('nf-dev-count');
    if(c){badge.style.display='';badge.textContent=c}else{badge.style.display='none'}
    document.getElementById('nf-sum-devs').innerHTML='<i class="fa fa-mobile"></i> الأجهزة: <b>'+c+'</b>'
}

/* ══════ الاختيار اليدوي ══════ */
document.getElementById('nf-manual-link').addEventListener('click',function(){document.getElementById('nf-manual-box').classList.toggle('open')});
document.getElementById('nf-manual-add').addEventListener('click',function(){
    var sel=document.getElementById('nf-manual-sel'),v=sel.value,n=sel.options[sel.selectedIndex].text;
    if(!v)return;
    var uid='m'+Date.now();
    addRow(uid,n,'','manual');
    var h=document.createElement('input');h.type='hidden';h.name='manual_item_ids[]';h.value=v;h.className='nf-sh';h.setAttribute('data-sid',uid);
    document.getElementById('nf-dev-body').parentElement.appendChild(h);
    syncDevUI();sel.value=''
});

/* ══════ العميل ══════ */
$('#nf-cust-id').on('change',function(){
    var d=$(this).select2('data');
    if(!d||!d[0]||!d[0].id){document.getElementById('nf-cust-bar').classList.remove('active');return}
    $.get(C,{id:d[0].id},function(r){
        if(r&&r.model){
            document.getElementById('nc-name').textContent=r.model.name||'—';
            document.getElementById('nc-id').textContent=r.model.id_number||'—';
            document.getElementById('nc-birth').textContent=r.model.birth_date||'—';
            document.getElementById('nc-job').textContent=r.model.job_title||'—';
            document.getElementById('nc-cnt').textContent=r.contracts_info?r.contracts_info.count:'0';
            document.getElementById('nf-cust-bar').classList.add('active')
        }
    })
});
document.getElementById('nf-edit-cust').addEventListener('click',function(){
    var d=$('#nf-cust-id').select2('data');if(d&&d[0])window.open(E+'?id='+d[0].id,'_blank')
});

/* ══════ نوع العقد ══════ */
function nfSyncType(){
    var s=document.getElementById('nf-type').value==='solidarity';
    document.getElementById('nf-sol-cust').style.display=s?'':'none';
    document.getElementById('nf-normal-cust').style.display=s?'none':'';
}
document.getElementById('nf-type').addEventListener('change',nfSyncType);
nfSyncType();

/* ══════ حساب الأقساط + تحديث الملخص ══════ */
document.querySelectorAll('.nf-calc').forEach(function(el){el.addEventListener('input',calc);el.addEventListener('change',calc)});

function calc(){
    var tv=parseFloat(document.getElementById('nf-tv').value)||0,
        fv=parseFloat(document.getElementById('nf-fv').value)||0,
        mv=parseFloat(document.getElementById('nf-mv').value)||0,
        fd=document.getElementById('nf-fd').value,
        remaining=tv>fv?tv-fv:tv,
        count=mv>0?Math.ceil(remaining/mv):0;

    // ملخص جانبي
    document.getElementById('ns-total').textContent=tv?tv+' د.أ':'0 د.أ';
    document.getElementById('ns-first').textContent=fv?fv+' د.أ':'0 د.أ';
    document.getElementById('ns-remaining').textContent=remaining?remaining+' د.أ':'0 د.أ';
    document.getElementById('ns-monthly').textContent=mv?mv+' د.أ':'0 د.أ';
    document.getElementById('ns-count').textContent=count||'—';
    document.getElementById('ns-date').textContent=fd||'—';

    // جدول الأقساط
    var sec=document.getElementById('sec-schedule'),tbody=document.getElementById('nf-inst-body');
    tbody.innerHTML='';
    if(tv>0&&mv>0&&fd&&count>0){
        var sd=new Date(fd);
        for(var i=0;i<count;i++){
            var d=new Date(sd);d.setMonth(d.getMonth()+i);
            var amt=(i===count-1)?(remaining-mv*(count-1)):mv;if(amt<=0)amt=mv;
            tbody.innerHTML+='<tr><td>'+(i+1)+'</td><td><b>'+amt+'</b> د.أ</td><td>'+(d.getMonth()+1)+'</td><td>'+d.getFullYear()+'</td></tr>'
        }
        sec.style.display=''
    }else{sec.style.display='none'}
}
calc();

function esc(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML}
})();
</script>
