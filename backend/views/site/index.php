<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */

$this->title = 'namaa aqsat erp system';
?>
<div class="site-index">


    <div class="row  box box-primary" style="margin: 0px;margin-top: 10px">
        <div class="col-sm-6 col-xs-6" >
            <?=
            GridView::widget([
                'dataProvider' => $customersDataProvider,
                'summary' => "",
                'columns' => [
                    'id',
                    'name',
                    [
                        'attribute' => 'status',
                        'value' => function($model) {
                            return \Yii::t('app', backend\modules\loanScheduling\models\LoanSchedulingrams['status'][$model->status]);
                        }
                    ],
                    'id_number',
                ],
            ]);
            ?>
        </div>
        <div class="col-sm-6 col-xs-6" >
            <?=
            GridView::widget([
                'dataProvider' => $incomeDataProvider,
                'summary' => "",
                'columns' => [
                    'date',
                    'amount',
                    'payment_type',
                    'receipt_bank',
                    'payment_purpose',
                    '_by',
                ],
            ]);
            ?>
        </div>
    </div>
</div>
<div class="row  box box-primary" style="margin: 0px;margin-top: 10px">
    <div class="col-sm-12 col-xs-12" >
        <?=
        GridView::widget([
            'dataProvider' => $contractSataProvider,
            'summary' => "",
            'columns' => [
                'id',
                'Date_of_sale',
                [
                    'attribute' => 'seller_id',
                    'value' => function ($model) {
                        return @$model->seller->name;
                    }
                ],
                'total_value',
                ['attribute' => 'customers_id',
                    'value' => function($model) {
                        return join(', ', yii\helpers\ArrayHelper::map($model->customers, 'id', 'name'));
                    },
                ],
            ],
        ]);
        ?>
    </div>
</div>

</div>
