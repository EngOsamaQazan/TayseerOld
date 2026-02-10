<?php
/**
 * نموذج القضية - بناء من الصفر
 * يشمل: بيانات القضية + إجراءات العملاء + جدول الإجراءات السابقة
 */
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use kartik\date\DatePicker;
use kartik\grid\GridView;
use backend\modules\judiciaryType\models\JudiciaryType;
use backend\modules\court\models\Court;
use backend\modules\lawyers\models\Lawyers;
use backend\modules\customers\models\ContractsCustomers;
use backend\modules\judiciaryActions\models\JudiciaryActions;
use backend\modules\JudiciaryInformAddress\model\JudiciaryInformAddress;
use backend\modules\companies\models\Companies;

/* بيانات مرجعية - دفعة واحدة */
$courts = ArrayHelper::map(Court::find()->asArray()->all(), 'id', 'name');
$types = ArrayHelper::map(JudiciaryType::find()->asArray()->all(), 'id', 'name');
$lawyers = ArrayHelper::map(Lawyers::find()->asArray()->all(), 'id', 'name');
$addresses = ArrayHelper::map(JudiciaryInformAddress::find()->asArray()->all(), 'id', 'address');
$companies = ArrayHelper::map(Companies::find()->asArray()->all(), 'id', 'name');
$isNew = $model->isNewRecord;

$form = ActiveForm::begin();
?>

