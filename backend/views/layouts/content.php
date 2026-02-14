<?php
/**
 * منطقة المحتوى الرئيسية
 * ========================
 * تحتوي على: رأس المحتوى مع مسار التنقل، المحتوى الفعلي، التذييل
 * 
 * @var string $content المحتوى المُمرر من الإجراء (Action)
 * @var string $directoryAsset مسار ملفات AdminLTE
 */

use yii\helpers\Html;
use yii\widgets\Breadcrumbs;
?>

<!-- === منطقة المحتوى === -->
<div class="content-wrapper">

    <!-- === رأس المحتوى: العنوان ومسار التنقل === -->
    <section class="content-header">
        <?php if (isset($this->blocks['content-header'])) : ?>
            <h1><?= $this->blocks['content-header'] ?></h1>
        <?php else : ?>
            <h1>
                <?php
                if ($this->title !== null) {
                    echo Html::encode($this->title);
                }
                ?>
            </h1>
        <?php endif ?>

        <!-- مسار التنقل (Breadcrumbs) -->
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
    </section>

    <!-- === المحتوى الرئيسي === -->
    <section class="content">
        <?= $content ?>
    </section>
</div>

<!-- === التذييل === -->
<footer class="main-footer" style="text-align: center;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-direction: row-reverse;">
        <?php
        $isStatement = false;
        if (isset($this->context) && $this->context instanceof \yii\base\Controller && $this->context->action !== null) {
            $isStatement = ($this->context->module->id ?? '') === 'followUp'
                && $this->context->id === 'follow-up'
                && $this->context->action->id === 'printer';
        }
        if (!$isStatement) :
        ?>
        <span>
            صُمم بـ <span style="color:#e25555; font-size:14px;">&#10084;</span> على يد الـ Boss &mdash; معمول عشان يسهّل عليك
        </span>
        <?php endif; ?>
        <span style="color: #999; font-size: 11px;">
            نظام تيــســـر لإدارة شركات التقسيط الإسلامي
        </span>
    </div>
</footer>
