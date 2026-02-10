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
        <div class="user-panel" style="padding: 18px 12px; border-bottom: 1px solid rgba(255,255,255,0.08); text-align: center;">
            <div style="display: inline-block;">
                <?php
                if (!empty(Yii::$app->params['logo'])) {
                    echo Html::img('/'.$logo, ['style' => "max-width: 55px; max-height: 55px; border-radius: 50%; border: 2px solid rgba(200,160,74,0.5); padding: 2px;", 'alt' => 'شعار الشركة']);
                } else { ?>
                    <img src="<?= $directoryAsset ?>/img/user2-160x160.jpg" style="max-width: 55px; max-height: 55px; border-radius: 50%; border: 2px solid rgba(200,160,74,0.5); padding: 2px;" alt="شعار الشركة" />
                <?php } ?>
            </div>
        </div>
        <div>
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
