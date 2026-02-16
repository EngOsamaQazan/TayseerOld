<?php
/**
 * القسم القانوني — شاشة موحدة بتبويبات
 * 4 تبويبات: القضايا | إجراءات الأطراف | كشف المثابرة | المحولين للشكوى
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use johnitvn\ajaxcrud\CrudAsset;

CrudAsset::register($this);
$this->title = 'القسم القانوني';
$this->params['breadcrumbs'][] = $this->title;

$activeTab = Yii::$app->request->get('tab', 'cases');
?>

<style>
/* ═══ Legal Hub — Tab System ═══ */
.lh-wrap { font-family:'Tajawal','Cairo',sans-serif; direction:rtl; }

.lh-tabs {
    display:flex; gap:0; background:#fff; border-radius:12px 12px 0 0;
    border:1px solid #E2E8F0; border-bottom:none;
    overflow-x:auto; -webkit-overflow-scrolling:touch;
}
.lh-tab {
    display:flex; align-items:center; gap:8px; padding:14px 24px;
    font-size:13px; font-weight:600; color:#64748B;
    cursor:pointer; border:none; background:none;
    border-bottom:3px solid transparent; transition:all .2s;
    white-space:nowrap; position:relative;
}
.lh-tab:hover { color:#1E293B; background:#F8FAFC; }
.lh-tab.active {
    color:#800020; border-bottom-color:#800020; background:#fff;
}
.lh-tab i { font-size:15px; }
.lh-tab .lh-badge {
    display:inline-flex; align-items:center; justify-content:center;
    min-width:22px; height:20px; padding:0 6px;
    border-radius:10px; font-size:10px; font-weight:700;
    background:#F1F5F9; color:#64748B;
}
.lh-tab.active .lh-badge { background:#800020; color:#fff; }

.lh-content {
    background:#fff; border:1px solid #E2E8F0; border-top:none;
    border-radius:0 0 12px 12px; min-height:400px;
    position:relative;
}
.lh-panel { display:none; padding:16px; }
.lh-panel.active { display:block; }
.lh-panel.loading { display:flex; align-items:center; justify-content:center; min-height:300px; }

.lh-loader {
    display:flex; flex-direction:column; align-items:center; gap:12px; color:#94A3B8;
}
.lh-loader i { font-size:28px; animation:lh-spin 1s linear infinite; }
@keyframes lh-spin { from{transform:rotate(0)} to{transform:rotate(360deg)} }

/* ═══ Dropdown & overflow fixes ═══ */
.lh-panel .jud-act-wrap, .lh-panel .jca-act-wrap { position:relative;display:inline-block; }
.lh-panel .jud-act-trigger, .lh-panel .jca-act-trigger {
    background:none;border:1px solid #E2E8F0;border-radius:6px;
    width:30px;height:28px;display:inline-flex;align-items:center;justify-content:center;
    cursor:pointer;color:#64748B;font-size:14px;transition:all .15s;padding:0;
}
.lh-panel .jud-act-trigger:hover, .lh-panel .jca-act-trigger:hover { background:#F1F5F9;color:#1E293B;border-color:#CBD5E1; }
.lh-panel .jud-act-menu, .lh-panel .jca-act-menu {
    display:none;position:fixed;left:auto;top:auto;margin:0;min-width:160px;
    background:#fff;border:1px solid #E2E8F0;border-radius:8px;
    box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:99999;padding:4px 0;
    direction:rtl;font-size:12px;
}
.lh-panel .jud-act-wrap.open .jud-act-menu,
.lh-panel .jca-act-wrap.open .jca-act-menu { display:block; }
.lh-panel .jud-act-menu a, .lh-panel .jca-act-menu a {
    display:flex;align-items:center;gap:8px;padding:7px 14px;
    color:#334155;text-decoration:none;white-space:nowrap;transition:background .12s;
}
.lh-panel .jud-act-menu a:hover, .lh-panel .jca-act-menu a:hover { background:#F1F5F9;color:#1D4ED8; }
.lh-panel .jud-act-menu a i, .lh-panel .jca-act-menu a i { width:16px;text-align:center; }
.lh-panel .jud-act-divider, .lh-panel .jca-act-divider { height:1px;background:#E2E8F0;margin:4px 0; }

/* Grid overflow fix */
.lh-panel .panel-body, .lh-panel .kv-grid-container,
.lh-panel .table-responsive, .lh-panel .kv-grid-table { overflow:visible !important; }

/* Responsive grid columns — auto-fit widths + resizable */
.lh-panel .kv-grid-table { table-layout:auto !important; }
.lh-panel .kv-grid-table td, .lh-panel .kv-grid-table th {
    white-space:nowrap; padding:8px 10px !important; font-size:12px;
}
.lh-panel .kv-grid-table td { max-width:250px; overflow:hidden; text-overflow:ellipsis; }
/* Column resizer handle */
.lh-panel .kv-grid-table th { position:relative; }
.lh-panel .kv-grid-table th .col-resize-handle {
    position:absolute; left:0; top:0; bottom:0; width:4px;
    cursor:col-resize; background:transparent; z-index:5;
}
.lh-panel .kv-grid-table th .col-resize-handle:hover,
.lh-panel .kv-grid-table th .col-resize-handle.active { background:rgba(128,0,32,.3); }
@media (max-width:992px) {
    .lh-panel .kv-grid-table { font-size:11px; }
    .lh-panel .kv-grid-table td, .lh-panel .kv-grid-table th { padding:6px 6px !important; }
}

@media (max-width:768px) {
    .lh-tab { padding:10px 14px; font-size:12px; }
    .lh-tab span.lh-tab-label { display:none; }
}
</style>

<div class="lh-wrap">
    <!-- ═══ Tabs ═══ -->
    <div class="lh-tabs">
        <button class="lh-tab <?= $activeTab === 'cases' ? 'active' : '' ?>" data-tab="cases">
            <i class="fa fa-balance-scale"></i>
            <span class="lh-tab-label">القضايا</span>
            <span class="lh-badge" id="lh-badge-cases"><?= $counter ?></span>
        </button>
        <button class="lh-tab <?= $activeTab === 'actions' ? 'active' : '' ?>" data-tab="actions">
            <i class="fa fa-legal"></i>
            <span class="lh-tab-label">إجراءات الأطراف</span>
            <span class="lh-badge" id="lh-badge-actions">—</span>
        </button>
        <button class="lh-tab <?= $activeTab === 'persistence' ? 'active' : '' ?>" data-tab="persistence">
            <i class="fa fa-line-chart"></i>
            <span class="lh-tab-label">كشف المثابرة</span>
            <span class="lh-badge" id="lh-badge-persistence">—</span>
        </button>
        <button class="lh-tab <?= $activeTab === 'legal' ? 'active' : '' ?>" data-tab="legal">
            <i class="fa fa-exchange"></i>
            <span class="lh-tab-label">المحولين للشكوى</span>
            <span class="lh-badge" id="lh-badge-legal">—</span>
        </button>
    </div>

    <!-- ═══ Tab Content ═══ -->
    <div class="lh-content">
        <!-- Tab: Cases (loaded inline since it's the default) -->
        <div class="lh-panel <?= $activeTab === 'cases' ? 'active' : '' ?>" id="lh-panel-cases" data-loaded="<?= $activeTab === 'cases' ? '1' : '0' ?>">
            <?php if ($activeTab === 'cases'): ?>
                <?= $this->render('_tab_cases', ['searchModel' => $searchModel, 'dataProvider' => $dataProvider, 'counter' => $counter]) ?>
            <?php endif; ?>
        </div>

        <!-- Tab: Party Actions (lazy) -->
        <div class="lh-panel <?= $activeTab === 'actions' ? 'active' : '' ?>" id="lh-panel-actions" data-loaded="0"></div>

        <!-- Tab: Persistence Report (lazy) -->
        <div class="lh-panel <?= $activeTab === 'persistence' ? 'active' : '' ?>" id="lh-panel-persistence" data-loaded="0"></div>

        <!-- Tab: Legal Department (lazy) -->
        <div class="lh-panel <?= $activeTab === 'legal' ? 'active' : '' ?>" id="lh-panel-legal" data-loaded="0"></div>
    </div>
</div>

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '', 'size' => Modal::SIZE_LARGE]) ?>
<?php Modal::end() ?>

<?php
$casesUrl       = Url::to(['tab-cases']);
$actionsUrl     = Url::to(['tab-actions']);
$persistenceUrl = Url::to(['tab-persistence']);
$legalUrl       = Url::to(['tab-legal']);

$js = <<<JS
(function(){
    var urls = {
        cases:       '{$casesUrl}',
        actions:     '{$actionsUrl}',
        persistence: '{$persistenceUrl}',
        legal:       '{$legalUrl}'
    };

    function loadTab(tab) {
        var \$panel = $('#lh-panel-' + tab);
        if (\$panel.data('loaded') == '1') return;

        \$panel.html('<div class="lh-loader"><i class="fa fa-spinner"></i><span>جاري التحميل...</span></div>').addClass('loading');

        $.get(urls[tab], function(html) {
            \$panel.html(html).removeClass('loading').data('loaded', '1');
            if (window._lhInitColResize) setTimeout(window._lhInitColResize, 300);
        }).fail(function() {
            \$panel.html('<div style="padding:40px;text-align:center;color:#EF4444"><i class="fa fa-exclamation-triangle"></i> حدث خطأ في التحميل</div>').removeClass('loading');
        });
    }

    // Tab click
    $(document).on('click', '.lh-tab', function() {
        var tab = $(this).data('tab');
        $('.lh-tab').removeClass('active');
        $(this).addClass('active');
        $('.lh-panel').removeClass('active');
        $('#lh-panel-' + tab).addClass('active');
        loadTab(tab);
    });

    // Custom dropdown (for both jud-act and jca-act)
    $(document).on('click', '.jud-act-trigger, .jca-act-trigger', function(e) {
        e.stopPropagation();
        var \$wrap = $(this).closest('.jud-act-wrap, .jca-act-wrap');
        var \$menu = \$wrap.find('.jud-act-menu, .jca-act-menu');
        var wasOpen = \$wrap.hasClass('open');
        $('.jud-act-wrap.open, .jca-act-wrap.open').removeClass('open');
        if (!wasOpen) {
            \$wrap.addClass('open');
            var r = this.getBoundingClientRect();
            \$menu.css({ left: r.left + 'px', top: (r.bottom + 4) + 'px' });
        }
    });
    $(document).on('click', function() {
        $('.jud-act-wrap.open, .jca-act-wrap.open').removeClass('open');
    });
    $(document).on('click', '.jud-act-menu a, .jca-act-menu a', function() {
        $('.jud-act-wrap.open, .jca-act-wrap.open').removeClass('open');
    });

    // Load active tab if not cases (default)
    var activeTab = '{$activeTab}';
    if (activeTab !== 'cases') {
        loadTab(activeTab);
    }

})();
JS;
$this->registerJs($js);
?>

<script>
// Column Resizer — inline script to avoid PHP HEREDOC variable conflicts
(function() {
    var SK = 'lh_col_widths_';

    function initResize() {
        var headers = document.querySelectorAll('.lh-panel .kv-grid-table thead th');
        if (!headers.length) return;

        headers.forEach(function(th) {
            if (th.querySelector('.col-resize-handle')) return;
            var handle = document.createElement('div');
            handle.className = 'col-resize-handle';
            th.appendChild(handle);
        });

        restoreWidths();
    }

    function restoreWidths() {
        document.querySelectorAll('.lh-panel .kv-grid-table').forEach(function(table) {
            var tid = table.id || 'default';
            try {
                var saved = localStorage.getItem(SK + tid);
                if (!saved) return;
                var widths = JSON.parse(saved);
                var ths = table.querySelectorAll('thead th');
                Object.keys(widths).forEach(function(i) {
                    var th = ths[parseInt(i)];
                    if (th) { th.style.width = widths[i]; th.style.minWidth = widths[i]; }
                });
            } catch(e) {}
        });
    }

    // Drag logic using event delegation
    var dragging = false, startX = 0, startW = 0, activeEl = null, activeTid = '';

    document.addEventListener('mousedown', function(e) {
        if (!e.target.classList.contains('col-resize-handle')) return;
        e.preventDefault();
        e.stopPropagation();
        dragging = true;
        activeEl = e.target.parentElement;
        var table = activeEl.closest('table');
        activeTid = table ? (table.id || 'default') : 'default';
        startX = e.pageX;
        startW = activeEl.offsetWidth;
        e.target.classList.add('active');
    }, true);

    document.addEventListener('mousemove', function(e) {
        if (!dragging || !activeEl) return;
        var newW = Math.max(40, startW + (startX - e.pageX));
        activeEl.style.width = newW + 'px';
        activeEl.style.minWidth = newW + 'px';
    });

    document.addEventListener('mouseup', function() {
        if (!dragging) return;
        dragging = false;
        document.querySelectorAll('.col-resize-handle.active').forEach(function(h) { h.classList.remove('active'); });
        if (activeEl) {
            try {
                var table = activeEl.closest('table');
                if (table) {
                    var widths = {};
                    table.querySelectorAll('thead th').forEach(function(th, i) { widths[i] = th.style.width || (th.offsetWidth + 'px'); });
                    localStorage.setItem(SK + activeTid, JSON.stringify(widths));
                }
            } catch(e) {}
        }
        activeEl = null;
    });

    // Expose globally for tab loading
    window._lhInitColResize = initResize;

    // Init after DOM is ready and table is rendered
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { setTimeout(initResize, 300); });
    } else {
        setTimeout(initResize, 300);
    }
})();
</script>
