<?php
/**
 * قائمة العقود
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;

CrudAsset::register($this);
$this->title = 'العقود';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="contracts-index">

    <?php foreach (['success' => 'check-circle', 'error' => 'exclamation-circle', 'warning' => 'exclamation-triangle'] as $type => $icon): ?>
        <?php if (Yii::$app->session->hasFlash($type)): ?>
            <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible">
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                <i class="fa fa-<?= $icon ?>"></i> <?= Yii::$app->session->getFlash($type) ?>
            </div>
        <?php endif ?>
    <?php endforeach ?>

    <?= $this->render('_search', ['model' => $searchModel]) ?>

    <div id="ajaxCrudDatatable">
        <?= GridView::widget([
            'id' => 'crud-datatable',
            'dataProvider' => $dataProvider,
            'pjax' => false,
            'summary' => '<span class="text-muted" style="font-size:12px">عرض {begin}-{end} من أصل {totalCount} عقد</span>',
            'columns' => require __DIR__ . '/_columns.php',
            'toolbar' => [
                [
                    'content' =>
                        Html::a('<i class="fa fa-plus"></i> إضافة عقد', ['create'], ['class' => 'btn btn-success']) .
                        Html::a('<i class="fa fa-refresh"></i>', [''], ['data-pjax' => 1, 'class' => 'btn btn-default', 'title' => 'تحديث']) .
                        '{toggleData}{export}'
                ],
            ],
            'striped' => true,
            'condensed' => true,
            'responsive' => true,
            'panel' => [
                'heading' => '<i class="fa fa-file-text"></i> العقود <span class="badge">' . $dataCount . '</span>',
            ],
        ]) ?>
    </div>
</div>

<?php /* نوافذ التأكيد */ ?>
<?php Modal::begin(['id' => 'finishContractModal', 'header' => '<h4 class="modal-title"><i class="fa fa-check-circle text-success"></i> تأكيد إنهاء العقد</h4>', 'size' => Modal::SIZE_SMALL]) ?>
<div class="text-center" style="padding:15px">
    <p class="lead">هل أنت متأكد من إنهاء هذا العقد؟</p>
    <p class="text-muted">سيتم تغيير حالة العقد إلى "منتهي"</p>
    <hr>
    <a id="finishContractBtn" href="#" class="btn btn-success btn-lg"><i class="fa fa-check"></i> نعم، إنهاء</a>
    <button type="button" class="btn btn-default btn-lg" data-dismiss="modal"><i class="fa fa-times"></i> إلغاء</button>
</div>
<?php Modal::end() ?>

<?php Modal::begin(['id' => 'cancelContractModal', 'header' => '<h4 class="modal-title"><i class="fa fa-ban text-danger"></i> تأكيد إلغاء العقد</h4>', 'size' => Modal::SIZE_SMALL]) ?>
<div class="text-center" style="padding:15px">
    <p class="lead">هل أنت متأكد من إلغاء هذا العقد؟</p>
    <p class="text-danger">تحذير: لا يمكن التراجع</p>
    <hr>
    <a id="cancelContractBtn" href="#" class="btn btn-danger btn-lg"><i class="fa fa-ban"></i> نعم، إلغاء</a>
    <button type="button" class="btn btn-default btn-lg" data-dismiss="modal"><i class="fa fa-times"></i> تراجع</button>
</div>
<?php Modal::end() ?>

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '']) ?>
<?php Modal::end() ?>

<?php
$this->registerJs(<<<'JS'
$(document).on('change', '.followUpUser', function(){
    var cid = $(this).data('contract-id'), uid = $(this).val();
    if(cid && uid) $.post('/contracts/contracts/change-followed-by', {contract_id:cid, user_id:uid, _csrf:yii.getCsrfToken()});
}).on('click', '.yeas-finish', function(e){
    e.preventDefault();
    $('#finishContractBtn').attr('href', $(this).data('url'));
    $('#finishContractModal').modal('show');
}).on('click', '.yeas-cancel', function(e){
    e.preventDefault();
    $('#cancelContractBtn').attr('href', $(this).data('url'));
    $('#cancelContractModal').modal('show');
});
JS
) ?>
