<?php
/**
 * قائمة القضايا — محسّنة بـ Pjax
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use yii\widgets\Pjax;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;

CrudAsset::register($this);
$this->title = 'القضاء';
$this->params['breadcrumbs'][] = $this->title;
?>

<style>
/* ═══ Custom action dropdown — judiciary index ═══ */
.jud-act-wrap { position:relative;display:inline-block; }
.jud-act-trigger {
    background:none;border:1px solid #E2E8F0;border-radius:6px;
    width:30px;height:28px;display:inline-flex;align-items:center;justify-content:center;
    cursor:pointer;color:#64748B;font-size:14px;transition:all .15s;padding:0;
}
.jud-act-trigger:hover { background:#F1F5F9;color:#1E293B;border-color:#CBD5E1; }
.jud-act-menu {
    display:none;position:fixed;left:auto;top:auto;margin:0;min-width:160px;
    background:#fff;border:1px solid #E2E8F0;border-radius:8px;
    box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:99999;padding:4px 0;
    direction:rtl;font-size:12px;
}
.jud-act-wrap.open .jud-act-menu { display:block; }
.jud-act-menu a {
    display:flex;align-items:center;gap:8px;padding:7px 14px;
    color:#334155;text-decoration:none;white-space:nowrap;transition:background .12s;
}
.jud-act-menu a:hover { background:#F1F5F9;color:#1D4ED8; }
.jud-act-menu a i { width:16px;text-align:center; }
.jud-act-divider { height:1px;background:#E2E8F0;margin:4px 0; }

/* Ensure grid doesn't clip — overflow visible on all ancestors */
.judiciary-index, #judiciary-pjax, #ajaxCrudDatatable,
#crud-datatable .panel-body, #crud-datatable .kv-grid-container,
#crud-datatable-container, #crud-datatable .table-responsive,
.kv-grid-table { overflow:visible !important; }
</style>

<div class="judiciary-index">

    <?= $this->render('_search', ['model' => $searchModel]) ?>

    <?php Pjax::begin(['id' => 'judiciary-pjax', 'timeout' => 10000]) ?>
    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'columns' => require __DIR__ . '/_columns.php',
            'summary' => '<span class="text-muted" style="font-size:12px">عرض {begin}-{end} من {totalCount} قضية</span>',
            'pjax' => true,
            'pjaxSettings' => [
                'options' => ['id' => 'judiciary-grid-pjax'],
                'neverTimeout' => true,
            ],
            'toolbar' => [
                [
                    'content' =>
                        Html::a('<i class="fa fa-plus"></i> إضافة إجراء', ['/judiciaryCustomersActions/judiciary-customers-actions/create'], ['class' => 'btn btn-success', 'role' => 'modal-remote']) .
                        Html::a('<i class="fa fa-refresh"></i>', [''], ['data-pjax' => 1, 'class' => 'btn btn-default', 'title' => 'تحديث']) .
                        '{toggleData}{export}'
                ],
            ],
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'panel' => [
                'heading' => '<i class="fa fa-gavel"></i> القضايا <span class="badge">' . $counter . '</span>',
            ],
        ]) ?>
    </div>
    <?php Pjax::end() ?>
</div>

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '']) ?>
<?php Modal::end() ?>

<?php
$js = <<<'JS'
$(document).on('click', '.jud-act-trigger', function(e) {
    e.stopPropagation();
    var $wrap = $(this).closest('.jud-act-wrap');
    var $menu = $wrap.find('.jud-act-menu');
    var wasOpen = $wrap.hasClass('open');
    $('.jud-act-wrap.open').removeClass('open');
    if (!wasOpen) {
        $wrap.addClass('open');
        var r = this.getBoundingClientRect();
        $menu.css({ left: r.left + 'px', top: (r.bottom + 4) + 'px' });
    }
});
$(document).on('click', function() {
    $('.jud-act-wrap.open').removeClass('open');
});
$(document).on('click', '.jud-act-menu a', function() {
    $('.jud-act-wrap.open').removeClass('open');
});
JS;
$this->registerJs($js);
?>
