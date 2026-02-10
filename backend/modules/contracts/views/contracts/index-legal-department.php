<?php
/**
 * قائمة عقود الدائرة القانونية - بناء من الصفر
 * تعرض العقود المحوّلة للدائرة القانونية مع بحث وتصدير
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;

CrudAsset::register($this);
$this->title = 'الدائرة القانونية';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="contracts-index legal-department-index">

    <?php foreach (['success' => 'check-circle', 'error' => 'exclamation-circle', 'warning' => 'exclamation-triangle'] as $type => $icon): ?>
        <?php if (Yii::$app->session->hasFlash($type)): ?>
            <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible">
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                <i class="fa fa-<?= $icon ?>"></i> <?= Yii::$app->session->getFlash($type) ?>
            </div>
        <?php endif ?>
    <?php endforeach ?>

    <?= $this->render('_legal_department_search', ['model' => $searchModel]) ?>

    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'pjax' => true,
            'summary' => '<span class="text-muted" style="font-size:12px">عرض {begin}-{end} من {totalCount} عقد</span>',
            'columns' => require __DIR__ . '/_legal_columns.php',
            'toolbar' => [
                [
                    'content' =>
                        Html::a('<i class="fa fa-plus"></i> عقد جديد', ['create'], ['class' => 'btn btn-success']) .
                        Html::a('<i class="fa fa-refresh"></i>', [''], ['data-pjax' => 1, 'class' => 'btn btn-default', 'title' => 'تحديث']) .
                        '{toggleData}{export}'
                ],
            ],
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'panel' => [
                'heading' => '<i class="fa fa-legal"></i> الدائرة القانونية <span class="badge">' . $dataCount . '</span>',
            ],
        ]) ?>
    </div>
</div>

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '']) ?>
<?php Modal::end() ?>
