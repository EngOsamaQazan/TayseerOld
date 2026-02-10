<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use kartik\money\MaskMoney;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use backend\modules\customers\models\Customers;
use yii\helpers\Url;
use backend\widgets\ImageManagerInputWidget;

$this->registerJsFile('/js/Tafqeet.js');
/* @var $this yii\web\View */
/* @var $model app\models\contracts */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="contracts-form" style="direction: rtl">

    <?php $form = ActiveForm::begin(); ?>

    <legend><h1>عقد بيع</h1></legend>
    <div class="row">
        <p>نص نص نص نص نص نص نص نص نص نص نص نص
            نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص ن
            نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص ن
            نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص ن
            نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص ن
            نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص ن
            نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص ن
            ص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص نص </p>
    </div>
    <legend><h1>بيانات المشتري والكفلاء</h1></legend>
    <article>
        <?php
        if ($model->type == 'normal') {
            ?>
            <h3>اسم المتشري</h3>
            <?php
        } else {
            ?>
            <h3>أسماء المشترين</h3>
            <?php
        }
        ?>
    </article>
    <?php
    foreach ($model->customers as $key) {
        ?>
        <div class="row">
            <div class="col-sm-4 col-xs-4">
                <?php {
                    if(isset($key->phoneNumbers[0])){
                        echo $key->phoneNumbers[0]->phone_number;
                    };
                }
                ?>
            </div>
            <div class="col-sm-4 col-xs-4">
                <?php {
                    echo $key->id_number;
                }
                ?>
            </div>
            <div class="col-sm-4 col-xs-4">
                <?php {
                    echo $key->name;
                }
                ?>
            </div>
        </div>
        <?php
    }
    ?>

    <div id="customer_data">
        <div class="row">
            <div class="col-sm-12 col-xs-12">
                <?=
                $form->field($model, 'guarantors_ids')->widget(Select2::classname(), [
                    'data' => yii\helpers\ArrayHelper::map(Customers::find()->all(), 'id', 'name'),
                    'language' => 'en',
                    'options' => ['placeholder' => Yii::t('app', 'Select guarantors  '),
                        'multiple' => true
                    ],
                    'pluginOptions' => [
                        'allowClear' => true,
                        // 'maximumSelectionLength' => 1,
                    ],
                    'pluginEvents' => [
                        "change" => "function() { getCustomerData($('#contracts-customer_id').select2('data')[0].id) }",
                    ],
                ])->label(Yii::t('app', 'Guarantors search'));
                ?>

            </div>
        </div>
        <div class="row">
            <div class="col-sm-2 col-xs-2">
                <label for="total_contracts">total contracts</label>
                <?= Html::input('text', 'total_contracts', '', ['class' => 'form-control', 'id' => 'total_contracts', 'readOnly' => true]) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6 col-xs-6">
                <label for="name">name</label>
                <?= Html::input('text', 'name', '', ['class' => 'form-control', 'id' => 'name', 'readOnly' => true]) ?>
            </div>
            <div class="col-sm-6 col-xs-6">
                <label for="id id_number">id number</label>
                <?= Html::input('text', 'id_number', '', ['class' => 'form-control', 'id' => 'id_number', 'readOnly' => true]) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6 col-xs-6">
                <label for="birth_date">birth date</label>
                <?= Html::input('text', 'birth_date', '', ['class' => 'form-control', 'id' => 'birth_date', 'readOnly' => true]) ?>
            </div>
            <div class="col-sm-6 col-xs-6">
                <label for="email">email</label>
                <?= Html::input('text', 'email', '', ['class' => 'form-control', 'id' => 'email', 'readOnly' => true]) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6 col-xs-6">
                <label for="job_title">job title</label>
                <?= Html::input('text', 'job_title', '', ['class' => 'form-control', 'id' => 'job_title', 'readOnly' => true]) ?>
            </div>
            <div class="col-sm-6 col-xs-6">
                <label for="bank_info">bank info</label>
                <?= Html::input('text', 'bank_info', '', ['class' => 'form-control', 'id' => 'bank_info', 'readOnly' => true]) ?>
            </div>
        </div>
    </div>
    <fieldset>
        <legend><?= Yii::t('app', 'Basic Information') ?></legend>
        <div class="row">

            <div class="col-sm-6 col-xs-6">
                <?= $form->field($model, 'seller_name')->dropDownList(yii\helpers\ArrayHelper::map(\backend\modules\companies\models\Companies::find()->all(),'id','name'), ['prompt' => \Yii::t('app', 'أختر شركة')]) ?>
            </div>
            <div class="col-sm-6 col-xs-6">
                <?=
                $form->field($model, 'Date_of_sale')->widget(DatePicker::classname(), [
                    'options' => ['placeholder' => 'Enter Date of sale ...'],
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'yyyy-mm-dd',
                        'multidate' => false
                    ]
                ]);
                ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6 col-xs-6">
                <?=
                $form->field($model, 'first_installment_value')->widget(MaskMoney::classname(), [
                    'pluginOptions' => [
                        'prefix' => html_entity_decode('$ '), // the Indian Rupee Symbol
                        'suffix' => '',
                        'affixesStay' => true,
                        'thousands' => ',',
                        'decimal' => '.',
                        'precision' => 0,
                        'allowZero' => false,
                        'allowNegative' => false,
                    ], 'pluginEvents' => [
                        "change" => "function() {installment_table()}",
                    ],
                ]);
                ?>
            </div>
            <div class="col-sm-6 col-xs-6">
                <?=
                $form->field($model, 'first_installment_date')->widget(DatePicker::classname(), [
                    'options' => ['placeholder' => 'Enter  Date of first Income sale ...'],
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'yyyy-mm-dd',
                        'multidate' => false
                    ], 'pluginEvents' => [
                        "change" => "function() {installment_table()}",
                    ],
                ]);
                ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-2 col-xs-2">
                <?=
                $form->field($model, 'total_value')->widget(MaskMoney::classname(), [
                    'pluginOptions' => [
                        'prefix' => html_entity_decode('$ '), // the Indian Rupee Symbol
                        'suffix' => '',
                        'affixesStay' => true,
                        'thousands' => '',
                        'decimal' => '.',
                        'precision' => 0,
                        'allowZero' => false,
                        'allowNegative' => false,
                    ], 'pluginEvents' => [
                        "change" => "function() {installment_table()}",
                    ],
                ]);
                ?>
            </div>
            <div class="col-sm-10 col-xs-10">
                <label for="total_in_words">total in words</label>
                <?= Html::input('text', 'total_in_words', '', ['class' => 'form-control', 'id' => 'total_in_words', 'readOnly' => true]) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6 col-xs-6">
                <?=
                $form->field($model, 'monthly_installment_value')->widget(MaskMoney::classname(), [
                    'pluginOptions' => [
                        'prefix' => html_entity_decode('$ '), // the Indian Rupee Symbol
                        'suffix' => '',
                        'affixesStay' => true,
                        'thousands' => ',',
                        'decimal' => '.',
                        'precision' => 0,
                        'allowZero' => false,
                        'allowNegative' => false,
                    ], 'pluginEvents' => [
                        "change" => "function() {installment_table()}",
                    ],
                ]);
                ?>
            </div>
            <div class="col-sm-6 col-xs-6">
                <label for="installment_count">installment's count</label>
                <?= Html::input('text', 'installment_count', '', ['class' => 'form-control', 'id' => 'installment_count', 'readOnly' => true]) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12 col-xs-12">
                <?= $form->field($model, 'notes')->textarea(['rows' => 6]) ?>
            </div>
        </div>
        <?php
        $image_manager_random_id = rand(100000000, 1000000000);
        $model->image_manager_id = $image_manager_random_id;
        ?>
        <?= $form->field($model, 'image_manager_id')->hiddenInput()->label(false); ?>
        <div class="row">
            <?php
            echo $form->field($model, 'contract_images')->widget(ImageManagerInputWidget::className(), [
                'aspectRatio' => (16 / 9), //set the aspect ratio
                'cropViewMode' => 1, //crop mode, option info: https://github.com/fengyuanchen/cropper/#viewmode
                'showPreview' => true, //false to hide the preview
                'showDeletePickedImageConfirm' => false, //on true show warning before detach image
                'groupName' => 'contracts',
                'contractId' => $model->isNewRecord ? $image_manager_random_id : $model->id,
            ]);
            ?>
        </div>



        <?php if (!Yii::$app->request->isAjax) { ?>
            <div class="form-group">
                <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
            </div>
        <?php } ?>

        <?php ActiveForm::end(); ?>
        <div class="row">

            <div class="col-sm-12 col-xs-12">
                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">amount</th>
                        <th scope="col">month</th>
                        <th scope="col">year</th>
                    </tr>
                    </thead>
                    <tbody id="installment_table_body">

                    </tbody>
                </table>
            </div>
        </div>

        <script type="text/javascript">
            //the dropdown list id; This doesn't have to be a dropdown it can be any field type.
            function getCustomerData(id) {
                //the dropdown list selected locations id
                //var id = $('#customers_ids').val();
                var url = "<?= Url::to(['customers/customer-data']); ?>" + "?id=" + id;
                //call the action we created above in the conrller
                $.get(url, function (data) {
                        //get the JSON data from the action
                        //var data = $.parseJSON(data);
                        //check if the system found any data
                        if (data !== null) {
                            $("#name").val(data.model.name).blur();
                            $("#city").val(data.model.city).blur();
                            $("#bank_info").val(data.model.bank_info).blur();
                            $("#job_title").val(data.model.job_title).blur();
                            $("#id_number").val(data.model.id_number).blur();
                            $("#birth_date").val(data.model.birth_date).blur();
                            $("#email").val(data.model.email).blur();
                            $("#contracts_info").val(data.contracts_info.count).blur();
                            $("#user_status").val(data.contracts_info.email).blur();
                            $('#updateCustomer').show();
                        } else {
                            //if data wasn't found the alert.
                            alert('We\'re sorry but we couldn\'t load the the customer data!');
                        }
                    }
                );
            }
            function updateCustomer(id) {
                var url = "<?= Url::to(['customers/update']); ?>" + "?id=" + id;
                window.open(url, '_blank');
            }
            function installment_table() {
                var total_value = $("#contracts-total_value").val();
                var first_installment_value = $("#contracts-first_installment_value").val();
                var monthly_installment_value = $("#contracts-monthly_installment_value").val();
                if (total_value > 0 && first_installment_value > 0 && monthly_installment_value > 0) {
                    $('#installment_table_body').empty();
                    instalments_count = 0;
                    instalments_count = total_value / monthly_installment_value;
                    var year = new Date($('#contracts-first_installment_date').val()).getFullYear();
                    var d = new Date($('#contracts-first_installment_date').val());
                    var m = d.getMonth();
                    var y = 0;

                    for (i = 0; i < instalments_count; i++) {

                        if (i == parseInt(instalments_count)) {
                            var mounthly_instalment = total_value - (monthly_installment_value * (i));
                        } else {
                            var mounthly_instalment = monthly_installment_value;
                        }
                        if (m == 12) {
                            m = 1
                            y++;
                        } else {
                            m++;
                        }
                        var year_string = year + y;
                        var row = '<tr><th scope="row">' + (i + 1) + '</th><td>' + mounthly_instalment + '</td><td>' + m + '</td><td>' + year_string + '</td></tr>';
                        $('#installment_table_body').append(row);
                    }
                    $('#installment_count').val(i);
                }
            }
            function customers_aria(item) {
                if (item == 'normal') {
                    $('#solidarity_contract').hide();
                    $('#normal_contract').show();
                    $('#customer_data').show();
                    $('#updateCustomer').show();
                } else {
                    $('#solidarity_contract').show();
                    $('#normal_contract').hide();
                    $('#customer_data').hide();
                    $('#updateCustomer').hide();
                }
            }
        </script>
        <?php
        if (!$model->isNewRecord) {
            $script = <<< JS
    $(document).ready(function(){
            $('#total_in_words').val(tafqeet($("#contracts-total_value").val())+' دينار اردني فقط لاغير');
            installment_table();
            customers_aria('$model->type');
            //getCustomerData($('#contracts-customer_id').select2('data')[0].id);
    }); 
JS;
            $this->registerJs($script, $this::POS_END);
        }
        ?>
        <?php
        $script = <<< JS
$(document).ready(function(){
    $("#contracts-total_value").change(function () {
                $('#total_in_words').val(tafqeet($(this).val())+' دينار اردني فقط لاغير');
            });
}); 
JS;
        $this->registerJs($script, $this::POS_END);
        ?>
        <?php
        $script = <<< JS
$(document).ready(function () {
    $("[name='Contracts[type]']").change(function () {
       customers_aria(\$(this).val());
       
    });
}); 
JS;
        $this->registerJs($script, $this::POS_END);
        ?>
</div>
