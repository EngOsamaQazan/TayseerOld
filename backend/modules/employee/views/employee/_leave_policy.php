<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model backend\models\Employee */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="employee-form">
    <?php $form = ActiveForm::begin(['action' =>['employee/employee-leave-policy?id='.Yii::$app->getRequest()->getQueryParam('id')
]]); ?>
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-6">
                <?php
                $UserLeavePolicy = \common\models\UserLeavePolicy::find()->select('leave_policy_id')->where(['user_id' =>Yii::$app->getRequest()->getQueryParam('id')])->asArray()->all();
                echo $form->field($model, 'leavePolicy')->checkboxList(yii\helpers\ArrayHelper::map($model->getLeavePolicy(), 'id', 'title'), [
                    'item' => function($index, $label, $name, $checked, $value) use ($UserLeavePolicy) {
                        if (isset($UserLeavePolicy[$index]) && in_array($value, array_values($UserLeavePolicy[$index]))) {
                            $checked = 'checked';
                        } else {
                            $checked = '';
                        }
                        return \yii\helpers\Html::checkbox($name, $checked, ['label' => $label, 'value' => $value]);
                    }
                        ])
                        ?>
                    </div>
                </div>

            </div>


            <?php if (!Yii::$app->request->isAjax) { ?>
                <div class="form-group">
                    <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
                </div>
            <?php } ?>

            <?php ActiveForm::end(); ?>


</div>
