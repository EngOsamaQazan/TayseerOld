<?php

use yii\helpers\Html;

/**
 * @var array $financials
 */

$total = $financials['total'] ?? 0;
$paid = $financials['paid'] ?? 0;
$remaining = $financials['remaining'] ?? 0;
$overdue = $financials['overdue'] ?? 0;
$overdueInstallments = $financials['overdue_installments'] ?? 0;
$remainingInstallments = $financials['remaining_installments'] ?? 0;
$complianceRate = $financials['compliance_rate'] ?? 0;
$paidRatio = $total > 0 ? round(($paid / $total) * 100) : 0;
$overdueRatio = $total > 0 ? round(($overdue / $total) * 100) : 0;
?>

<div class="ocp-section">
    <div class="ocp-section-title">
        <i class="fa fa-money"></i>
        اللقطة المالية
    </div>

    <div class="ocp-financial">
        <?php // Total Contract ?>
        <div class="ocp-fin-card">
            <div class="ocp-fin-card__icon ocp-fin-card__icon--total">
                <i class="fa fa-file-text-o"></i>
            </div>
            <div class="ocp-fin-card__value"><?= number_format($total) ?></div>
            <div class="ocp-fin-card__label">إجمالي العقد</div>
            <div class="ocp-fin-card__bar">
                <div class="ocp-fin-card__bar-fill" style="width:100%;background:var(--ocp-primary)"></div>
            </div>
        </div>

        <?php // Paid ?>
        <div class="ocp-fin-card">
            <div class="ocp-fin-card__icon ocp-fin-card__icon--paid">
                <i class="fa fa-check-circle"></i>
            </div>
            <div class="ocp-fin-card__value ocp-text-success"><?= number_format($paid) ?></div>
            <div class="ocp-fin-card__label">المدفوع</div>
            <div class="ocp-fin-card__bar">
                <div class="ocp-fin-card__bar-fill" style="width:<?= $paidRatio ?>%;background:var(--ocp-success)"></div>
            </div>
        </div>

        <?php // Remaining ?>
        <div class="ocp-fin-card">
            <div class="ocp-fin-card__icon ocp-fin-card__icon--remain">
                <i class="fa fa-hourglass-half"></i>
            </div>
            <div class="ocp-fin-card__value"><?= number_format($remaining) ?></div>
            <div class="ocp-fin-card__label">المتبقي</div>
            <div class="ocp-fin-card__bar">
                <div class="ocp-fin-card__bar-fill" style="width:<?= (100 - $paidRatio) ?>%;background:var(--ocp-info)"></div>
            </div>
        </div>

        <?php // Overdue Amount ?>
        <div class="ocp-fin-card">
            <div class="ocp-fin-card__icon ocp-fin-card__icon--overdue">
                <i class="fa fa-exclamation-triangle"></i>
            </div>
            <div class="ocp-fin-card__value ocp-text-danger"><?= number_format($overdue) ?></div>
            <div class="ocp-fin-card__label">المتأخر</div>
            <div class="ocp-fin-card__bar">
                <div class="ocp-fin-card__bar-fill" style="width:<?= $overdueRatio ?>%;background:var(--ocp-danger)"></div>
            </div>
        </div>

        <?php // Overdue Installments ?>
        <div class="ocp-fin-card">
            <div class="ocp-fin-card__icon ocp-fin-card__icon--count">
                <i class="fa fa-calendar-times-o"></i>
            </div>
            <div class="ocp-fin-card__value ocp-text-danger"><?= $overdueInstallments ?></div>
            <div class="ocp-fin-card__label">أقساط متأخرة</div>
        </div>

        <?php // Remaining Installments ?>
        <div class="ocp-fin-card">
            <div class="ocp-fin-card__icon ocp-fin-card__icon--count">
                <i class="fa fa-calendar"></i>
            </div>
            <div class="ocp-fin-card__value"><?= $remainingInstallments ?></div>
            <div class="ocp-fin-card__label">أقساط متبقية</div>
        </div>

        <?php // Compliance Rate ?>
        <div class="ocp-fin-card">
            <div class="ocp-fin-card__icon" style="background:<?= $complianceRate >= 70 ? 'var(--ocp-success-bg)' : ($complianceRate >= 40 ? 'var(--ocp-warning-bg)' : 'var(--ocp-danger-bg)') ?>;color:<?= $complianceRate >= 70 ? 'var(--ocp-success)' : ($complianceRate >= 40 ? 'var(--ocp-warning)' : 'var(--ocp-danger)') ?>">
                <i class="fa fa-pie-chart"></i>
            </div>
            <div class="ocp-fin-card__value" style="color:<?= $complianceRate >= 70 ? 'var(--ocp-success)' : ($complianceRate >= 40 ? 'var(--ocp-warning)' : 'var(--ocp-danger)') ?>"><?= $complianceRate ?>%</div>
            <div class="ocp-fin-card__label">نسبة الالتزام</div>
            <div class="ocp-fin-card__bar">
                <div class="ocp-fin-card__bar-fill" style="width:<?= $complianceRate ?>%;background:<?= $complianceRate >= 70 ? 'var(--ocp-success)' : ($complianceRate >= 40 ? 'var(--ocp-warning)' : 'var(--ocp-danger)') ?>"></div>
            </div>
        </div>
    </div>
</div>
