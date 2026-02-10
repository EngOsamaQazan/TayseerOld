<?php
/**
 * كشف المثابره — تقرير مؤشّر المثابرة
 * @var yii\web\View $this
 * @var array $rows
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'كشف المثابره';
$this->params['breadcrumbs'][] = ['label' => 'القضاء', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$exportUrl = Url::to(['judiciary/export-cases-report']);

/* عدادات الحالات */
$countRed = $countOrange = $countGreen = 0;
foreach ($rows as $r) {
    if ($r['persistence_color'] === 'red')       $countRed++;
    elseif ($r['persistence_color'] === 'orange') $countOrange++;
    else                                          $countGreen++;
}
$total = count($rows);
$perPage = 20;
?>

<style>
.cr-page { font-family: 'Cairo','Segoe UI',Tahoma,sans-serif; direction: rtl; }
.cr-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:12px; }
.cr-header h2 { font-size:20px; font-weight:700; color:#1e293b; margin:0; }
.cr-header h2 i { color:#800020; margin-left:8px; }

/* بطاقات الإحصائيات */
.cr-stats { display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
.cr-stat {
  display:flex; align-items:center; gap:10px;
  padding:14px 20px; border-radius:10px;
  background:#fff; border:1px solid #e2e8f0;
  box-shadow:0 1px 3px rgba(0,0,0,.06);
  flex:1; min-width:160px;
}
.cr-stat-icon { width:40px; height:40px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:18px; }
.cr-stat-icon.red   { background:#fee2e2; color:#b91c1c; }
.cr-stat-icon.orange { background:#fef3c7; color:#b45309; }
.cr-stat-icon.green  { background:#dcfce7; color:#15803d; }
.cr-stat-icon.total  { background:#f0f0ff; color:#4338ca; }
.cr-stat-info { flex:1; }
.cr-stat-val { font-size:20px; font-weight:700; color:#1e293b; margin:0; line-height:1.2; }
.cr-stat-lbl { font-size:12px; color:#64748b; margin:0; }

/* أدوات */
.cr-tools { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:16px; }
.cr-search {
  padding:8px 14px; border:1px solid #e2e8f0; border-radius:8px;
  font-family:inherit; font-size:13px; min-width:260px;
  direction:rtl;
}
.cr-search:focus { outline:none; border-color:#800020; box-shadow:0 0 0 3px rgba(128,0,32,.1); }
.cr-btn {
  display:inline-flex; align-items:center; gap:6px;
  padding:8px 18px; border-radius:8px; border:none;
  font-family:inherit; font-size:13px; font-weight:600;
  cursor:pointer; transition:all .2s; text-decoration:none !important;
}
.cr-btn-export { background:#059669; color:#fff; }
.cr-btn-export:hover { background:#047857; color:#fff; }
.cr-btn-print  { background:#64748b; color:#fff; }
.cr-btn-print:hover { background:#475569; color:#fff; }
.cr-filter-btn {
  padding:6px 14px; border-radius:6px; border:1px solid #e2e8f0;
  background:#fff; color:#64748b; font-family:inherit; font-size:12px;
  font-weight:600; cursor:pointer; transition:all .2s;
}
.cr-filter-btn:hover { background:#f1f5f9; }
.cr-filter-btn.active { background:#800020; color:#fff; border-color:#800020; }

/* الجدول */
.cr-table-wrap {
  overflow-x:auto; border-radius:10px;
  box-shadow:0 1px 3px rgba(0,0,0,.06);
  border:1px solid #e2e8f0; background:#fff;
}
.cr-table {
  width:100%; border-collapse:collapse; font-size:13px; white-space:nowrap;
}
.cr-table thead th {
  background:linear-gradient(135deg,#800020,#a0003a);
  color:#fff; font-weight:700; font-size:12px;
  padding:12px 14px; text-align:right;
  position:sticky; top:0; z-index:2;
}
.cr-table tbody td {
  padding:10px 14px; border-bottom:1px solid #f1f5f9; color:#1e293b;
}
.cr-table tbody tr:hover td { background:#fdf2f4; }
.cr-table tbody tr.cr-row-red td    { border-right:3px solid #b91c1c; }
.cr-table tbody tr.cr-row-orange td { border-right:3px solid #b45309; }

/* شريط المؤشّر */
.cr-badge {
  display:inline-flex; align-items:center; gap:5px;
  padding:4px 10px; border-radius:20px; font-size:12px; font-weight:700;
  white-space:nowrap;
}
.cr-badge-red    { background:#fee2e2; color:#991b1b; }
.cr-badge-orange { background:#fef3c7; color:#92400e; }
.cr-badge-green  { background:#dcfce7; color:#166534; }

/* شريط التصفح */
.cr-pager {
  display:flex; align-items:center; justify-content:center; gap:6px;
  margin-top:16px; flex-wrap:wrap;
}
.cr-pager-btn {
  min-width:36px; height:36px; border-radius:8px; border:1px solid #e2e8f0;
  background:#fff; color:#1e293b; font-family:inherit; font-size:13px;
  font-weight:600; cursor:pointer; transition:all .15s;
  display:inline-flex; align-items:center; justify-content:center;
  padding:0 10px;
}
.cr-pager-btn:hover { background:#f1f5f9; }
.cr-pager-btn.active { background:#800020; color:#fff; border-color:#800020; }
.cr-pager-btn:disabled { opacity:.4; cursor:default; }
.cr-pager-btn.cr-pager-all {
  background:#334155; color:#fff; border-color:#334155; padding:0 16px;
}
.cr-pager-btn.cr-pager-all:hover { background:#1e293b; }
.cr-pager-btn.cr-pager-all.active { background:#800020; border-color:#800020; }
.cr-pager-info {
  font-size:12.5px; color:#64748b; margin:0 8px;
}

/* Responsive */
@media (max-width:768px) {
  .cr-stats { flex-direction:column; }
  .cr-tools { flex-direction:column; align-items:stretch; }
  .cr-search { min-width:unset; }
}
@media print {
  .cr-tools, .cr-header .cr-btn, .cr-filter-btn, .cr-pager { display:none !important; }
  .cr-table thead th { background:#800020 !important; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>

<div class="cr-page">

    <!-- الرأس -->
    <div class="cr-header">
        <h2><i class="fa fa-gavel"></i> كشف المثابره</h2>
        <div style="display:flex;gap:8px">
            <a href="<?= $exportUrl ?>" class="cr-btn cr-btn-export">
                <i class="fa fa-file-excel-o"></i> تصدير Excel
            </a>
            <button type="button" class="cr-btn cr-btn-print" onclick="window.print()">
                <i class="fa fa-print"></i> طباعة
            </button>
        </div>
    </div>

    <!-- الإحصائيات -->
    <div class="cr-stats">
        <div class="cr-stat">
            <div class="cr-stat-icon total"><i class="fa fa-gavel"></i></div>
            <div class="cr-stat-info"><p class="cr-stat-val"><?= $total ?></p><p class="cr-stat-lbl">إجمالي القضايا</p></div>
        </div>
        <div class="cr-stat">
            <div class="cr-stat-icon red"><i class="fa fa-exclamation-triangle"></i></div>
            <div class="cr-stat-info"><p class="cr-stat-val"><?= $countRed ?></p><p class="cr-stat-lbl">بحاجة اهتمام عاجل</p></div>
        </div>
        <div class="cr-stat">
            <div class="cr-stat-icon orange"><i class="fa fa-clock-o"></i></div>
            <div class="cr-stat-info"><p class="cr-stat-val"><?= $countOrange ?></p><p class="cr-stat-lbl">قريب من الاستحقاق</p></div>
        </div>
        <div class="cr-stat">
            <div class="cr-stat-icon green"><i class="fa fa-check-circle"></i></div>
            <div class="cr-stat-info"><p class="cr-stat-val"><?= $countGreen ?></p><p class="cr-stat-lbl">بحالة جيدة</p></div>
        </div>
    </div>

    <!-- أدوات البحث والفلترة -->
    <div class="cr-tools">
        <input type="text" class="cr-search" id="crSearch" placeholder="ابحث بالاسم، المحكمة، رقم القضية أو العقد..." autocomplete="off">
        <button type="button" class="cr-filter-btn active" data-filter="all">الكل (<?= $total ?>)</button>
        <button type="button" class="cr-filter-btn" data-filter="red">🔴 عاجل (<?= $countRed ?>)</button>
        <button type="button" class="cr-filter-btn" data-filter="orange">🟠 قريب (<?= $countOrange ?>)</button>
        <button type="button" class="cr-filter-btn" data-filter="green">🟢 جيد (<?= $countGreen ?>)</button>
    </div>

    <!-- الجدول -->
    <div class="cr-table-wrap">
        <table class="cr-table" id="crTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>رقم القضية</th>
                    <th>السنة</th>
                    <th>المحكمة</th>
                    <th>رقم العقد</th>
                    <th>اسم العميل</th>
                    <th>الإجراء الأخير</th>
                    <th>تاريخ آخر إجراء</th>
                    <th>مؤشّر المثابرة</th>
                    <th>آخر متابعة للعقد</th>
                    <th>آخر تشييك وظيفة</th>
                    <th>المحامي</th>
                    <th>الوظيفة</th>
                    <th>نوع الوظيفة</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="14" style="text-align:center;padding:40px;color:#94a3b8;font-size:15px">
                        <i class="fa fa-inbox" style="font-size:28px;display:block;margin-bottom:10px"></i>
                        لا توجد قضايا مطابقة
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $i => $row): ?>
                    <tr class="cr-row cr-row-<?= $row['persistence_color'] ?>" data-color="<?= $row['persistence_color'] ?>">
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= Html::encode($row['judiciary_number']) ?></strong></td>
                        <td><?= Html::encode($row['case_year']) ?></td>
                        <td><?= Html::encode($row['court_name']) ?></td>
                        <td><?= Html::encode($row['contract_id']) ?></td>
                        <td><?= Html::encode($row['customer_name']) ?></td>
                        <td><?= Html::encode($row['last_action_name']) ?></td>
                        <td><?= Html::encode($row['last_action_date']) ?></td>
                        <td>
                            <span class="cr-badge cr-badge-<?= $row['persistence_color'] ?>">
                                <?= $row['persistence_icon'] ?> <?= Html::encode($row['persistence_label']) ?>
                            </span>
                        </td>
                        <td><?= Html::encode($row['last_followup_date'] ?? '—') ?></td>
                        <td><?= Html::encode($row['last_job_check_date'] ?? '—') ?></td>
                        <td><?= Html::encode($row['lawyer_name']) ?></td>
                        <td><?= Html::encode($row['job_title'] ?? '—') ?></td>
                        <td><?= Html::encode($row['job_type'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach ?>
                <?php endif ?>
            </tbody>
        </table>
    </div>

    <!-- شريط التصفح -->
    <div class="cr-pager" id="crPager"></div>

</div>

<?php
$js = <<<JS
(function($){
    var PER_PAGE = {$perPage};
    var currentPage = 1;
    var showAll = false;
    var activeFilter = 'all';
    var searchQuery = '';

    /* الحصول على الصفوف المرئية حسب الفلتر والبحث */
    function getVisibleRows() {
        var rows = [];
        $('#crTable tbody tr.cr-row').each(function(){
            var \$r = $(this);
            var matchFilter = (activeFilter === 'all') || (\$r.data('color') === activeFilter);
            var matchSearch = !searchQuery || \$r.text().toLowerCase().indexOf(searchQuery) > -1;
            if (matchFilter && matchSearch) rows.push(\$r);
        });
        return rows;
    }

    /* تطبيق التصفح */
    function applyPagination() {
        var visibleRows = getVisibleRows();
        var totalVisible = visibleRows.length;
        var totalPages = showAll ? 1 : Math.ceil(totalVisible / PER_PAGE);

        if (currentPage > totalPages && totalPages > 0) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        /* إخفاء الكل أولاً */
        $('#crTable tbody tr.cr-row').hide();

        /* إظهار الصفوف المناسبة */
        if (showAll) {
            for (var i = 0; i < visibleRows.length; i++) {
                visibleRows[i].show();
            }
        } else {
            var start = (currentPage - 1) * PER_PAGE;
            var end = start + PER_PAGE;
            for (var i = 0; i < visibleRows.length; i++) {
                if (i >= start && i < end) visibleRows[i].show();
            }
        }

        /* بناء شريط التصفح */
        buildPager(totalVisible, totalPages);
    }

    /* بناء أزرار التصفح */
    function buildPager(totalVisible, totalPages) {
        var html = '';

        if (totalVisible === 0) {
            $('#crPager').html('');
            return;
        }

        /* زر السابق */
        if (!showAll) {
            html += '<button class="cr-pager-btn" data-action="prev"' + (currentPage <= 1 ? ' disabled' : '') + '><i class="fa fa-chevron-right"></i></button>';
        }

        /* أرقام الصفحات */
        if (!showAll && totalPages > 1) {
            var startP = Math.max(1, currentPage - 3);
            var endP = Math.min(totalPages, currentPage + 3);

            if (startP > 1) {
                html += '<button class="cr-pager-btn" data-page="1">1</button>';
                if (startP > 2) html += '<span class="cr-pager-info">...</span>';
            }
            for (var p = startP; p <= endP; p++) {
                html += '<button class="cr-pager-btn' + (p === currentPage ? ' active' : '') + '" data-page="' + p + '">' + p + '</button>';
            }
            if (endP < totalPages) {
                if (endP < totalPages - 1) html += '<span class="cr-pager-info">...</span>';
                html += '<button class="cr-pager-btn" data-page="' + totalPages + '">' + totalPages + '</button>';
            }
        }

        /* زر التالي */
        if (!showAll) {
            html += '<button class="cr-pager-btn" data-action="next"' + (currentPage >= totalPages ? ' disabled' : '') + '><i class="fa fa-chevron-left"></i></button>';
        }

        /* فاصل + معلومات */
        if (!showAll) {
            var from = ((currentPage - 1) * PER_PAGE) + 1;
            var to = Math.min(currentPage * PER_PAGE, totalVisible);
            html += '<span class="cr-pager-info">عرض ' + from + '-' + to + ' من ' + totalVisible + '</span>';
        } else {
            html += '<span class="cr-pager-info">عرض الكل: ' + totalVisible + ' سجل</span>';
        }

        /* زر الكل / تصفح */
        html += '<button class="cr-pager-btn cr-pager-all' + (showAll ? ' active' : '') + '" data-action="toggle-all">';
        html += showAll ? '<i class="fa fa-list"></i> تصفح' : '<i class="fa fa-th-list"></i> الكل';
        html += '</button>';

        $('#crPager').html(html);
    }

    /* أحداث التصفح */
    $(document).on('click', '#crPager .cr-pager-btn', function(){
        var \$b = $(this);
        if (\$b.prop('disabled')) return;

        var action = \$b.data('action');
        var page = \$b.data('page');

        if (action === 'prev') {
            currentPage--;
        } else if (action === 'next') {
            currentPage++;
        } else if (action === 'toggle-all') {
            showAll = !showAll;
            currentPage = 1;
        } else if (page) {
            currentPage = page;
        }
        applyPagination();
        /* سكرول للأعلى */
        $('html,body').animate({scrollTop: $('.cr-table-wrap').offset().top - 80}, 200);
    });

    /* بحث */
    var timer;
    $('#crSearch').on('keyup', function(){
        clearTimeout(timer);
        var q = $(this).val().toLowerCase().trim();
        timer = setTimeout(function(){
            searchQuery = q;
            currentPage = 1;
            applyPagination();
        }, 250);
    });

    /* فلتر حسب اللون */
    $(document).on('click', '.cr-filter-btn', function(){
        $('.cr-filter-btn').removeClass('active');
        $(this).addClass('active');
        activeFilter = $(this).data('filter');
        currentPage = 1;
        applyPagination();
    });

    /* التشغيل الأولي */
    applyPagination();
})(jQuery);
JS;
$this->registerJs($js, \yii\web\View::POS_READY);
?>
