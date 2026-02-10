<?php

use kartik\tabs\TabsX;

/* @var $this yii\web\View */
/* @var $model backend\models\Employee */
/* @var $id  */
/* @var $employeeAttachments  */
?>

<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?=Yii::t('app','Update')?></h3>
    </div>
    <div class="box-body">
        <div class="employee-update">
            <?php
            $items = [
                [
                    'label' => '<i class="glyphicon glyphicon-user" ></i>' . Yii::t('app', 'employee'),
                    'content' => $this->render('_form', [
                        'model' => $model,
                        'employeeAttachments'=>$employeeAttachments,
                        'id'=>$id
                    ]),
                    'active' => true
                ],
                [
                    'label' => '<i class="glyphicon glyphicon-list-alt"></i> ' . Yii::t('app', 'leave policy'),
                    'content' => $this->render('_leave_policy', [
                        'model' => $model,
                    ])

                ]
            ];


            echo TabsX::widget([
                'position' => TabsX::POS_ABOVE,
                'items' => $items,
                'encodeLabels' => false,
            ]);
            ?>
        </div>
    </div>
</div>
