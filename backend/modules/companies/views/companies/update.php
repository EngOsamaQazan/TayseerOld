<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\modules\companies\models\Companies */

$this->title = Yii::t('app', 'Update Company');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Companies'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="companies-update">

    <?= $this->render('_form', [
        'model' => $model,
        'modelsCompanieBanks'=>$modelsCompanieBanks
    ]) ?>

</div>
