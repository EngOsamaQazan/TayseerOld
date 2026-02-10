<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use kartik\date\DatePicker;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use common\models\User;
use common\components\CompanyChecked;

/* @var $this yii\web\View */
/* @var $model backend\modules\companies\models\Companies */
/* @var $form yii\widgets\ActiveForm */
?>
<?php

?>
<div class='row'>
    <?php $form = ActiveForm::begin([]); ?>
    <?php
    if (isset($id)) {
        $form = ActiveForm::begin(['action' => Url::to(['update', 'id' => $id]), 'options' => ['enctype' => 'multipart/form-data'], 'id' => 'dynamic-form']);
    } else {
        $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data'], 'id' => 'dynamic-form']);
    }
    ?>
    <?= $form->errorSummary($model) ?>
    <div class="col-lg-3">
        <?php
        $logo = Yii::$app->params['companies_logo'];
        ?>
        <div>
            <?php
            if ($model->isNewRecord) {
                echo Html::img(Url::to([$logo]), ['style' => 'width: 200px;height:200px ;object-fit: contain; margin-top: 20px']);
            } else {
                echo Html::img(Url::to(['/' . $model->logo]), ['style' => 'width:200px;height:200px; object-fit: contain; margin-top: 20px']);
            }
            echo $form->field($model, 'logo')->fileInput()
                ?>
        </div>
    </div>
    <div class="col-lg-9">
        <div class="questions-bank box box-primary">
            <div class="row">
                <div class="col-lg-6">
                    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-lg-6">
                    <?= $form->field($model, 'phone_number')->textInput(['maxlength' => true]) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6">
                    <?= $form->field($model, 'company_social_security_number')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-lg-6">
                    <?= $form->field($model, 'company_tax_number')->textInput(['maxlength' => true]) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6">
                    <?= $form->field($model, 'company_address')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-lg-6">
                    <?= $form->field($model, 'company_email')->textInput(['maxlength' => true]) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <?php
                    $CompanyChecked = new CompanyChecked();
                    $primary_company = $CompanyChecked->findPrimaryCompany();
                    if ($primary_company == '') {
                        echo $form->field($model, 'is_primary_company')->checkbox();
                    } else {
                        if (!empty($primary_company->id)) {
                            if ($primary_company->id == $model->id) {
                                echo $form->field($model, 'is_primary_company')->checkbox();
                            }

                        }
                    }
                    ?>
                </div>
            </div>
            <div class="row">
                <?= $this->render('_parital/company_banks', [
                    'modelsCompanieBanks' => $modelsCompanieBanks,
                    'form' => $form
                ]) ?>
            </div>

            <?php if (!Yii::$app->request->isAjax) { ?>
                <div class="form-group">
                    <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
                </div>
            <?php } ?>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>