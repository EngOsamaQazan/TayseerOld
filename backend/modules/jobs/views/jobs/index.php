<?php
/**
 * شاشة جهات العمل — تصميم متوافق مع فلسفة النظام
 */
use yii\helpers\Url;
use yii\helpers\Html;
use kartik\grid\GridView;
use yii\bootstrap\Modal;
use yii\helpers\ArrayHelper;
use backend\modules\jobs\models\JobsType;

$this->title = 'جهات العمل';
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile(Yii::getAlias('@web') . '/css/fin-transactions.css', ['depends' => ['yii\web\YiiAsset']]);
\johnitvn\ajaxcrud\CrudAsset::register($this);
?>

<div class="fin-page">
    <!-- ═══ شريط الأدوات ═══ -->
    <section class="fin-actions" aria-label="إجراءات">
        <div class="fin-act-group">
            <?= Html::a('<i class="fa fa-plus"></i> <span>إضافة جهة عمل</span>', ['create'], [
                'class' => 'fin-btn fin-btn--add', 'title' => 'إضافة جهة عمل جديدة',
            ]) ?>
        </div>
        <div class="fin-act-group">
            <?= Html::a('<i class="fa fa-refresh"></i> <span>تحديث</span>', ['index'], [
                'class' => 'fin-btn fin-btn--reset',
            ]) ?>
        </div>
    </section>

    <!-- ═══ بحث متقدم ═══ -->
    <?= $this->render('_search', ['model' => $searchModel]) ?>

    <!-- ═══ جدول البيانات ═══ -->
    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'summary' => '<span style="font-size:13px;color:#64748b"><i class="fa fa-building"></i> عرض <b>{begin}-{end}</b> من <b>{totalCount}</b> جهة عمل</span>',
            'pjax' => true,
            'pjaxSettings' => ['options' => ['id' => 'crud-datatable-pjax']],
            'columns' => require(__DIR__ . '/_columns.php'),
            'toolbar' => [['content' => '{toggleData}{export}']],
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'hover' => true,
            'panel' => [
                'type' => 'default',
                'heading' => '<i class="fa fa-building"></i> قائمة جهات العمل <span class="badge" style="background:#7c3aed;margin-right:6px">' . $dataProvider->totalCount . '</span>',
            ],
        ]) ?>
    </div>
</div>

<style>
.fin-page .panel { border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.fin-page .panel-heading { background: #f8fafc !important; border-bottom: 1px solid #e2e8f0; font-weight: 700; color: #334155; border-radius: 10px 10px 0 0 !important; }
.fin-page .kv-grid-table th { background: #f8fafc; font-weight: 700; font-size: 13px; color: #334155; }
.fin-page .kv-grid-table td { font-size: 13.5px; vertical-align: middle; }
</style>

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '', 'size' => Modal::SIZE_LARGE]) ?>
<?php Modal::end(); ?>
