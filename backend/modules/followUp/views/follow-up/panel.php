<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;

/**
 * @var yii\web\View $this
 * @var backend\modules\contracts\models\Contracts $contract
 * @var array $riskData
 * @var array $aiData
 * @var array $kanbanData
 * @var array $timeline
 * @var array $financials
 * @var array $alerts
 * @var array $customer
 */

$this->title = 'لوحة تحكم العقد #' . $contract->id;
$this->params['breadcrumbs'][] = ['label' => 'تقارير المتابعة', 'url' => ['/followUpReport']];
$this->params['breadcrumbs'][] = $this->title;

// Register OCP assets
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/ocp.css', ['depends' => [\yii\web\JqueryAsset::class]]);
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/ocp.js', ['depends' => [\yii\web\JqueryAsset::class], 'position' => \yii\web\View::POS_END]);

// Pass data to JS
$contractId = $contract->id;
$this->registerJs("window.OCP_CONFIG = " . Json::encode([
    'contractId' => $contractId,
    'urls' => [
        'createTask' => Url::to(['/followUp/follow-up/create-task']),
        'moveTask' => Url::to(['/followUp/follow-up/move-task']),
        'saveFollowUp' => Url::to(['/followUp/follow-up/save-follow-up']),
        'savePromise' => Url::to(['/followUp/follow-up/save-promise']),
        'aiFeedback' => Url::to(['/followUp/follow-up/ai-feedback']),
        'getTimeline' => Url::to(['/followUp/follow-up/get-timeline', 'contract_id' => $contractId]),
        'sendSms' => Url::to(['/followUp/follow-up/send-sms']),
    ],
]) . ";", \yii\web\View::POS_HEAD);

$dpd = $riskData['dpd'] ?? 0;
$dpdClass = $dpd <= 0 ? 'ok' : ($dpd <= 7 ? 'warning' : ($dpd <= 30 ? 'danger' : 'critical'));
$riskLevel = $riskData['level'] ?? 'low';
$showWarningStrip = in_array($riskLevel, ['high', 'critical']) || in_array($contract->status, ['judiciary', 'legal_department']);
$statusBadge = \backend\modules\followUp\helper\RiskEngine::statusBadgeClass($contract->status);
$statusLabel = \backend\modules\followUp\helper\RiskEngine::statusLabel($contract->status);
$customerName = $customer ? $customer->name : 'غير محدد';
$lastPayment = $riskData['last_payment'] ?? ['date' => '-', 'amount' => 0];

$riskLevelArabic = ['low' => 'منخفض', 'med' => 'متوسط', 'high' => 'مرتفع', 'critical' => 'حرج'];
?>

