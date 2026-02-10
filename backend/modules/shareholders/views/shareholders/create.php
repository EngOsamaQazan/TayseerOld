<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Shareholders */

$this->title = Yii::t('app', 'Create Shareholders');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Shareholders'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="shareholders-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
