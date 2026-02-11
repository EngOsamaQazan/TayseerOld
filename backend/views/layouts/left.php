<?php

use common\components\CompanyChecked;
use yii\helpers\Html;
use yii\helpers\Url;

use backend\modules\notifications\models\notifications;
$CompanyChecked = new CompanyChecked();
$primary_company = $CompanyChecked->findPrimaryCompany();
if ($primary_company == '') {
    $logo = $logo = Yii::$app->params['companies_logo'];
   

} else {
    $logo = $primary_company->logo;
}
?>
<aside class="main-sidebar">
    <section class="sidebar">
        <!-- Sidebar user panel -->
        <div class="sidebar-brand-panel">
            <div class="sidebar-brand-logo">
                <?php
                if (!empty(Yii::$app->params['logo'])) {
                    echo Html::img('/'.$logo, ['class' => 'sidebar-brand-img', 'alt' => 'شعار الشركة']);
                } else { ?>
                    <img src="<?= $directoryAsset ?>/img/user2-160x160.jpg" class="sidebar-brand-img" alt="شعار الشركة" />
                <?php } ?>
            </div>
        </div>
        <div class="sidebar-nav-wrap">
            <?php
            echo dmstr\widgets\Menu::widget([
                'options' => ['class' => 'sidebar-menu tree', 'data-widget' => 'tree'],
                'encodeLabels' => false,
                'items' => require '_menu_items.php'
            ]);
            ?>
        </div>
    </section>
</aside>
