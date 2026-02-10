<?php
/**
 * نموذج العقد - بناء من الصفر
 * يدعم العقد العادي والتضامني، بنود المخزون، جدول الأقساط
 */
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use kartik\money\MaskMoney;
use kartik\select2\Select2;
use backend\modules\customers\models\ContractsCustomers;
use backend\modules\companies\models\Companies;
use backend\widgets\ImageManagerInputWidget;
use wbraganca\dynamicform\DynamicFormWidget;

/* بيانات مرجعية */
$customers = Yii::$app->cache->getOrSet(Yii::$app->params['key_customers'], fn() => Yii::$app->db->createCommand(Yii::$app->params['customers_query'])->queryAll(), Yii::$app->params['time_duration']);
$companies = ArrayHelper::map(Companies::find()->asArray()->all(), 'id', 'name');
$isNew = $model->isNewRecord;
$imgRandId = rand(100000000, 1000000000);
$model->image_manager_id = $imgRandId;
if ($isNew) {
    $model->type = 'normal';
    $model->Date_of_sale = date('Y-m-d');
    if (defined('\backend\modules\contracts\models\Contracts::DEFAUULT_TOTAL_VALUE'))
        $model->total_value = \backend\modules\contracts\models\Contracts::DEFAUULT_TOTAL_VALUE;
    if (defined('\backend\modules\contracts\models\Contracts::MONTHLY_INSTALLMENT_VALE'))
        $model->monthly_installment_value = \backend\modules\contracts\models\Contracts::MONTHLY_INSTALLMENT_VALE;
}
?>

