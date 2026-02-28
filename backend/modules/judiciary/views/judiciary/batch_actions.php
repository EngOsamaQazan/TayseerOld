<?php
/**
 * الإدخال المجمّع الذكي للإجراءات القضائية
 * Wizard: لصق → مراجعة + تعيين الإجراءات → تنفيذ
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use backend\modules\judiciaryActions\models\JudiciaryActions;

$this->title = 'الإدخال المجمّع';
$this->params['breadcrumbs'][] = ['label' => 'القسم القانوني', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$parseUrl   = Url::to(['batch-parse']);
$executeUrl = Url::to(['batch-execute']);

$allActions = JudiciaryActions::find()
    ->andWhere(['or', ['is_deleted' => 0], ['is_deleted' => null]])
    ->orderBy(['name' => SORT_ASC])
    ->all();

$natureLabels = ['request' => 'طلبات إجرائية', 'document' => 'كتب ومذكرات', 'doc_status' => 'حالات كتب', 'process' => 'إجراءات إدارية'];
$natureIcons  = ['request' => 'fa-file-text-o', 'document' => 'fa-file-o', 'doc_status' => 'fa-exchange', 'process' => 'fa-cog'];
$natureColors = ['request' => '#3B82F6', 'document' => '#8B5CF6', 'doc_status' => '#EA580C', 'process' => '#64748B'];
$grouped = [];
foreach ($allActions as $a) { $grouped[$a->action_nature ?: 'process'][] = $a; }

$actionsJson = [];
$natureOrder = ['request', 'document', 'doc_status', 'process'];
foreach ($natureOrder as $nature) {
    if (empty($grouped[$nature])) continue;
    foreach ($grouped[$nature] as $a) {
        $actionsJson[] = [
            'id' => $a->id,
            'name' => $a->name,
            'nature' => $nature,
            'natureLabel' => $natureLabels[$nature],
            'color' => $natureColors[$nature],
        ];
    }
}
?>

<style>
.ba { font-family:'Tajawal','Cairo',sans-serif; direction:rtl; max-width:1100px; margin:0 auto; }

/* Steps indicator */
.ba-steps { display:flex; gap:0; margin-bottom:24px; background:#fff; border-radius:12px; border:1px solid #E2E8F0; overflow:hidden; }
.ba-step {
    flex:1; display:flex; align-items:center; justify-content:center; gap:8px;
    padding:14px 12px; font-size:13px; font-weight:600; color:#94A3B8;
    border-left:1px solid #E2E8F0; position:relative;
}
.ba-step:last-child { border-left:none; }
.ba-step .ba-step-num {
    width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    background:#F1F5F9; font-size:12px; font-weight:700;
}
.ba-step.active { color:#800020; background:#FDF2F4; }
.ba-step.active .ba-step-num { background:#800020; color:#fff; }
.ba-step.done { color:#10B981; }
.ba-step.done .ba-step-num { background:#10B981; color:#fff; }

/* Panels */
.ba-panel { display:none; background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:24px; }
.ba-panel.active { display:block; }

/* Step 1: Input */
.ba-textarea {
    width:100%; min-height:250px; border:2px dashed #CBD5E1; border-radius:10px;
    padding:16px; font-family:'Courier New',monospace; font-size:14px; line-height:1.8;
    direction:ltr; text-align:left; resize:vertical; outline:none;
}
.ba-textarea:focus { border-color:#800020; box-shadow:0 0 0 3px rgba(128,0,32,.1); }
.ba-textarea::placeholder { color:#CBD5E1; font-family:'Tajawal',sans-serif; direction:rtl; text-align:right; }
.ba-hint { display:flex; align-items:center; gap:8px; padding:10px 14px; background:#F0F9FF; border-radius:8px; font-size:12px; color:#0369A1; margin-bottom:14px; }
.ba-hint i { font-size:16px; }

/* Toolbar */
.ba-toolbar {
    display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;
    padding:14px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; margin-bottom:14px;
}
.ba-toolbar-group { display:flex; flex-direction:column; gap:4px; }
.ba-toolbar-group label { font-size:11px; font-weight:600; color:#64748B; }
.ba-toolbar-group input, .ba-toolbar-group textarea { padding:7px 10px; border:1px solid #D1D5DB; border-radius:7px; font-size:12px; font-family:inherit; outline:none; }
.ba-toolbar-group input:focus, .ba-toolbar-group textarea:focus { border-color:#800020; box-shadow:0 0 0 2px rgba(128,0,32,.08); }
.ba-toolbar-action-pick {
    position:relative; flex:1; min-width:200px;
}
.ba-toolbar-action-input {
    width:100%; padding:7px 10px; border:1px solid #D1D5DB; border-radius:7px;
    font-size:12px; font-family:inherit; outline:none; cursor:pointer; background:#fff;
}
.ba-toolbar-action-input:focus { border-color:#800020; box-shadow:0 0 0 2px rgba(128,0,32,.08); }
.ba-toolbar-dd {
    display:none; position:absolute; top:100%; left:0; right:0; z-index:999;
    background:#fff; border:1px solid #E2E8F0; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,.12);
    max-height:260px; overflow-y:auto; margin-top:4px;
}
.ba-toolbar-dd.open { display:block; }
.ba-toolbar-dd-item {
    display:flex; align-items:center; gap:6px; padding:7px 12px; cursor:pointer;
    font-size:12px; border-bottom:1px solid #F5F5F5; transition:background .12s;
}
.ba-toolbar-dd-item:last-child { border-bottom:none; }
.ba-toolbar-dd-item:hover { background:#FDF2F4; }
.ba-toolbar-dd-item .bullet { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
.ba-toolbar-dd-nature {
    padding:5px 12px; font-size:10px; font-weight:700; color:#64748B; background:#F8FAFC;
    text-transform:uppercase; letter-spacing:.5px; position:sticky; top:0;
}
.ba-apply-btn {
    padding:7px 18px; border-radius:7px; border:none; background:#800020; color:#fff;
    font-size:12px; font-weight:600; font-family:inherit; cursor:pointer; white-space:nowrap;
    transition:background .2s;
}
.ba-apply-btn:hover { background:#600018; }

/* Results table */
.ba-results { border:1px solid #E2E8F0; border-radius:10px; overflow-x:auto; }
.ba-results table { width:100%; border-collapse:collapse; font-size:12px; }
.ba-results th { background:linear-gradient(135deg,#800020,#a0003a); color:#fff; padding:10px 12px; font-weight:600; text-align:right; white-space:nowrap; }
.ba-results td { padding:10px 12px; border-bottom:1px solid #F1F5F9; vertical-align:middle; }
.ba-results tr:hover td { background:#FDF2F4; }
.ba-status { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.ba-status-matched { background:#DCFCE7; color:#166534; }
.ba-status-multiple { background:#FEF3C7; color:#92400E; }
.ba-status-not_found { background:#FEE2E2; color:#991B1B; }
.ba-status-error { background:#F1F5F9; color:#64748B; }
.ba-court-select { padding:4px 8px; border:1px solid #E2E8F0; border-radius:6px; font-size:11px; font-family:inherit; }
.ba-parties-list { font-size:11px; color:#64748B; }
.ba-party-chips { display:flex; flex-wrap:wrap; gap:4px; max-width:180px; }
.ba-party-chip {
    display:inline-flex; align-items:center; gap:4px; padding:2px 8px;
    border-radius:6px; font-size:10px; font-weight:600; cursor:pointer;
    border:1.5px solid #E2E8F0; background:#fff; transition:all .15s; white-space:nowrap;
}
.ba-party-chip:hover { border-color:#94A3B8; }
.ba-party-chip.selected { border-color:#800020; background:#FDF2F4; color:#800020; }
.ba-party-chip .chip-check { font-size:11px; }
.ba-party-chip.selected .chip-check { color:#800020; }
.ba-party-selectall {
    font-size:10px; color:#059669; cursor:pointer; font-weight:600;
    padding:2px 6px; border-radius:4px; background:#ECFDF5; border:none; margin-bottom:2px;
}
.ba-check { width:16px; height:16px; cursor:pointer; accent-color:#800020; }

/* Per-row action chip */
.ba-row-action-chip {
    display:inline-flex; align-items:center; gap:4px; padding:3px 10px;
    border-radius:16px; font-size:11px; font-weight:600; cursor:pointer;
    border:1.5px solid #E2E8F0; background:#fff; transition:all .15s;
    white-space:nowrap; position:relative;
}
.ba-row-action-chip:hover { border-color:#800020; background:#FDF2F4; }
.ba-row-action-chip.has-action { border-color:#3B82F6; background:#EFF6FF; color:#1D4ED8; }
.ba-row-action-chip .ba-chip-bullet { width:6px; height:6px; border-radius:50%; flex-shrink:0; }
.ba-row-action-chip .ba-chip-x {
    font-size:12px; font-weight:700; color:#94A3B8; margin-right:2px; cursor:pointer;
}
.ba-row-action-chip .ba-chip-x:hover { color:#EF4444; }
.ba-row-dd {
    display:none; position:fixed; z-index:9999;
    background:#fff; border:1px solid #E2E8F0; border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,.15);
    max-height:300px; overflow-y:auto; min-width:260px; width:280px;
}
.ba-row-dd.open { display:block; }
.ba-row-dd-search {
    padding:8px 10px; border:none; border-bottom:1px solid #E2E8F0;
    font-size:12px; width:100%; outline:none; font-family:inherit;
    position:sticky; top:0; background:#fff; z-index:1;
}
.ba-row-dd-item {
    display:flex; align-items:center; gap:5px; padding:7px 12px; cursor:pointer;
    font-size:12px; border-bottom:1px solid #F8F8F8; transition:background .12s;
}
.ba-row-dd-item:last-child { border-bottom:none; }
.ba-row-dd-item:hover { background:#FDF2F4; }
.ba-row-dd-item .bullet { width:6px; height:6px; border-radius:50%; flex-shrink:0; }

/* Per-row note */
.ba-row-note-input {
    width:100%; padding:4px 8px; border:1px solid #E2E8F0; border-radius:5px;
    font-size:11px; font-family:inherit; outline:none; min-width:100px;
    transition:border-color .15s;
}
.ba-row-note-input:focus { border-color:#800020; box-shadow:0 0 0 2px rgba(128,0,32,.08); }
.ba-row-note-input::placeholder { color:#CBD5E1; }

/* Progress */
.ba-progress-bar { width:100%; height:8px; background:#E2E8F0; border-radius:4px; overflow:hidden; margin-bottom:16px; }
.ba-progress-fill { height:100%; background:linear-gradient(90deg,#800020,#10B981); transition:width .3s; border-radius:4px; }
.ba-summary { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px; }
.ba-summary-card { padding:16px; border-radius:10px; text-align:center; border:1px solid #E2E8F0; }
.ba-summary-card .num { font-size:28px; font-weight:800; }
.ba-summary-card .lbl { font-size:12px; color:#64748B; }
.ba-detail-log { max-height:300px; overflow-y:auto; border:1px solid #E2E8F0; border-radius:8px; }
.ba-detail-row { display:flex; align-items:center; gap:10px; padding:8px 12px; border-bottom:1px solid #F1F5F9; font-size:12px; }
.ba-detail-row:last-child { border-bottom:none; }

/* Buttons */
.ba-actions { display:flex; justify-content:space-between; margin-top:20px; padding-top:16px; border-top:1px solid #E2E8F0; }
.ba-btn {
    display:inline-flex; align-items:center; gap:6px; padding:10px 24px;
    border-radius:8px; border:none; font-family:inherit; font-size:13px;
    font-weight:600; cursor:pointer; transition:all .2s;
}
.ba-btn-primary { background:#800020; color:#fff; }
.ba-btn-primary:hover { background:#600018; }
.ba-btn-primary:disabled { background:#CBD5E1; cursor:not-allowed; }
.ba-btn-secondary { background:#F1F5F9; color:#475569; }
.ba-btn-secondary:hover { background:#E2E8F0; }
.ba-btn-success { background:#059669; color:#fff; }
.ba-btn-success:hover { background:#047857; }

/* Stats bar */
.ba-stats { display:flex; gap:12px; margin-bottom:14px; flex-wrap:wrap; }
.ba-stat { display:flex; align-items:center; gap:6px; padding:6px 14px; border-radius:8px; font-size:12px; font-weight:600; }
.ba-stat-ok { background:#DCFCE7; color:#166534; }
.ba-stat-warn { background:#FEF3C7; color:#92400E; }
.ba-stat-err { background:#FEE2E2; color:#991B1B; }
.ba-stat-total { background:#F0F0FF; color:#4338CA; }

@media (max-width:768px) {
    .ba-toolbar { flex-direction:column; }
    .ba-summary { grid-template-columns:1fr; }
    .ba-steps { flex-wrap:wrap; }
}
</style>

<div class="ba">
    <!-- Steps Indicator (3 steps) -->
    <div class="ba-steps">
        <div class="ba-step active" data-step="1"><span class="ba-step-num">1</span> لصق الأرقام</div>
        <div class="ba-step" data-step="2"><span class="ba-step-num">2</span> مراجعة وتعيين الإجراءات</div>
        <div class="ba-step" data-step="3"><span class="ba-step-num">3</span> التنفيذ</div>
    </div>

    <!-- Step 1: Paste Numbers -->
    <div class="ba-panel active" id="ba-step1">
        <h3 style="margin:0 0 14px;font-size:18px;font-weight:700;color:#1E293B"><i class="fa fa-paste" style="color:#800020;margin-left:8px"></i> لصق أرقام القضايا</h3>
        <div class="ba-hint">
            <i class="fa fa-lightbulb-o"></i>
            أدخل رقم قضية/سنة في كل سطر — الفواصل المقبولة: <code>/</code> <code>\</code> <code>-</code> أو مسافة.
            مثال: <code>2019/15938</code> أو <code>15938-2019</code>
        </div>
        <textarea class="ba-textarea" id="ba-input" placeholder="الصق أرقام القضايا هنا...&#10;&#10;2019/15938&#10;2020/4437&#10;2021-7345"></textarea>
        <div class="ba-actions">
            <a href="<?= Url::to(['index']) ?>" class="ba-btn ba-btn-secondary"><i class="fa fa-arrow-right"></i> رجوع</a>
            <button class="ba-btn ba-btn-primary" id="ba-parse-btn"><i class="fa fa-search"></i> تحليل ومطابقة</button>
        </div>
    </div>

    <!-- Step 2: Review + Assign Actions -->
    <div class="ba-panel" id="ba-step2">
        <h3 style="margin:0 0 14px;font-size:18px;font-weight:700;color:#1E293B"><i class="fa fa-check-square-o" style="color:#800020;margin-left:8px"></i> مراجعة وتعيين الإجراءات</h3>
        <div class="ba-stats" id="ba-stats"></div>

        <!-- Toolbar: bulk action assignment + date + note -->
        <div class="ba-toolbar">
            <div class="ba-toolbar-group ba-toolbar-action-pick">
                <label><i class="fa fa-gavel"></i> الإجراء</label>
                <input type="text" class="ba-toolbar-action-input" id="ba-bulk-action-input" placeholder="ابحث واختر الإجراء..." autocomplete="off">
                <div class="ba-toolbar-dd" id="ba-bulk-dd"></div>
            </div>
            <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
                <button type="button" class="ba-apply-btn" id="ba-apply-all-btn"><i class="fa fa-check-double"></i> تطبيق على المحدد</button>
            </div>
            <div class="ba-toolbar-group" style="min-width:130px">
                <label><i class="fa fa-calendar"></i> التاريخ</label>
                <input type="date" id="ba-action-date" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="ba-toolbar-group" style="flex:1;min-width:160px">
                <label><i class="fa fa-sticky-note-o"></i> ملاحظات</label>
                <input type="text" id="ba-note" placeholder="ملاحظات مشتركة (اختياري)...">
            </div>
        </div>

        <!-- Results table with action column -->
        <div class="ba-results">
            <table>
                <thead><tr>
                    <th style="width:36px"><input type="checkbox" class="ba-check" id="ba-check-all" checked></th>
                    <th>المدخل</th><th>رقم القضية</th><th>السنة</th>
                    <th>المحكمة</th><th>العقد</th><th>الأطراف</th>
                    <th>الإجراء</th>
                    <th>ملاحظة</th>
                    <th>الحالة</th>
                </tr></thead>
                <tbody id="ba-results-body"></tbody>
            </table>
        </div>

        <div class="ba-actions">
            <button class="ba-btn ba-btn-secondary" onclick="BA.goStep(1)"><i class="fa fa-arrow-right"></i> السابق</button>
            <button class="ba-btn ba-btn-primary" id="ba-execute-btn"><i class="fa fa-rocket"></i> تأكيد وتنفيذ</button>
        </div>
    </div>

    <!-- Step 3: Execution Results -->
    <div class="ba-panel" id="ba-step3">
        <h3 style="margin:0 0 14px;font-size:18px;font-weight:700;color:#1E293B"><i class="fa fa-check-circle" style="color:#10B981;margin-left:8px"></i> نتيجة التنفيذ</h3>
        <div class="ba-progress-bar"><div class="ba-progress-fill" id="ba-progress" style="width:0%"></div></div>
        <div class="ba-summary" id="ba-summary"></div>
        <div class="ba-detail-log" id="ba-log"></div>
        <div class="ba-actions">
            <a href="<?= Url::to(['index']) ?>" class="ba-btn ba-btn-secondary"><i class="fa fa-arrow-right"></i> رجوع للقسم القانوني</a>
            <button class="ba-btn ba-btn-success" onclick="BA.reset()"><i class="fa fa-plus"></i> إدخال دفعة جديدة</button>
        </div>
    </div>
</div>

<script>
var BA = (function() {
    'use strict';
    var PARSE_URL   = '<?= $parseUrl ?>';
    var EXECUTE_URL = '<?= $executeUrl ?>';
    var CSRF_PARAM  = '<?= Yii::$app->request->csrfParam ?>';
    var CSRF_TOKEN  = '<?= Yii::$app->request->csrfToken ?>';
    var ALL_ACTIONS = <?= Json::encode($actionsJson) ?>;
    var parsedResults = [];
    var bulkSelectedAction = null;
    var openRowDd = null;

    /* ── Navigation ── */
    function goStep(n) {
        document.querySelectorAll('.ba-panel').forEach(function(p) { p.classList.remove('active'); });
        document.getElementById('ba-step' + n).classList.add('active');
        document.querySelectorAll('.ba-step').forEach(function(s, i) {
            s.classList.remove('active', 'done');
            if (i + 1 < n) s.classList.add('done');
            if (i + 1 === n) s.classList.add('active');
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /* ── Step 1: Parse ── */
    document.getElementById('ba-parse-btn').addEventListener('click', function() {
        var input = document.getElementById('ba-input').value.trim();
        if (!input) { alert('أدخل أرقام القضايا أولاً'); return; }
        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جارٍ التحليل...';
        var fd = new FormData();
        fd.append('numbers', input);
        fd.append(CSRF_PARAM, CSRF_TOKEN);
        fetch(PARSE_URL, { method:'POST', body:fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-search"></i> تحليل ومطابقة';
                if (data.success) {
                    parsedResults = data.results;
                    renderResults();
                    goStep(2);
                } else { alert('خطأ في التحليل'); }
            })
            .catch(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-search"></i> تحليل ومطابقة';
                alert('خطأ في الاتصال');
            });
    });

    /* ── Step 2: Render Table ── */
    function renderResults() {
        var matched = 0, multi = 0, notFound = 0, errors = 0;
        var html = '';
        parsedResults.forEach(function(r, i) {
            var statusClass = 'ba-status-' + r.status;
            var statusLabel = { matched:'\u2705 متطابق', multiple:'\u26A0\uFE0F متعدد', not_found:'\u274C غير موجود', error:'\u26A1 خطأ' }[r.status] || r.status;
            if (r.status === 'matched') matched++;
            else if (r.status === 'multiple') multi++;
            else if (r.status === 'not_found') notFound++;
            else errors++;
            if (r.status === 'matched' && r.parties && !r.selectedParties) {
                r.selectedParties = r.parties.map(function(p) { return p.customer_id; });
            }
            var isSelectable = r.status === 'matched' || r.status === 'multiple';
            var isChecked = isSelectable && r._checked !== false;
            html += '<tr data-idx="' + i + '">';
            html += '<td><input type="checkbox" class="ba-check ba-row-check" data-idx="' + i + '" ' + (isSelectable ? (isChecked ? 'checked' : '') : 'disabled') + '></td>';
            html += '<td style="font-family:monospace;direction:ltr">' + esc(r.input) + '</td>';
            html += '<td><strong>' + (r.number || '\u2014') + '</strong></td>';
            html += '<td>' + (r.year || '\u2014') + '</td>';
            if (r.status === 'matched') {
                html += '<td>' + esc(r.court_name) + '</td>';
                html += '<td>' + (r.contract_id || '\u2014') + '</td>';
                html += '<td class="ba-parties-list"><div class="ba-party-chips">';
                if (r.parties && r.parties.length > 1) {
                    var allSel = r.selectedParties && r.selectedParties.length === r.parties.length;
                    html += '<button type="button" class="ba-party-selectall" onclick="BA.toggleAllParties(' + i + ')">' + (allSel ? '\u2715 إلغاء الكل' : '\u2713 تحديد الكل') + '</button>';
                }
                (r.parties || []).forEach(function(p) {
                    var isSel = r.selectedParties && r.selectedParties.indexOf(p.customer_id) !== -1;
                    html += '<span class="ba-party-chip ' + (isSel ? 'selected' : '') + '" onclick="BA.toggleParty(' + i + ',' + p.customer_id + ')" title="' + (p.customer_type === 'client' ? 'مدين' : 'كفيل') + '"><span class="chip-check">' + (isSel ? '\u2611' : '\u2610') + '</span> ' + esc(p.name) + '</span>';
                });
                html += '</div></td>';
                html += '<td>' + renderRowActionChip(i) + '</td>';
                html += '<td><input type="text" class="ba-row-note-input" data-idx="' + i + '" placeholder="ملاحظة..." value="' + escAttr(r.rowNote || '') + '" onchange="BA.setRowNote(' + i + ',this.value)"></td>';
            } else if (r.status === 'multiple') {
                html += '<td><select class="ba-court-select" data-idx="' + i + '" onchange="BA.resolveMultiple(' + i + ', this.value)">';
                html += '<option value="">\u2014 اختر المحكمة \u2014</option>';
                (r.options || []).forEach(function(o, oi) {
                    html += '<option value="' + oi + '">' + esc(o.court_name) + ' (عقد ' + o.contract_id + ')</option>';
                });
                html += '</select></td>';
                html += '<td>\u2014</td><td>\u2014</td><td>\u2014</td><td>\u2014</td>';
            } else {
                html += '<td colspan="5" style="color:#94A3B8">' + esc(r.message) + '</td>';
            }
            html += '<td><span class="ba-status ' + statusClass + '">' + statusLabel + '</span></td>';
            html += '</tr>';
        });
        document.getElementById('ba-results-body').innerHTML = html;
        document.getElementById('ba-stats').innerHTML =
            '<div class="ba-stat ba-stat-total"><i class="fa fa-list"></i> الكل: ' + parsedResults.length + '</div>' +
            '<div class="ba-stat ba-stat-ok"><i class="fa fa-check"></i> متطابق: ' + matched + '</div>' +
            (multi > 0 ? '<div class="ba-stat ba-stat-warn"><i class="fa fa-question-circle"></i> متعدد: ' + multi + '</div>' : '') +
            (notFound > 0 ? '<div class="ba-stat ba-stat-err"><i class="fa fa-times"></i> غير موجود: ' + notFound + '</div>' : '') +
            (errors > 0 ? '<div class="ba-stat ba-stat-err"><i class="fa fa-exclamation-triangle"></i> أخطاء: ' + errors + '</div>' : '');
        syncCheckAll();
    }

    function renderRowActionChip(idx) {
        var r = parsedResults[idx];
        if (!r || r.status !== 'matched') return '';
        var act = r.selectedAction;
        if (act) {
            return '<span class="ba-row-action-chip has-action" onclick="BA.openRowActionDd(' + idx + ', event)">'
                + '<span class="ba-chip-bullet" style="background:' + esc(act.color) + '"></span>'
                + esc(act.name)
                + '<span class="ba-chip-x" onclick="event.stopPropagation();BA.clearRowAction(' + idx + ')">&times;</span>'
                + '</span>';
        }
        return '<span class="ba-row-action-chip" onclick="BA.openRowActionDd(' + idx + ', event)">'
            + '<i class="fa fa-plus" style="font-size:10px;color:#94A3B8"></i> تعيين إجراء'
            + '</span>';
    }

    function setRowNote(idx, val) {
        parsedResults[idx].rowNote = val;
    }

    /* ── Per-row action dropdown ── */
    function openRowActionDd(idx, evt) {
        evt.stopPropagation();
        closeAllDd();
        var chip = evt.currentTarget;
        var rect = chip.getBoundingClientRect();
        var dd = document.createElement('div');
        dd.className = 'ba-row-dd open';
        dd.innerHTML = buildActionDdHtml('BA.pickRowAction(' + idx + ',');
        dd.style.top = (rect.bottom + 4) + 'px';
        dd.style.right = (window.innerWidth - rect.right) + 'px';
        document.body.appendChild(dd);
        openRowDd = dd;
        var search = dd.querySelector('.ba-row-dd-search');
        if (search) {
            search.focus();
            search.addEventListener('input', function() { filterDdItems(dd, this.value); });
        }
    }

    function pickRowAction(idx, actionId) {
        var act = ALL_ACTIONS.filter(function(a) { return a.id == actionId; })[0];
        if (act) parsedResults[idx].selectedAction = act;
        closeAllDd();
        renderResults();
    }

    function clearRowAction(idx) {
        parsedResults[idx].selectedAction = null;
        renderResults();
    }

    /* ── Bulk action dropdown ── */
    var bulkInput = document.getElementById('ba-bulk-action-input');
    var bulkDd    = document.getElementById('ba-bulk-dd');

    bulkInput.addEventListener('focus', function() {
        bulkDd.innerHTML = buildActionDdHtml('BA.pickBulkAction(');
        bulkDd.classList.add('open');
    });
    bulkInput.addEventListener('input', function() {
        filterDdItems(bulkDd, this.value);
    });

    function pickBulkAction(actionId) {
        var act = ALL_ACTIONS.filter(function(a) { return a.id == actionId; })[0];
        if (act) {
            bulkSelectedAction = act;
            bulkInput.value = act.name;
        }
        bulkDd.classList.remove('open');
    }

    document.getElementById('ba-apply-all-btn').addEventListener('click', function() {
        if (!bulkSelectedAction) { alert('اختر الإجراء أولاً من القائمة'); return; }
        var applied = 0;
        document.querySelectorAll('.ba-row-check:checked').forEach(function(c) {
            var idx = parseInt(c.dataset.idx);
            var r = parsedResults[idx];
            if (r && r.status === 'matched') {
                r.selectedAction = bulkSelectedAction;
                applied++;
            }
        });
        renderResults();
        if (applied > 0) {
            var toast = document.createElement('div');
            toast.textContent = '\u2705 تم تعيين "' + bulkSelectedAction.name + '" لـ ' + applied + ' قضية';
            toast.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#166534;color:#fff;padding:10px 24px;border-radius:8px;font-size:13px;font-weight:600;z-index:9999;font-family:inherit';
            document.body.appendChild(toast);
            setTimeout(function() { toast.remove(); }, 2500);
        }
    });

    /* ── Shared dropdown builder ── */
    function buildActionDdHtml(onClickPrefix) {
        var html = '<input type="text" class="ba-row-dd-search" placeholder="ابحث عن الإجراء..." onclick="event.stopPropagation()">';
        var lastNature = '';
        ALL_ACTIONS.forEach(function(a) {
            if (a.nature !== lastNature) {
                html += '<div class="ba-toolbar-dd-nature">' + esc(a.natureLabel) + '</div>';
                lastNature = a.nature;
            }
            html += '<div class="ba-toolbar-dd-item" data-name="' + escAttr(a.name) + '" onclick="event.stopPropagation();' + onClickPrefix + a.id + ')">'
                + '<span class="bullet" style="background:' + a.color + '"></span>'
                + esc(a.name) + '</div>';
        });
        return html;
    }

    function filterDdItems(dd, q) {
        q = (q || '').trim();
        var qLower = q.toLowerCase();
        var qNorm = normalizeArabic(q);
        dd.querySelectorAll('.ba-toolbar-dd-item').forEach(function(el) {
            if (!q) { el.style.display = ''; return; }
            var name = el.dataset.name || '';
            var found = name.toLowerCase().indexOf(qLower) !== -1
                || normalizeArabic(name).indexOf(qNorm) !== -1;
            el.style.display = found ? '' : 'none';
        });
        dd.querySelectorAll('.ba-toolbar-dd-nature').forEach(function(hdr) {
            var next = hdr.nextElementSibling;
            var anyVisible = false;
            while (next && !next.classList.contains('ba-toolbar-dd-nature')) {
                if (next.style.display !== 'none') anyVisible = true;
                next = next.nextElementSibling;
            }
            hdr.style.display = anyVisible || !q ? '' : 'none';
        });
    }

    function normalizeArabic(s) {
        return s.replace(/[\u0610-\u061A\u064B-\u065F\u0670]/g, '')
            .replace(/[إأآ]/g, 'ا').replace(/ة/g, 'ه').replace(/ى/g, 'ي');
    }

    function closeAllDd() {
        bulkDd.classList.remove('open');
        if (openRowDd) { openRowDd.remove(); openRowDd = null; }
    }
    document.addEventListener('click', function(e) {
        if (!bulkDd.contains(e.target) && e.target !== bulkInput) bulkDd.classList.remove('open');
        if (openRowDd && !openRowDd.contains(e.target)) { openRowDd.remove(); openRowDd = null; }
    });

    /* ── Party toggles ── */
    function toggleParty(caseIdx, customerId) {
        var r = parsedResults[caseIdx];
        if (!r || !r.selectedParties) return;
        var idx = r.selectedParties.indexOf(customerId);
        if (idx !== -1) r.selectedParties.splice(idx, 1);
        else r.selectedParties.push(customerId);
        renderResults();
    }
    function toggleAllParties(caseIdx) {
        var r = parsedResults[caseIdx];
        if (!r || !r.parties) return;
        var allIds = r.parties.map(function(p) { return p.customer_id; });
        r.selectedParties = (r.selectedParties && r.selectedParties.length === allIds.length) ? [] : allIds.slice();
        renderResults();
    }
    function resolveMultiple(idx, optionIdx) {
        if (optionIdx === '' || optionIdx === undefined) return;
        var r = parsedResults[idx];
        var opt = r.options[parseInt(optionIdx)];
        r.status = 'matched';
        r.judiciary_id = opt.judiciary_id;
        r.contract_id = opt.contract_id;
        r.court_name = opt.court_name;
        r.parties = opt.parties;
        r.selectedParties = opt.parties.map(function(p) { return p.customer_id; });
        renderResults();
    }

    /* ── Check all ── */
    document.getElementById('ba-check-all').addEventListener('change', function() {
        var checked = this.checked;
        document.querySelectorAll('.ba-row-check:not(:disabled)').forEach(function(c) {
            c.checked = checked;
            var idx = parseInt(c.dataset.idx);
            if (parsedResults[idx]) parsedResults[idx]._checked = checked;
        });
    });

    function syncCheckAll() {
        var all = document.querySelectorAll('.ba-row-check:not(:disabled)');
        var allChecked = all.length > 0;
        all.forEach(function(c) { if (!c.checked) allChecked = false; });
        var checkAll = document.getElementById('ba-check-all');
        if (checkAll) checkAll.checked = allChecked;
    }

    /* ── Build cases for execution ── */
    function getSelectedCases() {
        var globalNote = document.getElementById('ba-note').value || '';
        var cases = [];
        document.querySelectorAll('.ba-row-check:checked').forEach(function(c) {
            var idx = parseInt(c.dataset.idx);
            var r = parsedResults[idx];
            if (r && r.status === 'matched' && r.judiciary_id && r.selectedAction) {
                var partyIds = (r.selectedParties && r.selectedParties.length > 0) ? r.selectedParties : (r.parties || []).map(function(p) { return p.customer_id; });
                var note = r.rowNote || globalNote;
                cases.push({
                    input: r.input,
                    judiciary_id: r.judiciary_id,
                    contract_id: r.contract_id,
                    party_ids: partyIds,
                    action_id: r.selectedAction.id,
                    note: note
                });
            }
        });
        return cases;
    }

    /* ── Execute ── */
    document.getElementById('ba-execute-btn').addEventListener('click', function() {
        var cases = getSelectedCases();
        var totalChecked = document.querySelectorAll('.ba-row-check:checked').length;
        var matchedChecked = 0;
        document.querySelectorAll('.ba-row-check:checked').forEach(function(c) {
            var idx = parseInt(c.dataset.idx);
            if (parsedResults[idx] && parsedResults[idx].status === 'matched') matchedChecked++;
        });
        var noAction = matchedChecked - cases.length;

        if (matchedChecked === 0) { alert('اختر قضية واحدة على الأقل'); return; }
        if (noAction > 0) { alert('هناك ' + noAction + ' قضية محددة بدون إجراء. عيّن الإجراء لكل قضية أو أزل التحديد عنها.'); return; }
        if (cases.length === 0) { alert('لا توجد قضايا جاهزة للتنفيذ'); return; }

        var actionSummary = {};
        cases.forEach(function(c) {
            var a = ALL_ACTIONS.filter(function(x) { return x.id == c.action_id; })[0];
            var name = a ? a.name : '#' + c.action_id;
            actionSummary[name] = (actionSummary[name] || 0) + 1;
        });
        var summaryLines = Object.keys(actionSummary).map(function(k) { return '\u25B8 ' + k + ': ' + actionSummary[k] + ' قضية'; }).join('\n');

        var msg = 'تأكيد الإدخال المجمّع:\n\n'
            + summaryLines + '\n'
            + '\u25B8 التاريخ: ' + document.getElementById('ba-action-date').value + '\n\n'
            + 'هل تريد المتابعة؟';
        if (!confirm(msg)) return;

        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جارٍ التنفيذ...';
        goStep(3);
        document.getElementById('ba-progress').style.width = '30%';

        var fd = new FormData();
        fd.append(CSRF_PARAM, CSRF_TOKEN);
        fd.append('action_date', document.getElementById('ba-action-date').value);
        fd.append('note', document.getElementById('ba-note').value);
        fd.append('cases', JSON.stringify(cases));

        fetch(EXECUTE_URL, { method:'POST', body:fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                document.getElementById('ba-progress').style.width = '100%';
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-rocket"></i> تأكيد وتنفيذ';
                document.getElementById('ba-summary').innerHTML =
                    '<div class="ba-summary-card" style="border-color:#10B981"><div class="num" style="color:#10B981">' + (data.total_saved || 0) + '</div><div class="lbl">إجراء تم إضافته</div></div>' +
                    '<div class="ba-summary-card" style="border-color:#3B82F6"><div class="num" style="color:#3B82F6">' + (data.total_cases || 0) + '</div><div class="lbl">قضية تمت معالجتها</div></div>' +
                    '<div class="ba-summary-card" style="border-color:#EF4444"><div class="num" style="color:#EF4444">' + ((data.errors || []).length) + '</div><div class="lbl">أخطاء</div></div>';
                var logHtml = '';
                (data.details || []).forEach(function(d) {
                    var icon = d.saved > 0 ? '<i class="fa fa-check-circle" style="color:#10B981"></i>' : '<i class="fa fa-times-circle" style="color:#EF4444"></i>';
                    logHtml += '<div class="ba-detail-row">' + icon + ' <span style="font-family:monospace;direction:ltr">' + esc(d.input) + '</span> <span style="color:#64748B;margin-right:auto">' + d.saved + ' إجراء</span></div>';
                });
                document.getElementById('ba-log').innerHTML = logHtml;
            })
            .catch(function() {
                document.getElementById('ba-progress').style.width = '100%';
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-rocket"></i> تأكيد وتنفيذ';
                document.getElementById('ba-summary').innerHTML = '<div style="padding:20px;text-align:center;color:#EF4444"><i class="fa fa-exclamation-triangle"></i> خطأ في التنفيذ</div>';
            });
    });

    /* ── Reset ── */
    function reset() {
        document.getElementById('ba-input').value = '';
        parsedResults = [];
        bulkSelectedAction = null;
        bulkInput.value = '';
        document.getElementById('ba-note').value = '';
        goStep(1);
    }

    function esc(s) {
        if (!s && s !== 0) return '\u2014';
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(s));
        return d.innerHTML;
    }

    function escAttr(s) {
        if (!s && s !== 0) return '';
        return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    return {
        goStep: goStep,
        toggleParty: toggleParty,
        toggleAllParties: toggleAllParties,
        resolveMultiple: resolveMultiple,
        openRowActionDd: openRowActionDd,
        pickRowAction: pickRowAction,
        clearRowAction: clearRowAction,
        pickBulkAction: pickBulkAction,
        setRowNote: setRowNote,
        reset: reset
    };
})();
</script>
