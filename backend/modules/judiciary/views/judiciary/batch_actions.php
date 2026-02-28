<?php
/**
 * الإدخال المجمّع الذكي للإجراءات القضائية
 * Wizard: لصق → مراجعة → إجراء → تنفيذ
 */
use yii\helpers\Url;
use yii\helpers\Html;
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
?>

<style>
.ba { font-family:'Tajawal','Cairo',sans-serif; direction:rtl; max-width:960px; margin:0 auto; }

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

/* Step 2: Results table */
.ba-results { border:1px solid #E2E8F0; border-radius:10px; overflow:hidden; }
.ba-results table { width:100%; border-collapse:collapse; font-size:12px; }
.ba-results th { background:linear-gradient(135deg,#800020,#a0003a); color:#fff; padding:10px 12px; font-weight:600; text-align:right; }
.ba-results td { padding:10px 12px; border-bottom:1px solid #F1F5F9; vertical-align:middle; }
.ba-results tr:hover td { background:#FDF2F4; }
.ba-status { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.ba-status-matched { background:#DCFCE7; color:#166534; }
.ba-status-multiple { background:#FEF3C7; color:#92400E; }
.ba-status-not_found { background:#FEE2E2; color:#991B1B; }
.ba-status-error { background:#F1F5F9; color:#64748B; }
.ba-court-select { padding:4px 8px; border:1px solid #E2E8F0; border-radius:6px; font-size:11px; font-family:inherit; }
.ba-parties-list { font-size:11px; color:#64748B; }
.ba-party-chips { display:flex; flex-wrap:wrap; gap:4px; max-width:220px; }
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
    padding:2px 6px; border-radius:4px; background:#ECFDF5; border:none;
    margin-bottom:2px;
}
.ba-check { width:16px; height:16px; cursor:pointer; accent-color:#800020; }

/* Step 3: Action selector */
.ba-action-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.ba-action-box { border:1px solid #E2E8F0; border-radius:10px; padding:14px; }
.ba-action-box label { font-size:12px; font-weight:600; color:#64748B; margin-bottom:6px; display:block; }
.ba-action-box input, .ba-action-box textarea, .ba-action-box select {
    width:100%; padding:8px 12px; border:1px solid #D1D5DB; border-radius:8px;
    font-size:13px; font-family:inherit; outline:none;
}
.ba-action-box input:focus, .ba-action-box textarea:focus { border-color:#800020; box-shadow:0 0 0 3px rgba(128,0,32,.1); }

/* Action tree (reuse from jaf) */
.ba-nature-group { margin-bottom:6px; }
.ba-nature-header {
    display:flex; align-items:center; gap:6px; padding:6px 10px;
    background:#F8FAFC; border-radius:8px 8px 0 0; border:1px solid #E2E8F0; border-bottom:none;
    font-weight:700; font-size:12px; cursor:pointer;
}
.ba-nature-list { display:flex; flex-direction:column; border:1px solid #E2E8F0; border-radius:0 0 8px 8px; max-height:180px; overflow-y:auto; }
.ba-nature-list.collapsed { display:none; }
.ba-action-item {
    display:flex; align-items:center; gap:8px; padding:6px 12px;
    border-bottom:1px solid #F1F5F9; cursor:pointer; font-size:12px; transition:all .15s;
}
.ba-action-item:last-child { border-bottom:none; }
.ba-action-item:hover { background:#F8FAFC; }
.ba-action-item.selected { background:#EFF6FF; font-weight:600; color:#1D4ED8; }
.ba-action-item .bullet { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.ba-action-search { margin-bottom:8px; }

/* Step 4: Progress */
.ba-progress-bar { width:100%; height:8px; background:#E2E8F0; border-radius:4px; overflow:hidden; margin-bottom:16px; }
.ba-progress-fill { height:100%; background:linear-gradient(90deg,#800020,#10B981); transition:width .3s; border-radius:4px; }
.ba-summary { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:20px; }
.ba-summary-card {
    padding:16px; border-radius:10px; text-align:center;
    border:1px solid #E2E8F0;
}
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
    .ba-action-grid { grid-template-columns:1fr; }
    .ba-summary { grid-template-columns:1fr; }
    .ba-steps { flex-wrap:wrap; }
}
</style>

<div class="ba">
    <!-- Steps Indicator -->
    <div class="ba-steps">
        <div class="ba-step active" data-step="1"><span class="ba-step-num">1</span> لصق الأرقام</div>
        <div class="ba-step" data-step="2"><span class="ba-step-num">2</span> مراجعة ومطابقة</div>
        <div class="ba-step" data-step="3"><span class="ba-step-num">3</span> الإجراء والتفاصيل</div>
        <div class="ba-step" data-step="4"><span class="ba-step-num">4</span> التنفيذ</div>
    </div>

    <!-- ═══ Step 1: Paste Numbers ═══ -->
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

    <!-- ═══ Step 2: Review & Match ═══ -->
    <div class="ba-panel" id="ba-step2">
        <h3 style="margin:0 0 14px;font-size:18px;font-weight:700;color:#1E293B"><i class="fa fa-check-square-o" style="color:#800020;margin-left:8px"></i> مراجعة النتائج</h3>
        <div class="ba-stats" id="ba-stats"></div>
        <div class="ba-results">
            <table>
                <thead><tr>
                    <th style="width:36px"><input type="checkbox" class="ba-check" id="ba-check-all" checked></th>
                    <th>المدخل</th><th>رقم القضية</th><th>السنة</th>
                    <th>المحكمة</th><th>العقد</th><th>الأطراف</th><th>الحالة</th>
                </tr></thead>
                <tbody id="ba-results-body"></tbody>
            </table>
        </div>
        <div class="ba-actions">
            <button class="ba-btn ba-btn-secondary" onclick="BA.goStep(1)"><i class="fa fa-arrow-right"></i> السابق</button>
            <button class="ba-btn ba-btn-primary" id="ba-next2"><i class="fa fa-arrow-left"></i> التالي — اختيار الإجراء</button>
        </div>
    </div>

    <!-- ═══ Step 3: Select Action ═══ -->
    <div class="ba-panel" id="ba-step3">
        <h3 style="margin:0 0 14px;font-size:18px;font-weight:700;color:#1E293B"><i class="fa fa-gavel" style="color:#800020;margin-left:8px"></i> اختيار الإجراء</h3>
        <p style="color:#64748B;font-size:13px;margin-bottom:14px">سيتم تطبيق الإجراء على <strong id="ba-case-count">0</strong> قضية</p>

        <div class="ba-action-grid">
            <div class="ba-action-box" style="grid-column:span 2">
                <label><i class="fa fa-sitemap"></i> الإجراء القضائي</label>
                <input type="text" class="ba-action-search" id="ba-action-search" placeholder="ابحث في الإجراءات...">
                <input type="hidden" id="ba-action-id" value="">
                <div id="ba-action-tree" style="max-height:250px;overflow-y:auto">
                    <?php
                    $natureOrder = ['request', 'document', 'doc_status', 'process'];
                    foreach ($natureOrder as $nature):
                        if (empty($grouped[$nature])) continue;
                    ?>
                    <div class="ba-nature-group">
                        <div class="ba-nature-header" onclick="BA.toggleNature(this)">
                            <i class="fa <?= $natureIcons[$nature] ?>" style="color:<?= $natureColors[$nature] ?>"></i>
                            <span style="color:<?= $natureColors[$nature] ?>"><?= $natureLabels[$nature] ?></span>
                            <span style="font-weight:400;color:#94A3B8;font-size:11px" data-count="<?= count($grouped[$nature]) ?>">(<?= count($grouped[$nature]) ?>)</span>
                        </div>
                        <div class="ba-nature-list collapsed">
                            <?php foreach ($grouped[$nature] as $a): ?>
                            <div class="ba-action-item" data-id="<?= $a->id ?>" onclick="BA.selectAction(this)">
                                <span class="bullet" style="background:<?= $natureColors[$nature] ?>"></span>
                                <?= Html::encode($a->name) ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div id="ba-selected-action" style="display:none;margin-top:8px;padding:8px 12px;background:#EFF6FF;border-radius:8px;font-size:13px;font-weight:600;color:#1D4ED8">
                    <i class="fa fa-check-circle"></i> <span></span>
                </div>
            </div>

            <div class="ba-action-box">
                <label><i class="fa fa-calendar"></i> تاريخ الإجراء</label>
                <input type="date" id="ba-action-date" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="ba-action-box">
                <label><i class="fa fa-users"></i> الأطراف</label>
                <p style="font-size:12px;color:#64748B;margin:0;padding:8px 0">
                    <i class="fa fa-info-circle"></i> تم تحديد الأطراف لكل قضية في الخطوة السابقة
                </p>
            </div>
            <div class="ba-action-box" style="grid-column:span 2">
                <label><i class="fa fa-sticky-note-o"></i> ملاحظات (اختياري)</label>
                <textarea id="ba-note" rows="2" placeholder="ملاحظات مشتركة لجميع الإجراءات..." style="resize:vertical;min-height:50px"></textarea>
            </div>
        </div>

        <div class="ba-actions">
            <button class="ba-btn ba-btn-secondary" onclick="BA.goStep(2)"><i class="fa fa-arrow-right"></i> السابق</button>
            <button class="ba-btn ba-btn-primary" id="ba-execute-btn"><i class="fa fa-rocket"></i> تأكيد وتنفيذ</button>
        </div>
    </div>

    <!-- ═══ Step 4: Execution ═══ -->
    <div class="ba-panel" id="ba-step4">
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
    var PARSE_URL = '<?= $parseUrl ?>';
    var EXECUTE_URL = '<?= $executeUrl ?>';
    var parsedResults = [];
    var selectedAction = null;

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

    // Step 1: Parse
    document.getElementById('ba-parse-btn').addEventListener('click', function() {
        var input = document.getElementById('ba-input').value.trim();
        if (!input) { alert('أدخل أرقام القضايا أولاً'); return; }

        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جارٍ التحليل...';

        var formData = new FormData();
        formData.append('numbers', input);
        formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

        fetch(PARSE_URL, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-search"></i> تحليل ومطابقة';
                if (data.success) {
                    parsedResults = data.results;
                    renderResults();
                    goStep(2);
                } else {
                    alert('خطأ في التحليل');
                }
            })
            .catch(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-search"></i> تحليل ومطابقة';
                alert('خطأ في الاتصال');
            });
    });

    function renderResults() {
        var matched = 0, multi = 0, notFound = 0, errors = 0;
        var html = '';

        parsedResults.forEach(function(r, i) {
            var statusClass = 'ba-status-' + r.status;
            var statusLabel = { matched: '✅ متطابق', multiple: '⚠️ متعدد', not_found: '❌ غير موجود', error: '⚡ خطأ' }[r.status] || r.status;
            if (r.status === 'matched') matched++;
            else if (r.status === 'multiple') multi++;
            else if (r.status === 'not_found') notFound++;
            else errors++;

            // Initialize selectedParties for matched cases (all selected by default)
            if (r.status === 'matched' && r.parties && !r.selectedParties) {
                r.selectedParties = r.parties.map(function(p) { return p.customer_id; });
            }

            html += '<tr data-idx="' + i + '">';
            html += '<td><input type="checkbox" class="ba-check ba-row-check" data-idx="' + i + '" ' + (r.status === 'matched' || r.status === 'multiple' ? 'checked' : 'disabled') + '></td>';
            html += '<td style="font-family:monospace;direction:ltr">' + esc(r.input) + '</td>';
            html += '<td><strong>' + (r.number || '—') + '</strong></td>';
            html += '<td>' + (r.year || '—') + '</td>';

            if (r.status === 'matched') {
                html += '<td>' + esc(r.court_name) + '</td>';
                html += '<td>' + (r.contract_id || '—') + '</td>';
                // Party chips with multi-select
                html += '<td class="ba-parties-list"><div class="ba-party-chips">';
                if (r.parties && r.parties.length > 1) {
                    var allSelected = r.selectedParties && r.selectedParties.length === r.parties.length;
                    html += '<button type="button" class="ba-party-selectall" onclick="BA.toggleAllParties(' + i + ')">'
                        + (allSelected ? '✕ إلغاء الكل' : '✓ تحديد الكل') + '</button>';
                }
                (r.parties || []).forEach(function(p) {
                    var isSel = r.selectedParties && r.selectedParties.indexOf(p.customer_id) !== -1;
                    var typeLabel = p.customer_type === 'client' ? 'مدين' : 'كفيل';
                    html += '<span class="ba-party-chip ' + (isSel ? 'selected' : '') + '" '
                        + 'onclick="BA.toggleParty(' + i + ',' + p.customer_id + ')" '
                        + 'title="' + typeLabel + '">'
                        + '<span class="chip-check">' + (isSel ? '☑' : '☐') + '</span> '
                        + esc(p.name)
                        + '</span>';
                });
                html += '</div></td>';
            } else if (r.status === 'multiple') {
                html += '<td><select class="ba-court-select" data-idx="' + i + '" onchange="BA.resolveMultiple(' + i + ', this.value)">';
                html += '<option value="">— اختر المحكمة —</option>';
                (r.options || []).forEach(function(o, oi) {
                    html += '<option value="' + oi + '">' + esc(o.court_name) + ' (عقد ' + o.contract_id + ')</option>';
                });
                html += '</select></td>';
                html += '<td>—</td><td>—</td>';
            } else {
                html += '<td colspan="3" style="color:#94A3B8">' + esc(r.message) + '</td>';
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
    }

    function toggleParty(caseIdx, customerId) {
        var r = parsedResults[caseIdx];
        if (!r || !r.selectedParties) return;
        var idx = r.selectedParties.indexOf(customerId);
        if (idx !== -1) {
            r.selectedParties.splice(idx, 1);
        } else {
            r.selectedParties.push(customerId);
        }
        renderResults();
    }

    function toggleAllParties(caseIdx) {
        var r = parsedResults[caseIdx];
        if (!r || !r.parties) return;
        var allIds = r.parties.map(function(p) { return p.customer_id; });
        if (r.selectedParties && r.selectedParties.length === allIds.length) {
            r.selectedParties = [];
        } else {
            r.selectedParties = allIds.slice();
        }
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

    // Check all
    document.getElementById('ba-check-all').addEventListener('change', function() {
        var checked = this.checked;
        document.querySelectorAll('.ba-row-check:not(:disabled)').forEach(function(c) { c.checked = checked; });
    });

    // Step 2 → 3
    document.getElementById('ba-next2').addEventListener('click', function() {
        var selected = getSelectedCases();
        if (selected.length === 0) { alert('اختر قضية واحدة على الأقل'); return; }
        document.getElementById('ba-case-count').textContent = selected.length;
        goStep(3);
    });

    function getSelectedCases() {
        var cases = [];
        document.querySelectorAll('.ba-row-check:checked').forEach(function(c) {
            var idx = parseInt(c.dataset.idx);
            var r = parsedResults[idx];
            if (r && r.status === 'matched' && r.judiciary_id) {
                var partyIds = (r.selectedParties && r.selectedParties.length > 0) ? r.selectedParties : (r.parties || []).map(function(p) { return p.customer_id; });
                cases.push({
                    input: r.input,
                    judiciary_id: r.judiciary_id,
                    contract_id: r.contract_id,
                    party_ids: partyIds
                });
            }
        });
        return cases;
    }

    // Action tree
    function toggleNature(el) {
        var list = el.nextElementSibling;
        list.classList.toggle('collapsed');
    }

    function selectAction(el) {
        document.querySelectorAll('.ba-action-item').forEach(function(a) { a.classList.remove('selected'); });
        el.classList.add('selected');
        selectedAction = el.dataset.id;
        document.getElementById('ba-action-id').value = selectedAction;
        var sel = document.getElementById('ba-selected-action');
        sel.style.display = 'block';
        sel.querySelector('span').textContent = el.textContent.trim();
    }

    // Action search
    document.getElementById('ba-action-search').addEventListener('input', function() {
        var q = this.value.trim().toLowerCase();
        document.querySelectorAll('.ba-nature-list').forEach(function(l) { l.classList.remove('collapsed'); });
        if (!q) {
            document.querySelectorAll('.ba-action-item').forEach(function(a) { a.style.display = ''; });
            document.querySelectorAll('.ba-nature-list').forEach(function(l) { l.classList.add('collapsed'); });
            document.querySelectorAll('.ba-nature-group').forEach(function(g) { g.style.display = ''; });
            return;
        }
        document.querySelectorAll('.ba-action-item').forEach(function(a) {
            a.style.display = a.textContent.trim().toLowerCase().indexOf(q) !== -1 ? '' : 'none';
        });
        document.querySelectorAll('.ba-nature-group').forEach(function(g) {
            var visible = g.querySelectorAll('.ba-action-item:not([style*="display: none"])').length;
            g.style.display = visible > 0 ? '' : 'none';
        });
    });

    // Step 3: Execute
    document.getElementById('ba-execute-btn').addEventListener('click', function() {
        if (!selectedAction) { alert('اختر الإجراء القضائي'); return; }
        var cases = getSelectedCases();
        if (cases.length === 0) { alert('لا توجد قضايا محددة'); return; }

        var actionName = document.querySelector('.ba-action-item.selected') ? document.querySelector('.ba-action-item.selected').textContent.trim() : '';
        var msg = 'تأكيد الإدخال المجمّع:\n\n'
            + '▸ عدد القضايا: ' + cases.length + '\n'
            + '▸ الإجراء: ' + actionName + '\n'
            + '▸ التاريخ: ' + document.getElementById('ba-action-date').value + '\n\n'
            + 'هل تريد المتابعة؟';
        if (!confirm(msg)) return;

        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جارٍ التنفيذ...';
        goStep(4);
        document.getElementById('ba-progress').style.width = '30%';

        var formData = new FormData();
        formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');
        formData.append('action_id', selectedAction);
        formData.append('action_date', document.getElementById('ba-action-date').value);
        formData.append('note', document.getElementById('ba-note').value);
        formData.append('cases', JSON.stringify(cases));

        fetch(EXECUTE_URL, { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                document.getElementById('ba-progress').style.width = '100%';
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-rocket"></i> تأكيد وتنفيذ';

                var totalCases = data.total_cases || 0;
                var totalSaved = data.total_saved || 0;
                var totalErrors = (data.errors || []).length;

                document.getElementById('ba-summary').innerHTML =
                    '<div class="ba-summary-card" style="border-color:#10B981"><div class="num" style="color:#10B981">' + totalSaved + '</div><div class="lbl">إجراء تم إضافته</div></div>' +
                    '<div class="ba-summary-card" style="border-color:#3B82F6"><div class="num" style="color:#3B82F6">' + totalCases + '</div><div class="lbl">قضية تمت معالجتها</div></div>' +
                    '<div class="ba-summary-card" style="border-color:#EF4444"><div class="num" style="color:#EF4444">' + totalErrors + '</div><div class="lbl">أخطاء</div></div>';

                var logHtml = '';
                (data.details || []).forEach(function(d) {
                    var icon = d.saved > 0 ? '<i class="fa fa-check-circle" style="color:#10B981"></i>' : '<i class="fa fa-times-circle" style="color:#EF4444"></i>';
                    logHtml += '<div class="ba-detail-row">' + icon + ' <span style="font-family:monospace;direction:ltr">' + esc(d.input) + '</span> <span style="color:#64748B;margin-right:auto">' + d.saved + ' إجراء</span></div>';
                });
                document.getElementById('ba-log').innerHTML = logHtml;
            })
            .catch(function(err) {
                document.getElementById('ba-progress').style.width = '100%';
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-rocket"></i> تأكيد وتنفيذ';
                document.getElementById('ba-summary').innerHTML = '<div style="padding:20px;text-align:center;color:#EF4444"><i class="fa fa-exclamation-triangle"></i> خطأ في التنفيذ</div>';
            });
    });

    function reset() {
        document.getElementById('ba-input').value = '';
        parsedResults = [];
        selectedAction = null;
        document.getElementById('ba-action-id').value = '';
        document.getElementById('ba-selected-action').style.display = 'none';
        document.querySelectorAll('.ba-action-item').forEach(function(a) { a.classList.remove('selected'); });
        goStep(1);
    }

    function esc(s) {
        if (!s && s !== 0) return '—';
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(s));
        return d.innerHTML;
    }

    return {
        goStep: goStep,
        toggleNature: toggleNature,
        selectAction: selectAction,
        resolveMultiple: resolveMultiple,
        toggleParty: toggleParty,
        toggleAllParties: toggleAllParties,
        reset: reset
    };
})();
</script>