<div class="contracts-form">
    <?php $form = ActiveForm::begin(['id' => 'dynamic-form2']) ?>

    <!-- ═══ القسم 1: العميل ونوع العقد ═══ -->
    <fieldset>
        <legend><i class="fa fa-user-circle"></i> العميل والعقد</legend>
        <div class="row">
            <div class="col-md-4" id="normal_contract">
                <?= $form->field($model, 'customer_id')->widget(Select2::class, [
                    'data' => ArrayHelper::map($customers, 'id', 'name'),
                    'options' => ['placeholder' => 'اختر العميل'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                    'pluginEvents' => ['change' => "function(){getCustomerData($('#contracts-customer_id').select2('data')[0].id)}"],
                ])->label('العميل') ?>
            </div>
            <div class="col-md-4" id="solidarity_contract" style="display:none">
                <?= $form->field($model, 'customers_ids')->widget(Select2::class, [
                    'data' => ArrayHelper::map($customers, 'id', 'name'),
                    'options' => ['placeholder' => 'اختر العملاء (تضامني)', 'multiple' => true],
                    'pluginOptions' => ['allowClear' => true, 'minimumSelectionLength' => 2, 'dir' => 'rtl'],
                ])->label('العملاء (تضامني)') ?>
            </div>
            <div class="col-md-2">
                <?= $form->field($model, 'type', ['inputOptions' => ['id' => 'contract_type']])->dropDownList(['normal' => 'عادي', 'solidarity' => 'تضامني'])->label('نوع العقد') ?>
            </div>
            <div class="col-md-3">
                <?php
                $guarantorData = ContractsCustomers::find()->select(['c.name'])->alias('cc')
                    ->innerJoin('{{%customers}} c', 'c.id=cc.customer_id')
                    ->where(['cc.contract_id' => $model->id, 'cc.customer_type' => 'guarantor'])
                    ->createCommand()->queryAll();
                $model->guarantors_ids = $guarantorData;
                ?>
                <?= $form->field($model, 'guarantors_ids')->widget(Select2::class, [
                    'data' => ArrayHelper::map($customers, 'id', 'name'),
                    'value' => $guarantorData,
                    'options' => ['placeholder' => 'اختر الكفلاء', 'multiple' => true],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('الكفلاء') ?>
            </div>
            <div class="col-md-1" id="updateCustomer" style="display:none">
                <div style="margin-top:25px">
                    <button type="button" class="btn btn-warning btn-xs" onclick="updateCustomer($('#contracts-customer_id').select2('data')[0].id)" title="تعديل العميل"><i class="fa fa-pencil"></i></button>
                    <button type="button" class="btn btn-info btn-xs" onclick="window.open('<?= Url::to(['/customers/create']) ?>','_blank')" title="عميل جديد"><i class="fa fa-plus"></i></button>
                </div>
            </div>
        </div>
    </fieldset>

    <!-- ═══ بيانات العميل (قراءة فقط) ═══ -->
    <div id="customer_data">
        <fieldset>
            <legend><i class="fa fa-id-card"></i> بيانات العميل</legend>
            <div class="row">
                <div class="col-md-2"><label>العقود</label><input type="text" class="form-control" id="total_contracts" readonly></div>
                <div class="col-md-3"><label>الاسم</label><input type="text" class="form-control" id="name" readonly></div>
                <div class="col-md-2"><label>الرقم الوطني</label><input type="text" class="form-control" id="id_number" readonly></div>
                <div class="col-md-2"><label>تاريخ الميلاد</label><input type="text" class="form-control" id="birth_date" readonly></div>
                <div class="col-md-3"><label>الوظيفة</label><input type="text" class="form-control" id="job_title" readonly></div>
            </div>
        </fieldset>
    </div>

    <!-- ═══ القسم 2: الشركة والتاريخ ═══ -->
    <fieldset>
        <legend><i class="fa fa-building"></i> معلومات العقد</legend>
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'company_id')->widget(Select2::class, [
                    'data' => $companies,
                    'options' => ['placeholder' => 'اختر الشركة', 'id' => 'company_id', 'data-model-id' => $model->id],
                    'pluginEvents' => ['change' => 'function(){getCompanyItems()}'],
                    'pluginOptions' => ['dir' => 'rtl'],
                ])->label('الشركة') ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'Date_of_sale')->widget(DatePicker::class, [
                    'options' => ['placeholder' => 'تاريخ البيع'],
                    'pluginOptions' => ['autoclose' => true, 'format' => 'yyyy-mm-dd'],
                ])->label('تاريخ البيع') ?>
            </div>
        </div>
    </fieldset>

    <!-- ═══ القسم 3: المالية ═══ -->
    <fieldset>
        <legend><i class="fa fa-money"></i> المعلومات المالية</legend>
        <div class="row">
            <div class="col-md-3">
                <?= $form->field($model, 'first_installment_value')->widget(MaskMoney::class, [
                    'pluginOptions' => ['prefix' => '', 'suffix' => ' د.أ', 'affixesStay' => true, 'thousands' => ',', 'decimal' => '.', 'precision' => 0, 'allowZero' => false, 'allowNegative' => false],
                    'pluginEvents' => ['change' => 'function(){installment_table()}'],
                ])->label('الدفعة الأولى') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'total_value')->widget(MaskMoney::class, [
                    'pluginOptions' => ['prefix' => '', 'suffix' => ' د.أ', 'affixesStay' => true, 'thousands' => '', 'decimal' => '.', 'precision' => 0, 'allowZero' => false, 'allowNegative' => false],
                    'pluginEvents' => ['change' => 'function(){installment_table()}'],
                ])->label('الإجمالي') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'monthly_installment_value')->widget(MaskMoney::class, [
                    'pluginOptions' => ['prefix' => '', 'suffix' => ' د.أ', 'affixesStay' => true, 'thousands' => ',', 'decimal' => '.', 'precision' => 0, 'allowZero' => false, 'allowNegative' => false],
                    'pluginEvents' => ['change' => 'function(){installment_table()}'],
                ])->label('القسط الشهري') ?>
            </div>
            <div class="col-md-3">
                <label>عدد الأقساط</label>
                <input type="text" class="form-control" id="installment_count" readonly>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'first_installment_date')->widget(DatePicker::class, [
                    'options' => ['placeholder' => 'تاريخ أول قسط'],
                    'pluginOptions' => ['autoclose' => true, 'format' => 'yyyy-mm-dd'],
                    'pluginEvents' => ['change' => 'function(){installment_table()}'],
                ])->label('تاريخ أول قسط') ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'commitment_discount')->widget(MaskMoney::class, [
                    'pluginOptions' => ['prefix' => '', 'suffix' => ' د.أ', 'affixesStay' => true, 'thousands' => ',', 'decimal' => '.', 'precision' => 0, 'allowZero' => false, 'allowNegative' => false],
                    'pluginEvents' => ['change' => 'function(){installment_table()}'],
                ])->label('خصم الالتزام') ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'loss_commitment')->textInput(['placeholder' => 'التزام الخسارة'])->label('التزام الخسارة') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <?= $form->field($model, 'notes')->textarea(['rows' => 2, 'placeholder' => 'ملاحظات'])->label('ملاحظات') ?>
            </div>
        </div>
    </fieldset>

    <!-- ═══ القسم 4: بنود المخزون ═══ -->
    <fieldset>
        <legend><i class="fa fa-cube"></i> بنود المخزون</legend>
        <?php DynamicFormWidget::begin([
            'widgetContainer' => 'dynamicform_wrapper',
            'widgetBody' => '.container-items',
            'widgetItem' => '.item',
            'limit' => 50, 'min' => 1,
            'insertButton' => '.add-item',
            'deleteButton' => '.remove-item',
            'model' => $modelContractInventoryItem[0],
            'formId' => 'dynamic-form2',
            'formFields' => ['id', 'item_id'],
        ]) ?>

        <div class="container-items">
            <?php foreach ($modelContractInventoryItem as $i => $item): ?>
                <div class="item panel panel-default">
                    <div class="panel-body">
                        <?php if (!$item->isNewRecord) echo Html::activeHiddenInput($item, "[{$i}]id") ?>
                        <div class="row">
                            <div class="col-md-10">
                                <?= $form->field($item, "[{$i}]item_id")->dropDownList(
                                    ArrayHelper::map(\backend\modules\inventoryItems\models\InventoryItems::find()->asArray()->all(), 'id', 'item_name'),
                                    ['prompt' => '-- اختر الصنف --']
                                )->label('الصنف') ?>
                            </div>
                            <div class="col-md-2">
                                <div style="margin-top:26px">
                                    <button type="button" class="add-item btn btn-success btn-xs"><i class="fa fa-plus"></i></button>
                                    <button type="button" class="remove-item btn btn-danger btn-xs"><i class="fa fa-minus"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
        <?php DynamicFormWidget::end() ?>
    </fieldset>

    <!-- ═══ القسم 5: الصور ═══ -->
    <fieldset>
        <legend><i class="fa fa-image"></i> صور العقد</legend>
        <?= $form->field($model, 'selected_image')->hiddenInput()->label(false) ?>
        <?= $form->field($model, 'image_manager_id')->hiddenInput()->label(false) ?>
        <?php if (!$isNew && !empty($model->selected_image)): ?>
            <div class="jadal-image-preview" style="margin-bottom:15px">
                <img src="<?= $model->selectedImagePath ?>" class="img-responsive" style="max-width:350px;border-radius:8px" alt="صورة العقد">
            </div>
        <?php endif ?>
        <?= $form->field($model, 'contract_images')->widget(ImageManagerInputWidget::class, [
            'aspectRatio' => 16/9, 'cropViewMode' => 1, 'showPreview' => true,
            'showDeletePickedImageConfirm' => false, 'groupName' => 'contracts',
            'contractId' => $isNew ? $imgRandId : $model->id,
        ])->label('إدارة الصور') ?>
    </fieldset>

    <!-- أزرار الحفظ -->
    <div class="jadal-form-actions">
        <?= Html::submitButton(
            $isNew ? '<i class="fa fa-print"></i> إنشاء وطباعة' : '<i class="fa fa-print"></i> حفظ وطباعة',
            ['name' => 'print', 'class' => $isNew ? 'btn btn-success btn-lg' : 'btn btn-primary btn-lg']
        ) ?>
        <?php if (!Yii::$app->request->isAjax): ?>
            <?= Html::submitButton(
                $isNew ? '<i class="fa fa-plus"></i> إنشاء' : '<i class="fa fa-save"></i> حفظ',
                ['class' => $isNew ? 'btn btn-success btn-lg' : 'btn btn-primary btn-lg']
            ) ?>
        <?php endif ?>
    </div>

    <?php ActiveForm::end() ?>

    <!-- جدول الأقساط المتوقع -->
    <fieldset style="margin-top:20px">
        <legend><i class="fa fa-calendar"></i> جدول الأقساط</legend>
        <table class="table table-striped table-bordered">
            <thead><tr><th>#</th><th>المبلغ</th><th>الشهر</th><th>السنة</th></tr></thead>
            <tbody id="installment_table_body"></tbody>
        </table>
    </fieldset>

    <?php
    /* عرض الصور المرفوعة */
    if (!$isNew) {
        $imgData = new yii\data\ArrayDataProvider([
            'key' => 'id',
            'allModels' => \backend\modules\imagemanager\models\Imagemanager::find()
                ->where(['contractId' => $model->id, 'groupName' => 'contracts'])->all(),
        ]);
        echo kartik\grid\GridView::widget([
            'id' => 'img-grid', 'dataProvider' => $imgData, 'summary' => '', 'pjax' => true, 'export' => false,
            'columns' => [[
                'class' => '\kartik\grid\DataColumn', 'attribute' => 'fileName', 'label' => 'الصورة', 'format' => 'html',
                'value' => fn($m) => Html::img(Url::to(['/images/' . ($m->fileName ?: Yii::$app->params['companies_logo'])]), ['style' => 'width:50px;height:50px;border-radius:50%']),
            ]],
            'striped' => false, 'condensed' => false, 'responsive' => false,
        ]);
    }
    ?>
