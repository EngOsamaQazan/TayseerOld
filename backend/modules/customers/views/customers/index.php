<?php
/**
 * قائمة العملاء
 * يعرض جدول العملاء مع بحث متقدم وأدوات تصدير
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use common\helper\Permissions;
use backend\widgets\ExportButtons;

CrudAsset::register($this);
$this->title = 'العملاء';
$this->params['breadcrumbs'][] = $this->title;

/* Fix: allow dropdown menus to overflow outside the grid panel */
$this->registerCss('
    /* Override ALL parent containers that clip the dropdown */
    .customers-index .panel,
    .customers-index .panel-body,
    .customers-index .kv-grid-container,
    .customers-index .table-responsive,
    .customers-index .grid-view,
    .customers-index #ajaxCrudDatatable,
    .customers-index .table-bordered {
        overflow: visible !important;
    }

    /* RTL fix: force dropdown to open towards the RIGHT (center of page)
       since the action column is on the LEFT edge in RTL */
    .customers-index .dropdown-menu {
        left: 0 !important;
        right: auto !important;
        box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        border-radius: 6px;
        z-index: 9999;
    }

    .customers-index .btn-group .dropdown-toggle {
        background: #fdf0f3;
        border: 1px solid #f0c0cc;
        color: #800020;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        font-size: 14px;
        transition: all 0.2s ease;
    }
    .customers-index .btn-group .dropdown-toggle:hover,
    .customers-index .btn-group .dropdown-toggle:focus {
        background: #800020;
        color: #fff;
        border-color: #800020;
    }
    .customers-index .btn-group .dropdown-toggle .caret {
        display: none;
    }
    .customers-index .dropdown-menu > li > a {
        padding: 8px 16px;
        font-size: 13px;
        transition: background 0.15s ease;
    }
    .customers-index .dropdown-menu > li > a:hover {
        background: #fdf0f3;
        color: #800020;
    }
');
?>

<div class="customers-index">

    <?= $this->render('_search', ['model' => $searchModel]) ?>

    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'pjax' => false,
            'summary' => '<span class="text-muted" style="font-size:12px">عرض {begin}-{end} من أصل {totalCount} عميل</span>',
            'columns' => require __DIR__ . '/_columns.php',
            'toolbar' => [
                [
                    'content' =>
                        (Permissions::can(Permissions::CUST_CREATE) ?
                            Html::a('<i class="fa fa-plus"></i> إضافة عميل', ['create'], [
                                'class' => 'btn btn-success',
                            ]) : '') .
                        Html::a('<i class="fa fa-refresh"></i>', [''], [
                            'data-pjax' => 1,
                            'class' => 'btn btn-default',
                            'title' => 'تحديث',
                        ]) .
                        '{toggleData}' .
                        (Permissions::can(Permissions::CUST_EXPORT)
                            ? ExportButtons::widget(['excelRoute' => ['export-excel'], 'pdfRoute' => ['export-pdf']])
                            : '')
                ],
            ],
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'panel' => [
                'heading' => '<i class="fa fa-users"></i> العملاء <span class="badge">' . $searchCounter . '</span>',
            ],
        ]) ?>
    </div>
</div>

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '']) ?>
<?php Modal::end() ?>
