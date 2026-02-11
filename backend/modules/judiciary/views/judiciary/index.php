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
