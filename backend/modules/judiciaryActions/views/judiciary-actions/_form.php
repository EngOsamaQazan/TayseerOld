<?php
/**
 * فورم إضافة/تعديل الإجراء القضائي — تصميم OCP
 * يدعم: الطبيعة + المرحلة + العلاقات (كتب مسموحة / حالات / طلبات أصلية)
 */
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;
use backend\modules\judiciaryActions\models\JudiciaryActions;

/* @var $model JudiciaryActions */

$isNew = $model->isNewRecord;

// Load all actions for relationship pickers (excluding current)
$allActions = (new \yii\db\Query())
    ->select(['id', 'name', 'action_nature'])
    ->from('os_judiciary_actions')
    ->where(['or', ['is_deleted' => 0], ['is_deleted' => null]])
    ->orderBy(['name' => SORT_ASC])
    ->all();

$requests = $documents = $statuses = $processes = [];
foreach ($allActions as $a) {
    if (!$isNew && $a['id'] == $model->id) continue;
    switch ($a['action_nature']) {
        case 'request':    $requests[$a['id']]  = $a['name']; break;
        case 'document':   $documents[$a['id']] = $a['name']; break;
        case 'doc_status': $statuses[$a['id']]  = $a['name']; break;
        default:           $processes[$a['id']] = $a['name']; break;
    }
}

$natureStyles = [
    'request'    => ['icon' => 'fa-file-text-o', 'color' => '#3B82F6', 'bg' => '#EFF6FF', 'label' => 'طلب إجرائي',    'desc' => 'طلب يُقدَّم للمحكمة وينتظر قرار (موافقة/رفض)'],
    'document'   => ['icon' => 'fa-file-o',      'color' => '#8B5CF6', 'bg' => '#F5F3FF', 'label' => 'كتاب / مذكرة',   'desc' => 'كتاب أو مذكرة تصدر بعد الموافقة على طلب'],
    'doc_status' => ['icon' => 'fa-exchange',     'color' => '#EA580C', 'bg' => '#FFF7ED', 'label' => 'حالة كتاب',      'desc' => 'حالة تتبع مسار الكتاب (تسليم، رد، حسم...)'],
    'process'    => ['icon' => 'fa-cog',          'color' => '#64748B', 'bg' => '#F1F5F9', 'label' => 'إجراء إداري',    'desc' => 'خطوة إدارية عامة (تجهيز، تسجيل...)'],
];

// Current values for multi-select
$currentAllowedDocs = $model->getAllowedDocumentIds();
$currentAllowedStatuses = $model->getAllowedStatusIds();
$currentParentRequests = $model->getParentRequestIdList();
?>

