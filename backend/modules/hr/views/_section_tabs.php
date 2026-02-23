<?php
/**
 * شريط تبويبات مشترك للأقسام الفرعية داخل الموارد البشرية
 *
 * @var array  $tabs  [['label'=>'...', 'icon'=>'fa-...', 'url'=>['/hr/...']],...]
 * @var string $group معرف المجموعة (tracking, payroll, reports)
 */

use yii\helpers\Url;

$currentUrl = '/' . ltrim(Yii::$app->request->pathInfo, '/');
?>

<div class="hr-section-tabs" data-group="<?= $group ?? 'default' ?>">
    <div class="hr-section-tabs__bar">
        <?php foreach ($tabs as $tab):
            $tabHref = Url::to($tab['url']);
            $tabPath = parse_url($tabHref, PHP_URL_PATH) ?: $tabHref;
            $isActive = ($currentUrl === $tabPath)
                || (isset($tab['match']) && preg_match($tab['match'], $currentUrl));
        ?>
        <a href="<?= $tabHref ?>"
           class="hr-section-tabs__item <?= $isActive ? 'active' : '' ?>">
            <i class="fa <?= $tab['icon'] ?>"></i>
            <span><?= $tab['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<style>
.hr-section-tabs{
    margin:-20px -15px 24px -15px;
    background:#fff;
    border-bottom:1px solid #e2e8f0;
    padding:0 20px;
    position:sticky;top:0;z-index:50;
}
.hr-section-tabs__bar{
    display:flex;
    gap:0;
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
    scrollbar-width:none;
}
.hr-section-tabs__bar::-webkit-scrollbar{display:none}
.hr-section-tabs__item{
    display:flex;
    align-items:center;
    gap:8px;
    padding:14px 20px;
    font-size:13px;
    font-weight:600;
    color:#64748b;
    text-decoration:none;
    white-space:nowrap;
    border-bottom:3px solid transparent;
    transition:all .2s ease;
    position:relative;
}
.hr-section-tabs__item:hover{
    color:#1e293b;
    background:#f8fafc;
    text-decoration:none;
}
.hr-section-tabs__item.active{
    color:var(--clr-primary,#800020);
    border-bottom-color:var(--clr-primary,#800020);
    background:transparent;
}
.hr-section-tabs__item.active:hover{
    background:rgba(128,0,32,.03);
}
.hr-section-tabs__item i{
    font-size:15px;
    width:18px;
    text-align:center;
}
@media(max-width:768px){
    .hr-section-tabs{margin:-15px -10px 16px -10px;padding:0 10px}
    .hr-section-tabs__item{padding:12px 14px;font-size:12px;gap:6px}
    .hr-section-tabs__item i{font-size:13px}
}
</style>
