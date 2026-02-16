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
        \johnitvn\ajaxcrud\CrudAsset::register($this);

        $natureStyles = [
            'request'    => ['icon' => 'fa-file-text-o', 'color' => '#3B82F6', 'bg' => '#EFF6FF', 'label' => 'طلب إجرائي'],
            'document'   => ['icon' => 'fa-file-o',      'color' => '#8B5CF6', 'bg' => '#F5F3FF', 'label' => 'كتاب / مذكرة'],
            'doc_status' => ['icon' => 'fa-exchange',     'color' => '#EA580C', 'bg' => '#FFF7ED', 'label' => 'حالة كتاب'],
            'process'    => ['icon' => 'fa-cog',          'color' => '#64748B', 'bg' => '#F1F5F9', 'label' => 'إجراء إداري'],
        ];
        $statusColors = ['pending' => '#F59E0B', 'approved' => '#10B981', 'rejected' => '#EF4444'];
        $statusLabels = ['pending' => 'معلق', 'approved' => 'موافقة', 'rejected' => 'مرفوض'];
        ?>

        <style>
        .jca-act-wrap { position:relative;display:inline-block; }
        .jca-act-trigger {
            background:none;border:1px solid #E2E8F0;border-radius:6px;
            width:30px;height:28px;display:inline-flex;align-items:center;justify-content:center;
            cursor:pointer;color:#64748B;font-size:14px;transition:all .15s;padding:0;
        }
        .jca-act-trigger:hover { background:#F1F5F9;color:#1E293B;border-color:#CBD5E1; }
        .jca-act-menu {
            display:none;position:fixed;left:auto;top:auto;margin:0;min-width:160px;
            background:#fff;border:1px solid #E2E8F0;border-radius:8px;
            box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:99999;padding:4px 0;
            direction:rtl;font-size:12px;
        }
        .jca-act-wrap.open .jca-act-menu { display:block; }
        .jca-act-menu a {
            display:flex;align-items:center;gap:8px;padding:7px 14px;
            color:#334155;text-decoration:none;white-space:nowrap;transition:background .12s;
        }
        .jca-act-menu a:hover { background:#F1F5F9;color:#1D4ED8; }
        .jca-act-menu a i { width:16px;text-align:center; }
        .jca-act-divider { height:1px;background:#E2E8F0;margin:4px 0; }
        #os_judiciary_customers_actions .panel-body,
        #os_judiciary_customers_actions .kv-grid-container,
        #os_judiciary_customers_actions-container { overflow:visible !important; }
        #os_judiciary_customers_actions .table-responsive { overflow:visible !important; }
        </style>

        <fieldset style="margin-top:20px">
            <legend><i class="fa fa-users"></i> إجراءات الأطراف</legend>
            <div style="margin-bottom:14px">
                <?= Html::a(
                    '<i class="fa fa-plus"></i> إضافة إجراء جديد',
                    ['/judiciaryCustomersActions/judiciary-customers-actions/create-followup-judicary-custamer-action', 'contractID' => $model->contract_id],
                    [
                        'role' => 'modal-remote',
                        'class' => 'btn btn-success',
                        'style' => 'border-radius:8px;font-size:13px;padding:8px 20px;font-weight:600',
                    ]
                ) ?>
            </div>

            <?php
            $contractIdForGrid = $model->contract_id;
            $judiciaryIdForGrid = $model->id;
            $actionsDP = new yii\data\ActiveDataProvider([
                'query' => \backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions::find()
                    ->where(['judiciary_id' => $model->id]),
                'sort' => ['defaultOrder' => ['action_date' => SORT_DESC]],
                'pagination' => ['pageSize' => 20],
            ]);
            echo GridView::widget([
                'id' => 'os_judiciary_customers_actions',
                'dataProvider' => $actionsDP,
                'pjax' => true,
                'summary' => '<span style="font-size:11px;color:#94A3B8">عرض {begin}-{end} من {totalCount} إجراء</span>',
                'export' => false,
                'columns' => [
                    [
                        'label' => 'الإجراء',
                        'format' => 'raw',
                        'value' => function ($m) use ($natureStyles) {
                            $def = $m->judiciaryActions;
                            $nature = $def ? ($def->action_nature ?: 'process') : 'process';
                            $ns = $natureStyles[$nature] ?? $natureStyles['process'];
                            $icon = '<i class="fa ' . $ns['icon'] . '" style="color:' . $ns['color'] . ';margin-left:4px"></i>';
                            return $icon . '<span style="font-weight:600">' . Html::encode($def ? $def->name : '#' . $m->judiciary_actions_id) . '</span>';
                        },
                    ],
                    [
                        'label' => 'الطبيعة',
                        'format' => 'raw',
                        'value' => function ($m) use ($natureStyles) {
                            $def = $m->judiciaryActions;
                            $nature = $def ? ($def->action_nature ?: 'process') : 'process';
                            $ns = $natureStyles[$nature] ?? $natureStyles['process'];
                            return '<span style="padding:2px 8px;border-radius:6px;font-size:10px;font-weight:600;background:' . $ns['bg'] . ';color:' . $ns['color'] . '">' . $ns['label'] . '</span>';
                        },
                        'contentOptions' => ['style' => 'white-space:nowrap'],
                    ],
                    ['label' => 'العميل', 'value' => 'customers.name'],
                    [
                        'label' => 'حالة الطلب',
                        'format' => 'raw',
                        'value' => function ($m) use ($statusColors, $statusLabels) {
                            if (!$m->request_status) return '<span style="color:#CBD5E1">—</span>';
                            $c = $statusColors[$m->request_status] ?? '#6B7280';
                            $l = $statusLabels[$m->request_status] ?? $m->request_status;
                            return '<span style="padding:2px 8px;border-radius:6px;font-size:10px;font-weight:600;background:' . $c . '20;color:' . $c . '">' . $l . '</span>';
                        },
                    ],
                    ['attribute' => 'action_date', 'label' => 'التاريخ', 'contentOptions' => ['style' => 'white-space:nowrap']],
                    ['attribute' => 'note', 'label' => 'ملاحظات', 'contentOptions' => ['style' => 'max-width:160px;word-wrap:break-word;direction:rtl;font-size:11px;color:#64748B']],
                    ['attribute' => 'created_by', 'label' => 'المنشئ', 'value' => 'createdBy.username'],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'contentOptions' => ['style' => 'width:50px;text-align:center;overflow:visible;position:relative'],
                        'header' => '',
                        'template' => '{all}',
                        'buttons' => [
                            'all' => function($url, $m) use ($contractIdForGrid) {
                                $editUrl = Url::to(['/judiciaryCustomersActions/judiciary-customers-actions/update-followup-judicary-custamer-action', 'contractID' => $contractIdForGrid, 'id' => $m->id]);
                                $delUrl  = Url::to(['/judiciary/judiciary/delete-customer-action', 'id' => $m->id, 'judiciary' => $m->judiciary_id]);

                                return '<div class="jca-act-wrap">'
                                    . '<button type="button" class="jca-act-trigger"><i class="fa fa-ellipsis-v"></i></button>'
                                    . '<div class="jca-act-menu">'
                                    .   '<a href="' . $editUrl . '" role="modal-remote"><i class="fa fa-pencil text-primary"></i> تعديل</a>'
                                    .   '<div class="jca-act-divider"></div>'
                                    .   '<a href="' . $delUrl . '" role="modal-remote" data-request-method="post" data-confirm-title="تأكيد الحذف" data-confirm-message="هل أنت متأكد من حذف هذا الإجراء؟"><i class="fa fa-trash text-danger"></i> حذف</a>'
                                    . '</div>'
                                    . '</div>';
                            },
                        ],
                    ],
                ],
                'striped' => true,
                'condensed' => true,
                'responsive' => true,
            ]);
            ?>
        </fieldset>

        <?php \yii\bootstrap\Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '', 'size' => \yii\bootstrap\Modal::SIZE_LARGE]) ?>
        <?php \yii\bootstrap\Modal::end() ?>

        <?php
        $jcaJs = <<<'JS'
        $(document).on('click', '.jca-act-trigger', function(e) {
            e.stopPropagation();
            var $wrap = $(this).closest('.jca-act-wrap');
            var $menu = $wrap.find('.jca-act-menu');
            var wasOpen = $wrap.hasClass('open');
            $('.jca-act-wrap.open').removeClass('open');
            if (!wasOpen) {
                $wrap.addClass('open');
                var r = this.getBoundingClientRect();
                $menu.css({ left: r.left + 'px', top: (r.bottom + 4) + 'px' });
            }
        });
        $(document).on('click', function() { $('.jca-act-wrap.open').removeClass('open'); });
        $(document).on('click', '.jca-act-menu a', function() { $('.jca-act-wrap.open').removeClass('open'); });
JS;
        $this->registerJs($jcaJs);
        ?>
    <?php endif ?>
</div>