<style>
.jaf-def { direction:rtl;font-family:'Tajawal',sans-serif;font-size:13px;color:#1E293B; }
.jaf-def *,.jaf-def *:before,.jaf-def *:after { box-sizing:border-box; }

.jaf-def .sec { margin-bottom:14px;padding:14px 16px;background:#FAFBFC;border-radius:10px;border:1px solid #E2E8F0; }
.jaf-def .sec-title { font-size:13px;font-weight:700;color:#334155;margin-bottom:10px;display:flex;align-items:center;gap:6px; }

/* Nature picker cards */
.jaf-def .nature-grid { display:grid;grid-template-columns:repeat(2,1fr);gap:8px; }
.jaf-def .nature-card {
    display:flex;align-items:center;gap:10px;padding:10px 14px;
    border-radius:10px;border:2px solid #E2E8F0;background:#fff;
    cursor:pointer;transition:all .2s;
}
.jaf-def .nature-card:hover { border-color:#93C5FD;background:#F0F9FF; }
.jaf-def .nature-card.selected { box-shadow:0 0 0 3px rgba(59,130,246,.15); }
.jaf-def .nature-card-icon {
    width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;
    font-size:16px;flex-shrink:0;
}
.jaf-def .nature-card-name { font-weight:700;font-size:13px; }
.jaf-def .nature-card-desc { font-size:10px;color:#94A3B8;margin-top:1px; }

/* Input styling */
.jaf-def .fi { width:100%;padding:8px 12px;border:1px solid #D1D5DB;border-radius:8px;font-size:13px;outline:none;transition:border .2s;background:#fff;font-family:inherit; }
.jaf-def .fi:focus { border-color:#3B82F6;box-shadow:0 0 0 3px rgba(59,130,246,.08); }
.jaf-def .fl { font-size:11px;font-weight:600;color:#64748B;margin-bottom:4px;display:block; }

/* Multi-select checkboxes */
.jaf-def .ms-wrap { border:1px solid #E2E8F0;border-radius:8px;background:#fff;overflow:hidden; }
.jaf-def .ms-search { width:100%;padding:7px 10px;border:none;border-bottom:1px solid #E2E8F0;font-size:12px;outline:none;font-family:inherit;direction:rtl;background:#FAFBFC; }
.jaf-def .ms-search:focus { background:#fff;border-bottom-color:#3B82F6; }
.jaf-def .ms-list { max-height:160px;overflow-y:auto; }
.jaf-def .ms-item {
    display:flex;align-items:center;gap:8px;padding:6px 10px;
    border-bottom:1px solid #F1F5F9;font-size:12px;cursor:pointer;transition:background .15s;
}
.jaf-def .ms-item:last-child { border-bottom:none; }
.jaf-def .ms-item:hover { background:#F8FAFC; }
.jaf-def .ms-item input[type=checkbox] { accent-color:#3B82F6; }
.jaf-def .ms-item .ms-id { font-size:10px;color:#94A3B8;margin-right:auto;font-family:monospace; }

/* Conditional sections */
.jaf-def .ctx-rel { display:none; }
.jaf-def .ctx-rel.active { display:block; }
</style>

<div class="jaf-def">
<?php $form = ActiveForm::begin(['id' => 'ja-def-form']); ?>

<!-- ═══ 1. Name + Stage ═══ -->
<div class="sec">
    <div class="sec-title"><i class="fa fa-pencil" style="color:#3B82F6"></i> المعلومات الأساسية</div>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
        <div style="flex:2;min-width:200px">
            <label class="fl">اسم الإجراء *</label>
            <?= Html::activeTextInput($model, 'name', ['class' => 'fi', 'placeholder' => 'مثال: طلب حسم راتب', 'autofocus' => true]) ?>
        </div>
        <div style="flex:1;min-width:160px">
            <label class="fl">المرحلة القضائية</label>
            <?= Html::activeDropDownList($model, 'action_type', JudiciaryActions::getActionTypeList(), [
                'class' => 'fi',
                'prompt' => '— اختر المرحلة —',
            ]) ?>
        </div>
    </div>
</div>

<!-- ═══ 2. Nature Selection ═══ -->
<div class="sec">
    <div class="sec-title"><i class="fa fa-tags" style="color:#8B5CF6"></i> طبيعة الإجراء</div>
    <?= Html::activeHiddenInput($model, 'action_nature', ['id' => 'ja-nature-input']) ?>
    <div class="nature-grid">
        <?php foreach ($natureStyles as $nk => $ns): ?>
        <div class="nature-card <?= $model->action_nature === $nk ? 'selected' : '' ?>"
             data-nature="<?= $nk ?>"
             style="<?= $model->action_nature === $nk ? 'border-color:'.$ns['color'].';background:'.$ns['bg'] : '' ?>"
             onclick="JADef.selectNature('<?= $nk ?>')">
            <div class="nature-card-icon" style="background:<?= $ns['bg'] ?>;color:<?= $ns['color'] ?>">
                <i class="fa <?= $ns['icon'] ?>"></i>
            </div>
            <div>
                <div class="nature-card-name" style="color:<?= $ns['color'] ?>"><?= $ns['label'] ?></div>
                <div class="nature-card-desc"><?= $ns['desc'] ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ═══ 3. Relationships (conditional) ═══ -->

<!-- For REQUESTS: allowed documents + allowed statuses -->
<div class="sec ctx-rel" id="rel-request">
    <div class="sec-title"><i class="fa fa-link" style="color:#3B82F6"></i> العلاقات — ماذا يمكن أن يصدر بعد هذا الطلب؟</div>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
        <div style="flex:1;min-width:200px">
            <label class="fl">الكتب المسموح إصدارها بعد الموافقة</label>
            <div class="ms-wrap">
                <input type="text" class="ms-search" placeholder="ابحث في الكتب..." oninput="JADef.filterList(this)">
                <div class="ms-list" id="ms-allowed-docs">
                    <?php foreach ($documents as $did => $dname): ?>
                    <label class="ms-item" data-search-text="<?= Html::encode($dname) ?> #<?= $did ?>">
                        <input type="checkbox" name="rel_allowed_documents[]" value="<?= $did ?>" <?= in_array($did, $currentAllowedDocs) ? 'checked' : '' ?>>
                        <span><?= Html::encode($dname) ?></span>
                        <span class="ms-id">#<?= $did ?></span>
                    </label>
                    <?php endforeach; ?>
                    <?php if (empty($documents)): ?>
                    <div style="padding:10px;color:#94A3B8;text-align:center;font-size:11px">لا توجد كتب مسجلة</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div style="flex:1;min-width:200px">
            <label class="fl">الحالات المسموحة على كتب هذا الطلب</label>
            <div class="ms-wrap">
                <input type="text" class="ms-search" placeholder="ابحث في الحالات..." oninput="JADef.filterList(this)">
                <div class="ms-list" id="ms-allowed-statuses">
                    <?php foreach ($statuses as $sid => $sname): ?>
                    <label class="ms-item" data-search-text="<?= Html::encode($sname) ?> #<?= $sid ?>">
                        <input type="checkbox" name="rel_allowed_statuses[]" value="<?= $sid ?>" <?= in_array($sid, $currentAllowedStatuses) ? 'checked' : '' ?>>
                        <span><?= Html::encode($sname) ?></span>
                        <span class="ms-id">#<?= $sid ?></span>
                    </label>
                    <?php endforeach; ?>
                    <?php if (empty($statuses)): ?>
                    <div style="padding:10px;color:#94A3B8;text-align:center;font-size:11px">لا توجد حالات مسجلة</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- For DOCUMENTS: parent requests -->
<div class="sec ctx-rel" id="rel-document">
    <div class="sec-title"><i class="fa fa-link" style="color:#8B5CF6"></i> العلاقات — ما هي الطلبات التي يمكن أن يصدر بعدها هذا الكتاب؟</div>
    <label class="fl">الطلبات الأصلية المرتبطة</label>
    <div class="ms-wrap">
        <input type="text" class="ms-search" placeholder="ابحث في الطلبات..." oninput="JADef.filterList(this)">
        <div class="ms-list" id="ms-parent-requests-doc">
            <?php foreach ($requests as $rid => $rname): ?>
            <label class="ms-item" data-search-text="<?= Html::encode($rname) ?> #<?= $rid ?>">
                <input type="checkbox" name="rel_parent_request_ids[]" value="<?= $rid ?>" <?= in_array($rid, $currentParentRequests) ? 'checked' : '' ?>>
                <span><?= Html::encode($rname) ?></span>
                <span class="ms-id">#<?= $rid ?></span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- For DOC_STATUS: parent documents (shown as parent_request_ids too) -->
<div class="sec ctx-rel" id="rel-doc-status">
    <div class="sec-title"><i class="fa fa-link" style="color:#EA580C"></i> العلاقات — ما هي الكتب التي يمكن أن ترتبط بها هذه الحالة؟</div>
    <label class="fl">الكتب المرتبطة</label>
    <div class="ms-wrap">
        <input type="text" class="ms-search" placeholder="ابحث في الكتب..." oninput="JADef.filterList(this)">
        <div class="ms-list" id="ms-parent-docs-status">
            <?php foreach ($documents as $did => $dname): ?>
            <label class="ms-item" data-search-text="<?= Html::encode($dname) ?> #<?= $did ?>">
                <input type="checkbox" name="rel_parent_request_ids[]" value="<?= $did ?>" <?= in_array($did, $currentParentRequests) ? 'checked' : '' ?>>
                <span><?= Html::encode($dname) ?></span>
                <span class="ms-id">#<?= $did ?></span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Hidden fields for relationships (synced by JS before submit) -->
<?= Html::activeHiddenInput($model, 'allowed_documents', ['id' => 'ja-allowed-docs-hidden']) ?>
<?= Html::activeHiddenInput($model, 'allowed_statuses', ['id' => 'ja-allowed-statuses-hidden']) ?>
<?= Html::activeHiddenInput($model, 'parent_request_ids', ['id' => 'ja-parent-requests-hidden']) ?>

<?php if (!Yii::$app->request->isAjax): ?>
<div style="padding-top:10px">
    <?= Html::submitButton(
        $isNew ? '<i class="fa fa-plus"></i> إضافة الإجراء' : '<i class="fa fa-save"></i> حفظ التعديلات',
        ['class' => $isNew ? 'btn btn-success btn-lg' : 'btn btn-primary btn-lg', 'style' => 'border-radius:10px;font-size:14px;padding:10px 30px']
    ) ?>
</div>
<?php endif; ?>

<?php ActiveForm::end(); ?>
</div>

<script>
var JADef = (function() {
    var natureColors = <?= Json::encode(array_map(function($s) { return ['color'=>$s['color'],'bg'=>$s['bg']]; }, $natureStyles)) ?>;

    function selectNature(nature) {
        // Update UI
        $('.nature-card').removeClass('selected').css({borderColor:'#E2E8F0',background:'#fff'});
        var $card = $('.nature-card[data-nature="' + nature + '"]');
        var c = natureColors[nature];
        $card.addClass('selected').css({borderColor:c.color,background:c.bg});

        // Set hidden input
        $('#ja-nature-input').val(nature);

        // Show relevant relationship section
        $('.ctx-rel').removeClass('active');
        if (nature === 'request')    $('#rel-request').addClass('active');
        if (nature === 'document')   $('#rel-document').addClass('active');
        if (nature === 'doc_status') $('#rel-doc-status').addClass('active');
    }

    function syncBeforeSubmit() {
        var nature = $('#ja-nature-input').val();

        // allowed_documents
        var docs = [];
        if (nature === 'request') {
            $('input[name="rel_allowed_documents[]"]:checked').each(function() { docs.push($(this).val()); });
        }
        $('#ja-allowed-docs-hidden').val(docs.join(','));

        // allowed_statuses
        var stats = [];
        if (nature === 'request') {
            $('input[name="rel_allowed_statuses[]"]:checked').each(function() { stats.push($(this).val()); });
        }
        $('#ja-allowed-statuses-hidden').val(stats.join(','));

        // parent_request_ids
        var parents = [];
        $('input[name="rel_parent_request_ids[]"]:checked').each(function() { parents.push($(this).val()); });
        $('#ja-parent-requests-hidden').val(parents.join(','));
    }

    $(document).ready(function() {
        // Show current nature section on load
        var currentNature = $('#ja-nature-input').val();
        if (currentNature) {
            selectNature(currentNature);
        }

        // Sync before submit
        $('#ja-def-form').on('beforeSubmit submit', function() {
            syncBeforeSubmit();
        });
    });

    function filterList(input) {
        var q = input.value.trim().toLowerCase();
        var items = input.nextElementSibling.querySelectorAll('.ms-item');
        for (var i = 0; i < items.length; i++) {
            var text = (items[i].dataset.searchText || '').toLowerCase();
            items[i].style.display = (!q || text.indexOf(q) !== -1) ? '' : 'none';
        }
    }

    return { selectNature: selectNature, filterList: filterList };
})();
</script>