<div class="judiciary-form">
    <fieldset>
        <legend><i class="fa fa-gavel"></i> بيانات القضية</legend>
        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'court_id')->widget(Select2::class, [
                    'data' => $courts,
                    'options' => ['placeholder' => 'اختر المحكمة'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('المحكمة') ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'type_id')->widget(Select2::class, [
                    'data' => $types,
                    'options' => ['placeholder' => 'نوع القضية'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('نوع القضية') ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'company_id')->widget(Select2::class, [
                    'data' => $companies,
                    'options' => ['placeholder' => 'الشركة'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('الشركة') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'lawyer_id')->widget(Select2::class, [
                    'data' => $lawyers,
                    'options' => ['placeholder' => 'اختر المحامي'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('المحامي') ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'lawyer_cost')->textInput(['type' => 'number', 'step' => '0.01', 'placeholder' => '0.00'])->label('أتعاب المحامي') ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'case_cost')->textInput(['type' => 'number', 'step' => '0.01', 'placeholder' => '0.00'])->label('رسوم القضية') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <?= $form->field($model, 'judiciary_number')->textInput(['placeholder' => 'رقم القضية'])->label('رقم القضية') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'year')->widget(Select2::class, [
                    'data' => $model->year(),
                    'options' => ['placeholder' => 'السنة'],
                    'pluginOptions' => ['allowClear' => true],
                ])->label('السنة') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'income_date')->widget(DatePicker::class, [
                    'pluginOptions' => ['autoclose' => true, 'format' => 'yyyy-mm-dd'],
                ])->label('تاريخ الورود') ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'input_method')->dropDownList(['الادخال اليدوي', 'نسبه مؤيه'])->label('طريقة الإدخال') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'judiciary_inform_address_id')->widget(Select2::class, [
                    'data' => $addresses,
                    'options' => ['placeholder' => 'الموطن المختار'],
                    'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                ])->label('الموطن المختار') ?>
            </div>
        </div>
    </fieldset>

    <!-- أزرار الحفظ -->
    <?php if (!Yii::$app->request->isAjax): ?>
        <div class="jadal-form-actions">
            <?php if ($isNew): ?>
                <?= Html::submitButton('<i class="fa fa-print"></i> إنشاء وطباعة', ['name' => 'print', 'class' => 'btn btn-success btn-lg']) ?>
            <?php endif ?>
            <?= Html::submitButton(
                $isNew ? '<i class="fa fa-plus"></i> إنشاء' : '<i class="fa fa-save"></i> حفظ',
                ['class' => $isNew ? 'btn btn-success btn-lg' : 'btn btn-primary btn-lg']
            ) ?>
            <?php if (!$isNew): ?>
                <?= Html::a('<i class="fa fa-print"></i> طباعة سندات التنفيذ', ['/judiciary/judiciary/print-case', 'id' => $model->id], ['class' => 'btn btn-info btn-lg']) ?>
            <?php endif ?>
        </div>
    <?php endif ?>

    <?php ActiveForm::end() ?>

    <!-- ═══ إجراءات العملاء (في حالة التعديل فقط) ═══ -->
    <?php if (!$isNew): ?>
        <?php
        $customerData = ContractsCustomers::find()->select(['c.id', 'c.name'])->alias('cc')
            ->innerJoin('{{%customers}} c', 'c.id=cc.customer_id')
            ->where(['cc.contract_id' => $model->contract_id])
            ->createCommand()->queryAll();
        $judiciaryActions = ArrayHelper::map(JudiciaryActions::find()->asArray()->all(), 'id', 'name');
        ?>

        <fieldset style="margin-top:20px">
            <legend><i class="fa fa-users"></i> إضافة إجراء عميل</legend>
            <div id="customerActionCollapse">
                <?php $caForm = ActiveForm::begin([
                    'method' => 'post',
                    'action' => 'customer-action?judiciary=' . $model->id . '&contract_id=' . $model->contract_id,
                    'options' => ['enctype' => 'multipart/form-data'],
                ]) ?>

                <div class="row">
                    <div class="col-md-4">
                        <?= $caForm->field($modelCustomerAction, 'customers_id')->widget(Select2::class, [
                            'data' => ArrayHelper::map($customerData, 'id', 'name'),
                            'options' => ['placeholder' => 'اختر العميل'],
                            'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                        ])->label('العميل') ?>
                    </div>
                    <div class="col-md-4">
                        <?= $caForm->field($modelCustomerAction, 'judiciary_actions_id')->widget(Select2::class, [
                            'data' => $judiciaryActions,
                            'options' => ['placeholder' => 'اختر الإجراء'],
                            'pluginOptions' => ['allowClear' => true, 'dir' => 'rtl'],
                        ])->label('الإجراء') ?>
                    </div>
                    <div class="col-md-4">
                        <?= $caForm->field($modelCustomerAction, 'action_date')->widget(DatePicker::class, [
                            'pluginOptions' => ['autoclose' => true, 'format' => 'yyyy-mm-dd'],
                        ])->label('تاريخ الإجراء') ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <?= $caForm->field($modelCustomerAction, 'image')->fileInput(['accept' => 'image/*,.pdf'])->label('مرفق') ?>
                    </div>
                    <div class="col-md-8">
                        <?= $caForm->field($modelCustomerAction, 'note')->textarea(['rows' => 2, 'placeholder' => 'ملاحظات'])->label('ملاحظات') ?>
                    </div>
                </div>
                <div class="jadal-form-actions">
                    <?= Html::submitButton('<i class="fa fa-plus"></i> إضافة إجراء', ['class' => 'btn btn-success']) ?>
                </div>

                <?php ActiveForm::end() ?>
            </div>
        </fieldset>

        <!-- جدول الإجراءات السابقة -->
        <fieldset style="margin-top:20px">
            <legend><i class="fa fa-list"></i> الإجراءات السابقة</legend>
            <?php
            $actionsDP = new yii\data\ArrayDataProvider([
                'key' => 'id',
                'allModels' => \backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions::find()
                    ->where(['judiciary_id' => $model->id])->all(),
            ]);
            echo GridView::widget([
                'id' => 'judiciary-actions-grid',
                'dataProvider' => $actionsDP,
                'summary' => '',
                'export' => false,
                'columns' => [
                    ['label' => 'العقد', 'value' => fn($m) => \common\helper\FindJudicary::findJudiciaryContract($m->judiciary_id)],
                    ['label' => 'العميل', 'value' => 'customers.name'],
                    ['attribute' => 'judiciary_actions_id', 'label' => 'الإجراء', 'value' => 'judiciaryActions.name'],
                    ['attribute' => 'note', 'label' => 'ملاحظات', 'format' => 'html', 'contentOptions' => ['style' => 'max-width:200px;word-wrap:break-word;direction:rtl']],
                    ['attribute' => 'created_by', 'label' => 'المنشئ', 'value' => 'createdBy.username'],
                    ['attribute' => 'action_date', 'label' => 'التاريخ'],
                    [
                        'class' => 'kartik\grid\ActionColumn',
                        'dropdown' => false,
                        'template' => '{update}{delete}',
                        'urlCreator' => function ($action, $m) {
                            if ($action === 'delete') return Url::to(['judiciary/delete-customer-action', 'id' => $m->id, 'judiciary' => $m->judiciary_id]);
                            return Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/update-followup-judicary-custamer-action', 'contractID' => $m->contract_id, 'id' => $m->id]);
                        },
                        'updateOptions' => ['role' => 'modal-remote', 'title' => 'تعديل', 'data-toggle' => 'tooltip'],
                        'deleteOptions' => ['role' => 'modal-remote', 'title' => 'حذف', 'data-confirm' => false, 'data-method' => false, 'data-request-method' => 'post', 'data-toggle' => 'tooltip', 'data-confirm-title' => 'تأكيد الحذف', 'data-confirm-message' => 'هل أنت متأكد من حذف هذا الإجراء؟'],
                    ],
                ],
                'striped' => true, 'condensed' => true, 'responsive' => true,
            ]);
            ?>
        </fieldset>
    <?php endif ?>
</div>
