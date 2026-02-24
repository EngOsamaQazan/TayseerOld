<?php
use yii\helpers\Html;
use yii\helpers\Url;
?>

<form action="<?= Url::to(['confirm-delete', 'id' => $model->id]) ?>" method="post">
<?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

<style>
.cd-box { padding: 20px; }
.cd-warning {
    display: flex; align-items: center; gap: 14px; padding: 16px;
    background: #FEF3C7; border-radius: 10px; margin-bottom: 20px; border: 1px solid #FDE68A;
}
.cd-warning i { font-size: 32px; color: #D97706; }
.cd-warning-text h5 { margin: 0 0 4px; font-size: 15px; font-weight: 700; color: #92400E; }
.cd-warning-text p { margin: 0; font-size: 13px; color: #A16207; }
.cd-safe {
    display: flex; align-items: center; gap: 14px; padding: 16px;
    background: #F0FDF4; border-radius: 10px; margin-bottom: 16px; border: 1px solid #BBF7D0;
}
.cd-safe i { font-size: 32px; color: #16A34A; }
.cd-safe-text h5 { margin: 0 0 4px; font-size: 15px; font-weight: 700; color: #166534; }
.cd-safe-text p { margin: 0; font-size: 13px; color: #15803D; }
.cd-action-info {
    display: flex; align-items: center; gap: 10px; padding: 12px 16px;
    background: #F8FAFC; border-radius: 8px; margin-bottom: 20px; border: 1px solid #E2E8F0;
}
.cd-action-info i { font-size: 20px; color: #64748B; }
.cd-action-info span { font-size: 14px; font-weight: 600; color: #334155; }
.cd-action-info .cd-usage {
    margin-right: auto; font-size: 12px; color: #94A3B8; font-weight: 400;
}
.cd-migrate-section {
    padding: 16px; background: #EFF6FF; border-radius: 10px; border: 1px solid #BFDBFE;
}
.cd-migrate-section label {
    display: block; font-size: 13px; font-weight: 700; color: #1E40AF; margin-bottom: 8px;
}
.cd-migrate-section select {
    width: 100%; padding: 10px 12px; border: 1px solid #93C5FD; border-radius: 8px;
    font-size: 14px; background: #fff;
}
.cd-migrate-hint {
    margin-top: 8px; font-size: 12px; color: #3B82F6;
}
</style>

<div class="cd-box">
    <?php if (!empty($error)): ?>
    <div style="padding:10px 16px;background:#FEE2E2;border:1px solid #FCA5A5;border-radius:8px;margin-bottom:16px;color:#991B1B;font-size:13px;font-weight:600">
        <i class="fa fa-exclamation-circle"></i> <?= Html::encode($error) ?>
    </div>
    <?php endif; ?>

    <div class="cd-action-info">
        <i class="fa fa-gavel"></i>
        <span><?= Html::encode($model->name) ?></span>
        <span class="cd-usage"><?= $usageCount ?> استخدام</span>
    </div>

    <?php if ($usageCount > 0): ?>
        <div class="cd-warning">
            <i class="fa fa-exclamation-triangle"></i>
            <div class="cd-warning-text">
                <h5>يوجد <?= number_format($usageCount) ?> سجل مرتبط بهذا الإجراء</h5>
                <p>اختر الإجراء البديل لترحيل السجلات إليه قبل الحذف</p>
            </div>
        </div>

        <div class="cd-migrate-section">
            <label><i class="fa fa-exchange"></i> ترحيل السجلات إلى:</label>
            <?= Html::dropDownList('migrate_to_id', null, $otherActions, [
                'prompt' => '— اختر الإجراء البديل —',
                'class' => 'cd-migrate-select',
                'id' => 'migrate-to-select',
                'required' => true,
            ]) ?>
            <div class="cd-migrate-hint">
                <i class="fa fa-info-circle"></i>
                سيتم نقل جميع السجلات (<?= number_format($usageCount) ?>) من «<?= Html::encode($model->name) ?>» إلى الإجراء المحدد
            </div>
        </div>
    <?php else: ?>
        <div class="cd-safe">
            <i class="fa fa-check-circle"></i>
            <div class="cd-safe-text">
                <h5>لا توجد سجلات مرتبطة</h5>
                <p>يمكن حذف هذا الإجراء بأمان بدون تأثير على بيانات العملاء</p>
            </div>
        </div>
    <?php endif; ?>
</div>
</form>
