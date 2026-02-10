<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use common\models\User;
use backend\modules\location\models\Location;
use backend\modules\designation\models\Designation;
use backend\modules\department\models\Department;
use common\models\Countries;
use kartik\date\DatePicker;

use backend\widgets\ImageManagerInputWidget;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $id */
/* @var $model backend\models\Employee */
/* @var $form yii\widgets\ActiveForm */
/* @var $employeeAttachments */

$avatarSrc = !empty($model->profileAvatar) ? $model->profileAvatar->path : '/images/aa.jpg';

?>

<div class="employee-form">
    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <div class="col-md-3">

        <?php
        if (!empty($avatarSrc)){
        echo Html::img(Url::to([$avatarSrc]), ['style' => "width: 200px;height:200px;", 'alt' => 'User Image']);

        }else{
            echo Html::a(Html::img($directoryAsset ."/img/user2-160x160.jpg", ['style' => "max-width: 37px;max-height:37px;margin-top: 2px;border-radius:50%", 'alt' => 'User Image'])
                . $msgUnread->title_html, Url::to([$msgUnread->href.'&notificationID='.$msgUnread->id]));
        }?>
        <hr/>
        <?= $form->field($model, 'profile_avatar_file')->fileInput()->label(Yii::t('app','Profile Avatar File')) ?>
    </div>
    <div class="col-md-9">
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'class' => 'form-control'])->label(Yii::t('app', 'first name'), ['class' => 'col-md-6']) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'middle_name')->textInput(['class' => 'form-control'])->label(Yii::t('app', 'middle name'), ['class' => 'col-md-6']) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'last_name')->textInput(['maxlength' => true, 'class' => 'form-control'])->label(Yii::t('app', 'last name'), ['class' => 'col-md-6']) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'location')->dropDownList(yii\helpers\ArrayHelper::map(Location::find()->where(['status' => 'active'])->all(), 'id', 'location'), ['prompt' => Yii::t('app', 'select location')])->label(Yii::t('app', 'location'), ['class' => 'col-md-6']) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'email')->textInput(['maxlength' => true, 'class' => 'form-control'])->label(Yii::t('app', 'email'), ['class' => 'col-md-6']) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'employee_type')->dropDownList(['Active' => 'Active', 'Suspended' => 'Suspended',], ['prompt' => ''])->label(Yii::t('app', 'employee type'), ['class' => 'col-md-6']) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'employee_status')->dropDownList(['Full_time' => 'Full time', 'Part_time' => 'Part time',], ['prompt' => ''])->label(Yii::t('app', 'employee status'), ['class' => 'col-md-6']) ?>
            </div>
            <div class="col-md-6">
                <?=
                $form->field($model, 'date_of_hire')->widget(DatePicker::classname(), [
                    'options' => ['placeholder' => 'Enter birth date ...',],
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'yyyy-mm-dd'
                    ]
                ])->label(Yii::t('app', 'date of hire'))
                ?>
            </div>
        </div>
        <div style="width: 87%;height: 28px;border-bottom: 1px solid #e4e4e4;margin-bottom: 2%;">
            <span style="font-size: 20px;color: #7d7d7d;"><?= Yii::t('app', 'work') ?></span>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'department')->dropDownList(yii\helpers\ArrayHelper::map(Department::find()->all(), 'id', 'title'), ['prompt' => Yii::t('app', 'select department')])->label(Yii::t('app', 'department'), ['class' => 'col-md-6']) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'job_title')->dropDownList(yii\helpers\ArrayHelper::map(Designation::find()->where(['status' => 'active'])->all(), 'id', 'title'), ['prompt' => Yii::t('app', 'select title')])->label(Yii::t('app', 'job title'), ['class' => 'col-md-6']) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'reporting_to')->dropDownList(yii\helpers\ArrayHelper::map(User::find()->all(), 'id', 'username'), ['prompt' => Yii::t('app', 'select reporting to')])->label(Yii::t('app', 'reporting leader'), ['class' => 'col-md-6']) ?>
            </div>
        </div>
        <div style="width: 87%;height: 28px;border-bottom: 1px solid #e4e4e4;margin-bottom: 2%;">
            <span style="font-size: 20px;color: #7d7d7d;"><?= Yii::t('app', 'personal data') ?></span>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'mobile')->textInput(['maxlength' => true, 'class' => 'form-control'])->label(Yii::t('app', 'mobile'), ['class' => 'col-md-6']) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'nationality')->dropDownList(yii\helpers\ArrayHelper::map(Countries::find()->all(), 'id', 'country_name'), ['prompt' => Yii::t('app', 'select nationality')])->label(Yii::t('app', 'nationality'), ['class' => 'col-md-6']) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'gender')->dropDownList(['Female' => 'Female', 'Male' => 'Male',], ['prompt' => Yii::t('app', 'select gender')])->label(Yii::t('app', 'gender'), ['class' => 'col-md-6']) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'marital_status')->dropDownList(['single' => 'Single', 'married' => 'Married',], ['prompt' => Yii::t('app', 'select status')])->label(Yii::t('app', 'marital status'), ['class' => 'col-md-6']) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <?= $form->field($model, 'bio')->textarea(['rows' => 4])->label(Yii::t('app', 'bio'), ['class' => 'col-md-6']) ?>
            </div>
        </div>
        <div style="width: 87%;height: 28px;border-bottom: 1px solid #e4e4e4;margin-bottom: 2%;">
            <span style="font-size: 20px;color: #7d7d7d;"><?= Yii::t('app', 'credentials') ?></span>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'username')->textInput(['maxlength' => true, 'class' => 'form-control'])->label(Yii::t('app', 'user name'), ['class' => 'col-md-6']) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'password_hash')->passwordInput(['maxlength' => true, 'value' => '', 'class' => 'form-control'])->label(Yii::t('app', 'password'), ['class' => 'col-md-6']) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?= $form->field($model, 'credental_sms_send')->checkBox(['label' => Yii::t('app','send credental by sms'), 'data-size' => 'small', 'class' => 'bs_switch', 'style' => 'margin-bottom:4px;', 'id' => 'active']) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'credental_email_send')->checkBox(['label' => Yii::t('app','send credental by email'), 'data-size' => 'small', 'class' => 'bs_switch', 'style' => 'margin-bottom:4px;', 'id' => 'active']) ?>
            </div>
        </div>
    </div>

   <?php /*if (!($model->isNewRecord)) {
        echo $this->render('_partial/_attachments_table', ['employeeAttachments' => $employeeAttachments,'form'=>$form,'model'=>$model]);
    }
*/    ?>


    <?php if (!Yii::$app->request->isAjax) { ?>
        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>
    <?php } ?>

    <?php ActiveForm::end(); ?>
</div>

