<?php
/**
 * قائمة إجراءات العملاء القضائية - بناء من الصفر
 * تعرض جدول جميع الإجراءات مع بحث متقدم
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;

CrudAsset::register($this);
$this->title = 'إجراءات العملاء القضائية';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="judiciary-customers-actions-index">

    <?= $this->render('_search', ['model' => $searchModel]) ?>

    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'summary' => '<span class="text-muted" style="font-size:12px">عرض {begin}-{end} من {totalCount} إجراء</span>',
            'columns' => require __DIR__ . '/_columns.php',
            'toolbar' => [
                [
                    'content' =>
                        Html::a('<i class="fa fa-plus"></i> إضافة إجراء', ['create'], ['class' => 'btn btn-success']) .
                        Html::a('<i class="fa fa-refresh"></i>', [''], ['data-pjax' => 1, 'class' => 'btn btn-default', 'title' => 'تحديث']) .
                        '{toggleData}{export}'
                ],
            ],
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'panel' => [
                'heading' => '<i class="fa fa-gavel"></i> إجراءات العملاء القضائية <span class="badge">' . $searchCounter . '</span>',
            ],
        ]) ?>
    </div>
</div>

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '']) ?>
<?php Modal::end() ?>