<div class="ocp-page">

    <?php // ═══ WARNING STRIP ═══ ?>
    <?php if ($showWarningStrip): ?>
    <div class="ocp-warning-strip <?= in_array($contract->status, ['judiciary', 'legal_department']) ? 'ocp-warning-strip--legal' : '' ?>">
        <i class="fa fa-exclamation-triangle"></i>
        <?php if (in_array($contract->status, ['judiciary', 'legal_department'])): ?>
            <span>هذا العقد في المرحلة القضائية — تطبق قيود خاصة على الإجراءات المتاحة</span>
        <?php else: ?>
            <span>تنبيه: مستوى المخاطر <?= $riskLevelArabic[$riskLevel] ?? 'مرتفع' ?> — <?= Html::encode($riskData['primary_reason'] ?? '') ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php // ═══ STATUS BAR ═══ ?>
    <div class="ocp-status-bar">
        <div class="ocp-status-bar__inner">
            <?php // Contract ID ?>
            <div class="ocp-status-bar__contract">
                <span class="ocp-status-bar__contract-id">#<?= $contract->id ?></span>
                <button class="ocp-status-bar__copy-btn" onclick="OCP.copyToClipboard('<?= $contract->id ?>')" title="نسخ رقم العقد">
                    <i class="fa fa-copy"></i>
                </button>
            </div>

            <div class="ocp-status-bar__divider"></div>

            <?php // Customer ?>
            <div class="ocp-status-bar__customer">
                <span class="ocp-status-bar__customer-name" title="<?= Html::encode($customerName) ?>"><?= Html::encode($customerName) ?></span>
                <?php if ($customer): ?>
                <a href="<?= Url::to(['/customers/customers/view', 'id' => $customer->id]) ?>" class="ocp-status-bar__customer-link" title="فتح ملف العميل">
                    <i class="fa fa-external-link"></i>
                </a>
                <?php endif; ?>
            </div>

            <div class="ocp-status-bar__divider"></div>

            <?php // Status + DPD ?>
            <span class="ocp-badge ocp-badge--<?= $statusBadge ?>"><?= Html::encode($statusLabel) ?></span>
            
            <div class="ocp-dpd ocp-dpd--<?= $dpdClass ?>">
                <span><?= $dpd ?></span>
                <span class="ocp-dpd__label">يوم تأخير</span>
            </div>

            <div class="ocp-status-bar__divider"></div>

            <?php // Quick Metrics ?>
            <div class="ocp-status-bar__metrics">
                <div class="ocp-metric-mini">
                    <span class="ocp-metric-mini__value ocp-ltr"><?= number_format($financials['remaining'] ?? 0) ?></span>
                    <span class="ocp-metric-mini__label">المتبقي</span>
                </div>
                <div class="ocp-metric-mini">
                    <span class="ocp-metric-mini__value ocp-ltr ocp-text-danger"><?= number_format($financials['overdue'] ?? 0) ?></span>
                    <span class="ocp-metric-mini__label">المتأخر</span>
                </div>
                <div class="ocp-metric-mini">
                    <span class="ocp-metric-mini__value ocp-ltr"><?= $lastPayment['date'] !== null ? date('Y/m/d', strtotime($lastPayment['date'])) : '-' ?></span>
                    <span class="ocp-metric-mini__label">آخر دفعة</span>
                </div>
            </div>

            <div class="ocp-status-bar__divider"></div>

            <?php // Counters ?>
            <div class="ocp-status-bar__counters">
                <?php $brokenPromises = $riskData['broken_promises'] ?? 0; ?>
                <?php if ($brokenPromises > 0): ?>
                <span class="ocp-counter-chip ocp-counter-chip--danger">
                    <i class="fa fa-warning"></i>
                    <span class="ocp-counter-chip__count"><?= $brokenPromises ?></span> وعد غير منفذ
                </span>
                <?php endif; ?>
                
                <?php $overdueTasks = 0; foreach ($kanbanData as $col) $overdueTasks += $col['overdue']; ?>
                <?php if ($overdueTasks > 0): ?>
                <span class="ocp-counter-chip ocp-counter-chip--danger">
                    <i class="fa fa-clock-o"></i>
                    <span class="ocp-counter-chip__count"><?= $overdueTasks ?></span> مهمة متأخرة
                </span>
                <?php endif; ?>
            </div>

            <div class="ocp-status-bar__divider"></div>

            <?php // Risk Badge ?>
            <div class="ocp-risk-badge ocp-risk-badge--<?= $riskLevel ?>">
                <span class="ocp-risk-badge__dot"></span>
                <?= $riskLevelArabic[$riskLevel] ?? 'غير محدد' ?>
            </div>

            <div class="ocp-status-bar__divider"></div>

            <?php // Assignee ?>
            <div class="ocp-assignee">
                <div class="ocp-assignee__avatar">
                    <i class="fa fa-user"></i>
                </div>
                <span class="ocp-assignee__name"><?= Html::encode($contract->followedBy ? $contract->followedBy->username : 'غير محدد') ?></span>
            </div>
        </div>
    </div>

    <?php // ═══ MAIN CONTENT ═══ ?>
    <div class="ocp-container" style="padding-top: var(--ocp-space-xl);">

        <?php // ═══ SMART ALERTS ═══ ?>
        <?php if (!empty($alerts)): ?>
        <div class="ocp-section">
            <div class="ocp-alerts">
                <?php foreach ($alerts as $alert): ?>
                <div class="ocp-alert ocp-alert--<?= $alert['severity'] ?>">
                    <div class="ocp-alert__icon">
                        <i class="fa <?= $alert['icon'] ?>"></i>
                    </div>
                    <div class="ocp-alert__body">
                        <div class="ocp-alert__title"><?= Html::encode($alert['title']) ?></div>
                        <div class="ocp-alert__desc"><?= Html::encode($alert['description']) ?></div>
                    </div>
                    <?php if (!empty($alert['cta'])): ?>
                    <div class="ocp-alert__cta">
                        <button class="ocp-alert__cta-btn" data-action="<?= $alert['cta']['action'] ?>" onclick="OCP.handleAlertCTA(this)">
                            <?= Html::encode($alert['cta']['label']) ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php // ═══ TWO COLUMN LAYOUT ═══ ?>
        <div class="ocp-grid-2col">

            <?php // ═══ LEFT COLUMN (Main) ═══ ?>
            <div>
                <?php // ACTION CENTER ?>
                <div class="ocp-section">
                    <div class="ocp-action-center">
                        <div class="ocp-action-center__title">
                            <i class="fa fa-bolt"></i>
                            مركز الأفعال — ماذا تريد أن تفعل الآن؟
                        </div>
                        <div class="ocp-action-grid">
                            <button class="ocp-action-btn" data-action="call" onclick="OCP.openPanel('call')">
                                <span class="ocp-action-btn__shortcut">C</span>
                                <div class="ocp-action-btn__icon ocp-action-btn__icon--call"><i class="fa fa-phone"></i></div>
                                <span class="ocp-action-btn__label">تسجيل اتصال</span>
                            </button>
                            <button class="ocp-action-btn" data-action="promise" onclick="OCP.openPanel('promise')">
                                <span class="ocp-action-btn__shortcut">P</span>
                                <div class="ocp-action-btn__icon ocp-action-btn__icon--promise"><i class="fa fa-handshake-o"></i></div>
                                <span class="ocp-action-btn__label">وعد دفع</span>
                            </button>
                            <button class="ocp-action-btn" data-action="visit" onclick="OCP.openPanel('visit')">
                                <span class="ocp-action-btn__shortcut">V</span>
                                <div class="ocp-action-btn__icon ocp-action-btn__icon--visit"><i class="fa fa-car"></i></div>
                                <span class="ocp-action-btn__label">تسجيل زيارة</span>
                            </button>
                            <button class="ocp-action-btn" data-action="sms" onclick="OCP.openPanel('sms')">
                                <span class="ocp-action-btn__shortcut">S</span>
                                <div class="ocp-action-btn__icon ocp-action-btn__icon--sms"><i class="fa fa-comment"></i></div>
                                <span class="ocp-action-btn__label">إرسال تذكير</span>
                            </button>
                            <button class="ocp-action-btn" data-action="legal" onclick="OCP.openPanel('legal')">
                                <span class="ocp-action-btn__shortcut">L</span>
                                <div class="ocp-action-btn__icon ocp-action-btn__icon--legal"><i class="fa fa-gavel"></i></div>
                                <span class="ocp-action-btn__label">تحويل للقضائي</span>
                            </button>
                            <button class="ocp-action-btn ocp-action-more-btn" onclick="OCP.toggleMoreActions()">
                                <div class="ocp-action-btn__icon" style="background:var(--ocp-border-light);color:var(--ocp-text-muted)"><i class="fa fa-ellipsis-h"></i></div>
                                <span class="ocp-action-btn__label">المزيد</span>
                            </button>
                        </div>
                        <?php // Hidden extra actions ?>
                        <div class="ocp-action-grid ocp-hidden" id="ocp-more-actions" style="margin-top:var(--ocp-space-md)">
                            <button class="ocp-action-btn" data-action="review" onclick="OCP.openPanel('review')">
                                <div class="ocp-action-btn__icon ocp-action-btn__icon--review"><i class="fa fa-user-circle"></i></div>
                                <span class="ocp-action-btn__label">طلب مراجعة مدير</span>
                            </button>
                            <button class="ocp-action-btn" data-action="note" onclick="OCP.openPanel('note')">
                                <div class="ocp-action-btn__icon ocp-action-btn__icon--note"><i class="fa fa-sticky-note"></i></div>
                                <span class="ocp-action-btn__label">إضافة ملاحظة</span>
                            </button>
                            <button class="ocp-action-btn" data-action="freeze" onclick="OCP.openPanel('freeze')">
                                <div class="ocp-action-btn__icon ocp-action-btn__icon--freeze"><i class="fa fa-pause-circle"></i></div>
                                <span class="ocp-action-btn__label">تجميد المتابعة</span>
                            </button>
                        </div>
                    </div>
                </div>

                <?php // TABS: Timeline / Kanban / Financial ?>
                <div class="ocp-section">
                    <div class="ocp-tabs">
                        <button class="ocp-tab active" data-tab="timeline" onclick="OCP.switchTab('timeline')">
                            <i class="fa fa-clock-o"></i> السجل الزمني
                            <span class="ocp-tab__count"><?= count($timeline) ?></span>
                        </button>
                        <button class="ocp-tab" data-tab="kanban" onclick="OCP.switchTab('kanban')">
                            <i class="fa fa-columns"></i> سير العمل
                            <?php $totalTasks = 0; foreach ($kanbanData as $col) $totalTasks += $col['total']; ?>
                            <span class="ocp-tab__count"><?= $totalTasks ?></span>
                        </button>
                        <button class="ocp-tab" data-tab="financial" onclick="OCP.switchTab('financial')">
                            <i class="fa fa-money"></i> اللقطة المالية
                        </button>
                    </div>

                    <?php // TIMELINE TAB ?>
                    <div class="ocp-tab-content" id="tab-timeline">
                        <?= $this->render('panel/_timeline', ['timeline' => $timeline]) ?>
                    </div>

                    <?php // KANBAN TAB ?>
                    <div class="ocp-tab-content ocp-hidden" id="tab-kanban">
                        <?= $this->render('panel/_kanban', ['kanbanData' => $kanbanData, 'contract' => $contract]) ?>
                    </div>

                    <?php // FINANCIAL TAB ?>
                    <div class="ocp-tab-content ocp-hidden" id="tab-financial">
                        <?= $this->render('panel/_financial', ['financials' => $financials]) ?>
                    </div>
                </div>
            </div>

            <?php // ═══ RIGHT COLUMN (AI + Sidebar) ═══ ?>
            <div>
                <?php // AI SUGGESTION PANEL ?>
                <?= $this->render('panel/_ai_suggestions', ['aiData' => $aiData, 'contract' => $contract]) ?>
            </div>

        </div>
    </div>

    <?php // ═══ SIDE PANELS (Hidden by default) ═══ ?>
    <div class="ocp-side-panel__overlay" id="ocp-overlay" onclick="OCP.closePanel()"></div>
    <?= $this->render('panel/_side_panels', ['contract' => $contract, 'customer' => $customer]) ?>

    <?php // ═══ TOAST NOTIFICATION ═══ ?>
    <div class="ocp-toast" id="ocp-toast">
        <i class="fa" id="ocp-toast-icon"></i>
        <span id="ocp-toast-message"></span>
    </div>
</div>
