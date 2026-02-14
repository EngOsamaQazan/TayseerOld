<?php

use yii\helpers\Url;
use yii\helpers\Html;
use kartik\grid\GridView;
use yii\bootstrap\Modal;

$this->title = 'جهات العمل';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="jobs-index">

    <?= $this->render('_search', ['model' => $searchModel]) ?>

    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'summary' => '<div class="text-muted">عرض {begin}-{end} من أصل {totalCount} جهة عمل</div>',
            'pjax' => true,
            'pjaxSettings' => [
                'options' => ['id' => 'crud-datatable-pjax'],
            ],
            'columns' => require(__DIR__ . '/_columns.php'),
            'toolbar' => [
                ['content' =>
                    Html::a('<i class="fa fa-plus"></i> إضافة جهة عمل', ['create'],
                        ['class' => 'btn btn-success', 'title' => 'إضافة جهة عمل جديدة']) .
                    Html::a('<i class="fa fa-refresh"></i>', [''],
                        ['data-pjax' => 1, 'class' => 'btn btn-default', 'title' => 'تحديث']) .
                    '{toggleData}' .
                    '{export}'
                ],
            ],
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'hover' => true,
            'panel' => [
                'type' => 'default',
                'heading' => '<i class="fa fa-building"></i> قائمة جهات العمل',
            ],
        ]) ?>
    </div>
</div>