</div>

<script>
function getCustomerData(id){
    $.get("<?= Url::to(['/customers/customers/customer-data']) ?>?id="+id, function(d){
        if(d){
            $('#total_contracts').val(d.contracts_info.count);
            $('#name').val(d.model.name);
            $('#id_number').val(d.model.id_number);
            $('#birth_date').val(d.model.birth_date);
            $('#job_title').val(d.model.job_title);
            $('#updateCustomer').show();
        }
    });
}
function updateCustomer(id){ window.open("<?= Url::to(['/customers/customers/update']) ?>?id="+id,'_blank'); }
function installment_table(){
    var tv=$("#contracts-total_value").val(), fv=$("#contracts-first_installment_value").val(), mv=$("#contracts-monthly_installment_value").val();
    if(tv>0 && fv>0 && mv>0){
        $('#installment_table_body').empty();
        var cnt=tv/mv, sd=new Date($('#contracts-first_installment_date').val()), y=sd.getFullYear(), m=sd.getMonth(), yo=0;
        for(var i=0;i<cnt;i++){
            var amt=(i==parseInt(cnt))? tv-(mv*i) : mv;
            if(++m>12){m=1;yo++}
            $('#installment_table_body').append('<tr><th>'+(i+1)+'</th><td>'+amt+' د.أ</td><td>'+m+'</td><td>'+(y+yo)+'</td></tr>');
        }
        $('#installment_count').val(i);
    }
}
function getCompanyItems(){
    var id=$('#company_id').val(), mid=$('#company_id').data('model-id');
    $.get("<?= Url::to(['companies/companies/get-items']) ?>?company_id="+id+"&model_id="+mid, function(d){
        var sel=Object.keys(d.selected);
        $('#inventory_item').empty().trigger("change");
        $.each(d.items, function(k,v){ $('#inventory_item').append("<option"+(sel.includes(k)?" selected":"")+" value="+k+">"+v+"</option>"); });
    });
}
</script>

<?php
$this->registerJs(<<<'JS'
$(document).on('change','#contract_type',function(){
    var t=$(this).val();
    $('#solidarity_contract').toggle(t==='solidarity');
    $('#normal_contract').toggle(t==='normal');
    $('#customer_data').toggle(t==='normal');
    $('#updateCustomer').toggle(t==='normal');
});
JS
) ?>
