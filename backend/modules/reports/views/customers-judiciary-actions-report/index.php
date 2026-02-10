<?php
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset; 
use johnitvn\ajaxcrud\BulkButtonWidget;

/* @var $this yii\web\View */
/* @var $searchModel app\models\CustomersJudiciaryActionsSearch */
/* @var $dataProvider yii\data\SqlDataProvider */

$this->title = 'Customers Judiciary Actions Reports';
$this->params['breadcrumbs'][] = $this->title;

CrudAsset::register($this);
echo $this->render('_search', ['model' => $searchModel]);
?>
<div class="customers-judiciary-actions-report">
    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'columns' => require(__DIR__ . '/_columns.php'),
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'panel' => [
                'type' => 'default', 
                'heading' => '<h4>' . Html::encode($this->title) . '</h4>',
            ],
        ]) ?>
    </div>
</div>
<?php Modal::begin([
    "id" => "ajaxCrudModal",
    "footer" => "", // always need it for jquery plugin
]) ?>
<?php Modal::end(); ?>
