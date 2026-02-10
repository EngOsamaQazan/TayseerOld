<?php
/**
 * قائمة القضايا
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;

CrudAsset::register($this);
$this->title = 'القضاء';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="judiciary-index">

    <?= $this->render('_search', ['model' => $searchModel]) ?>

    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'columns' => require __DIR__ . '/_columns.php',
            'summary' => '<span class="text-muted" style="font-size:12px">عرض {begin}-{end} من {totalCount} قضية</span>',
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
</div>

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '']) ?>
<?php Modal::end() ?>
