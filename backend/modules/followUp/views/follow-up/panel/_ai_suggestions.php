<?php

use yii\helpers\Html;

/**
 * @var array $aiData
 * @var backend\modules\contracts\models\Contracts $contract
 */

$nba = $aiData['next_best_action'] ?? [];
$alternatives = $aiData['alternatives'] ?? [];
$playbook = $aiData['playbook'] ?? null;
$risk = $aiData['risk'] ?? [];

$riskLevelArabic = ['low' => 'منخفض', 'med' => 'متوسط', 'high' => 'مرتفع', 'critical' => 'حرج'];
$riskImpactArabic = ['low' => 'منخفض', 'medium' => 'متوسط', 'high' => 'مرتفع'];
?>

<div class="ocp-ai-panel">
    <div class="ocp-ai-panel__header">
        <div class="ocp-ai-panel__header-icon">
            <i class="fa fa-magic"></i>
        </div>
        <span class="ocp-ai-panel__header-title">اقتراحات النظام</span>
        <?php if (!empty($nba['confidence'])): ?>
        <span class="ocp-ai-panel__confidence">ثقة <?= $nba['confidence'] ?>%</span>
        <?php endif; ?>
    </div>

    <?php // ═══ NEXT BEST ACTION ═══ ?>
    <?php if (!empty($nba['action'])): ?>
    <div class="ocp-ai-nba">
        <div class="ocp-ai-nba__label">الإجراء الأمثل التالي</div>
        <div class="ocp-ai-nba__action">
            <i class="fa <?= $nba['icon'] ?? 'fa-bolt' ?>" style="color:var(--ocp-primary)"></i>
            <?= Html::encode($nba['action']) ?>
        </div>

        <?php if (!empty($nba['reasons'])): ?>
        <ul class="ocp-ai-nba__reasons">
            <?php foreach ($nba['reasons'] as $reason): ?>
            <li><?= Html::encode($reason) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <?php if (!empty($nba['risk_impact'])): ?>
        <div class="ocp-ai-nba__risk ocp-ai-nba__risk--<?= $nba['risk_impact'] ?>">
            <i class="fa fa-shield"></i>
            تأثير المخاطر: <?= $riskImpactArabic[$nba['risk_impact']] ?? $nba['risk_impact'] ?>
        </div>
        <?php endif; ?>

        <?php if ($nba['action_type'] !== 'none'): ?>
        <button class="ocp-ai-nba__execute-btn" onclick="OCP.executeAIAction('<?= $nba['action_type'] ?>')">
            <i class="fa fa-play-circle"></i>
            تنفيذ الإجراء
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php // ═══ ALTERNATIVES ═══ ?>
    <?php if (!empty($alternatives)): ?>
    <div class="ocp-ai-alternatives">
        <div style="font-size:var(--ocp-font-size-xs);font-weight:600;color:var(--ocp-text-secondary);margin-bottom:4px">بدائل أخرى</div>
        <?php foreach ($alternatives as $alt): ?>
        <div class="ocp-ai-alt" onclick="OCP.openPanel('<?= $alt['type'] ?>')">
            <span class="ocp-ai-alt__icon"><i class="fa <?= $alt['icon'] ?>"></i></span>
            <span class="ocp-ai-alt__text"><?= Html::encode($alt['action']) ?></span>
            <span class="ocp-ai-alt__arrow"><i class="fa fa-chevron-left"></i></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php // ═══ FEEDBACK ═══ ?>
    <div class="ocp-ai-feedback">
        <button class="ocp-ai-feedback__btn" onclick="OCP.aiFeedback('executed')" data-feedback="executed">
            <i class="fa fa-check"></i> نفّذت
        </button>
        <button class="ocp-ai-feedback__btn" onclick="OCP.aiFeedback('rejected')" data-feedback="rejected">
            <i class="fa fa-times"></i> رفضت
        </button>
        <button class="ocp-ai-feedback__btn" onclick="OCP.aiFeedback('not_applicable')" data-feedback="not_applicable">
            <i class="fa fa-ban"></i> غير مناسب
        </button>
    </div>

    <?php // ═══ PLAYBOOK ═══ ?>
    <?php if ($playbook): ?>
    <div class="ocp-playbook">
        <div class="ocp-playbook__header">
            <span class="ocp-playbook__badge">سيناريو <?= Html::encode($playbook['id']) ?></span>
            <span class="ocp-playbook__title"><?= Html::encode($playbook['name']) ?></span>
        </div>

        <ol class="ocp-playbook__steps">
            <?php foreach ($playbook['steps'] as $si => $step): 
                $isCurrent = ($si + 1) === $playbook['current_step'];
                $isDone = $step['done'];
                $stepClass = $isDone ? 'done' : ($isCurrent ? 'current' : '');
            ?>
            <li class="ocp-playbook__step ocp-playbook__step--<?= $stepClass ?>">
                <div class="ocp-playbook__step-dot"></div>
                <span class="ocp-playbook__step-when"><?= Html::encode($step['when']) ?></span>
                <span class="ocp-playbook__step-what"><?= Html::encode($step['what']) ?></span>
            </li>
            <?php endforeach; ?>
        </ol>
    </div>
    <?php endif; ?>

    <?php // ═══ RISK SIGNALS ═══ ?>
    <?php if (!empty($risk['signals'])): ?>
    <div style="margin-top:var(--ocp-space-lg);padding-top:var(--ocp-space-md);border-top:1px solid var(--ocp-border-light)">
        <div style="font-size:var(--ocp-font-size-xs);font-weight:700;color:var(--ocp-text-secondary);margin-bottom:var(--ocp-space-sm)">
            <i class="fa fa-shield"></i> إشارات المخاطر (<?= $risk['score'] ?>/100)
        </div>
        <?php foreach (array_slice($risk['signals'], 0, 5) as $signal): ?>
        <div style="display:flex;align-items:center;gap:6px;padding:3px 0;font-size:var(--ocp-font-size-xs);color:var(--ocp-text-secondary)">
            <span style="width:6px;height:6px;border-radius:50%;background:<?= $signal['weight'] >= 25 ? 'var(--ocp-danger)' : ($signal['weight'] >= 15 ? 'var(--ocp-warning)' : 'var(--ocp-info)') ?>;flex-shrink:0"></span>
            <span><?= Html::encode($signal['reason']) ?></span>
            <span class="ocp-mono" style="margin-right:auto;opacity:0.6">+<?= $signal['weight'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
