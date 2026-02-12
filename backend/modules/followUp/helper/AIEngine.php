<?php

namespace backend\modules\followUp\helper;

use Yii;
use backend\modules\contracts\models\Contracts;
use backend\modules\followUp\models\FollowUp;
use backend\modules\followUp\models\FollowUpTask;
use backend\modules\followUp\helper\RiskEngine;
use backend\modules\followUp\helper\ContractCalculations;

/**
 * AI Engine — Rule-based recommendation system (Stage 1)
 * 
 * Generates Next Best Action, alternatives, and confidence scores.
 * Designed for future ML upgrade (Stage 3).
 */
class AIEngine
{
    private $contract;
    private $risk;
    private $calc;
    private $dpd;
    private $brokenPromises;
    private $lastContactDate;

    public function __construct($contract)
    {
        $this->contract = $contract;
        $riskEngine = new RiskEngine($contract);
        $this->risk = $riskEngine->assess();
        $this->calc = new ContractCalculations($contract);
        $this->dpd = $riskEngine->getDPD();
        $this->brokenPromises = $riskEngine->getBrokenPromisesCount();
        $this->lastContactDate = $riskEngine->getLastContactDate();
    }

    /**
     * Generate complete AI recommendation
     * @return array
     */
    public function recommend()
    {
        $nba = $this->getNextBestAction();
        $alternatives = $this->getAlternatives($nba['action_type']);
        $playbook = $this->getActivePlaybook();

        return [
            'next_best_action' => $nba,
            'alternatives' => $alternatives,
            'playbook' => $playbook,
            'risk' => $this->risk,
            'dpd' => $this->dpd,
        ];
    }

    /**
     * Determine Next Best Action
     */
    private function getNextBestAction()
    {
        $status = $this->contract->status;

        // Legal mode
        if (in_array($status, ['judiciary', 'legal_department'])) {
            return $this->legalModeAction();
        }

        // Critical: many broken promises or very late
        if ($this->brokenPromises >= 3 || $this->dpd > 30) {
            return $this->criticalAction();
        }

        // Late 8-30 days
        if ($this->dpd > 7 && $this->dpd <= 30) {
            return $this->moderateLateAction();
        }

        // Late 1-7 days
        if ($this->dpd > 0 && $this->dpd <= 7) {
            return $this->mildLateAction();
        }

        // No contact history
        if ($this->lastContactDate === null) {
            return $this->noContactAction();
        }

        // Days since last contact
        $daysSinceContact = $this->lastContactDate 
            ? (int)((strtotime('today') - strtotime($this->lastContactDate)) / 86400)
            : 999;

        // Stale contact
        if ($daysSinceContact > 14) {
            return $this->staleContactAction($daysSinceContact);
        }

        // Default: routine follow-up
        return $this->routineAction();
    }

    private function legalModeAction()
    {
        return [
            'action' => 'مراجعة الملف القضائي',
            'action_type' => 'legal_review',
            'reasons' => [
                'العقد في المرحلة القضائية',
                'يجب متابعة آخر مستجدات القضية',
                'التحقق من وجود جلسات أو تبليغات قادمة',
            ],
            'confidence' => 90,
            'risk_impact' => 'high',
            'icon' => 'fa-gavel',
        ];
    }

    private function criticalAction()
    {
        $reasons = [];
        if ($this->brokenPromises >= 3) {
            $reasons[] = $this->brokenPromises . ' وعود دفع غير منفذة (نمط عدم التزام)';
        }
        if ($this->dpd > 30) {
            $reasons[] = 'تأخير ' . $this->dpd . ' يوم (تجاوز الحد الأقصى)';
        }
        $reasons[] = 'مستوى المخاطر: ' . $this->riskLevelArabic($this->risk['level']);
        $reasons[] = 'يجب التصعيد الفوري لحماية حقوق الشركة';

        return [
            'action' => 'تصعيد فوري + إعداد ملف إنذار',
            'action_type' => 'escalate',
            'reasons' => $reasons,
            'confidence' => 85,
            'risk_impact' => 'high',
            'icon' => 'fa-exclamation-triangle',
        ];
    }

    private function moderateLateAction()
    {
        $reasons = [
            'تأخير ' . $this->dpd . ' يوم (تأخير متوسط)',
            'مبلغ متأخر: ' . number_format($this->calc->deservedAmount()) . ' د.أ',
        ];

        if ($this->brokenPromises > 0) {
            $reasons[] = 'يوجد ' . $this->brokenPromises . ' وعد/وعود دفع غير منفذة';
            return [
                'action' => 'اتصال + طلب وعد دفع مع جدولة زيارة ميدانية',
                'action_type' => 'call',
                'reasons' => $reasons,
                'confidence' => 80,
                'risk_impact' => 'medium',
                'icon' => 'fa-phone',
            ];
        }

        $reasons[] = 'المطلوب الاتصال بالعميل وأخذ وعد دفع ملزم';

        return [
            'action' => 'اتصال + وعد دفع إلزامي',
            'action_type' => 'call',
            'reasons' => $reasons,
            'confidence' => 82,
            'risk_impact' => 'medium',
            'icon' => 'fa-phone',
        ];
    }

    private function mildLateAction()
    {
        return [
            'action' => 'اتصال تذكيري + إرسال تنبيه',
            'action_type' => 'call',
            'reasons' => [
                'تأخير بسيط (' . $this->dpd . ' يوم)',
                'التذكير المبكر يزيد احتمال الدفع بنسبة عالية',
                'مبلغ متأخر: ' . number_format($this->calc->deservedAmount()) . ' د.أ',
            ],
            'confidence' => 88,
            'risk_impact' => 'low',
            'icon' => 'fa-phone',
        ];
    }

    private function noContactAction()
    {
        return [
            'action' => 'إجراء أول اتصال مع العميل',
            'action_type' => 'call',
            'reasons' => [
                'لا يوجد سجل تواصل سابق مع العميل',
                'التواصل الأول يؤسس للعلاقة ويحدد نمط السداد',
                'مطلوب تسجيل بيانات التواصل وتقييم الوضع',
            ],
            'confidence' => 92,
            'risk_impact' => 'low',
            'icon' => 'fa-phone',
        ];
    }

    private function staleContactAction($days)
    {
        return [
            'action' => 'اتصال متابعة (لا تواصل منذ ' . $days . ' يوم)',
            'action_type' => 'call',
            'reasons' => [
                'مضى ' . $days . ' يوم بدون أي تواصل',
                'الفترة الطويلة بدون تواصل تزيد مخاطر عدم السداد',
                'يجب استعادة التواصل وتحديث وضع العميل',
            ],
            'confidence' => 85,
            'risk_impact' => 'medium',
            'icon' => 'fa-phone',
        ];
    }

    private function routineAction()
    {
        $overdue = $this->calc->deservedAmount();
        if ($overdue > 0) {
            return [
                'action' => 'متابعة دورية — تحصيل مستحقات',
                'action_type' => 'call',
                'reasons' => [
                    'مبلغ مستحق: ' . number_format($overdue) . ' د.أ',
                    'الوضع العام مستقر ولكن يوجد مستحقات',
                    'المتابعة الدورية تحافظ على معدل التحصيل',
                ],
                'confidence' => 75,
                'risk_impact' => 'low',
                'icon' => 'fa-phone',
            ];
        }

        return [
            'action' => 'العقد بوضع جيد — لا إجراء مطلوب حالياً',
            'action_type' => 'none',
            'reasons' => [
                'جميع الأقساط المستحقة مدفوعة',
                'لا توجد وعود دفع معلقة',
                'العقد ملتزم بالسداد',
            ],
            'confidence' => 95,
            'risk_impact' => 'low',
            'icon' => 'fa-check-circle',
        ];
    }

    /**
     * Get alternative actions
     */
    private function getAlternatives($primaryType)
    {
        $all = [
            ['action' => 'تسجيل اتصال', 'type' => 'call', 'icon' => 'fa-phone'],
            ['action' => 'تسجيل وعد دفع', 'type' => 'promise', 'icon' => 'fa-handshake-o'],
            ['action' => 'جدولة زيارة ميدانية', 'type' => 'visit', 'icon' => 'fa-car'],
            ['action' => 'إرسال تذكير SMS', 'type' => 'sms', 'icon' => 'fa-comment'],
            ['action' => 'تحويل للقضائي', 'type' => 'legal', 'icon' => 'fa-gavel'],
            ['action' => 'طلب مراجعة مدير', 'type' => 'review', 'icon' => 'fa-user-circle'],
            ['action' => 'إضافة ملاحظة', 'type' => 'note', 'icon' => 'fa-sticky-note'],
        ];

        // Return 2 alternatives that are not the primary type
        $filtered = array_filter($all, function ($item) use ($primaryType) {
            return $item['type'] !== $primaryType;
        });

        return array_slice(array_values($filtered), 0, 2);
    }

    /**
     * Determine which playbook applies
     */
    public function getActivePlaybook()
    {
        $status = $this->contract->status;

        if (in_array($status, ['judiciary', 'legal_department'])) {
            return $this->playbookD();
        }

        if ($this->brokenPromises >= 3 || $this->dpd > 30) {
            return $this->playbookC();
        }

        if ($this->dpd > 7 && $this->dpd <= 30) {
            return $this->playbookB();
        }

        if ($this->dpd > 0 && $this->dpd <= 7) {
            return $this->playbookA();
        }

        return null;
    }

    private function playbookA()
    {
        $steps = [
            ['when' => 'اليوم', 'what' => 'اتصال تذكيري + إرسال تنبيه SMS', 'done' => false],
            ['when' => 'بعد 48 ساعة', 'what' => 'إذا لم يدفع: أخذ وعد دفع إلزامي', 'done' => false],
            ['when' => 'بعد 5 أيام', 'what' => 'إذا لم يستجب: تصعيد داخلي (ليس قضائي)', 'done' => false],
        ];

        // Auto-mark steps based on existing follow-ups
        $recentFollowUps = $this->getRecentFollowUps(7);
        if (count($recentFollowUps) > 0) {
            $steps[0]['done'] = true;
        }

        return [
            'id' => 'A',
            'name' => 'تأخير بسيط (1-7 أيام)',
            'steps' => $steps,
            'current_step' => $this->getCurrentStep($steps),
        ];
    }

    private function playbookB()
    {
        $steps = [
            ['when' => 'اليوم', 'what' => 'اتصال + وعد دفع + تحديد موعد مراجعة', 'done' => false],
            ['when' => 'موعد الوعد', 'what' => 'إذا وعد غير منفذ: زيارة/تحصيل ميداني', 'done' => false],
            ['when' => 'بعد أسبوع', 'what' => 'إذا لا استجابة: تصعيد للإدارة', 'done' => false],
        ];

        $recentFollowUps = $this->getRecentFollowUps(14);
        if (count($recentFollowUps) > 0) {
            $steps[0]['done'] = true;
        }

        return [
            'id' => 'B',
            'name' => 'تأخير متوسط (8-30 يوم)',
            'steps' => $steps,
            'current_step' => $this->getCurrentStep($steps),
        ];
    }

    private function playbookC()
    {
        $steps = [
            ['when' => 'فوراً', 'what' => 'تصعيد مباشر للإدارة', 'done' => false],
            ['when' => 'خلال 48 ساعة', 'what' => 'إعداد ملف إنذار رسمي', 'done' => false],
            ['when' => 'خلال أسبوع', 'what' => 'طلب موافقة مدير على الإجراء القضائي', 'done' => false],
        ];

        return [
            'id' => 'C',
            'name' => 'تأخير حرج (+30 يوم أو 3+ وعود مخلفة)',
            'steps' => $steps,
            'current_step' => 1,
        ];
    }

    private function playbookD()
    {
        $steps = [
            ['when' => 'الآن', 'what' => 'مراجعة حالة الملف القضائي', 'done' => false],
            ['when' => 'حسب الجدول', 'what' => 'متابعة الجلسة/التبليغ القادم', 'done' => false],
            ['when' => 'مستمر', 'what' => 'تحديث السجل القضائي وتوثيق كل إجراء', 'done' => false],
        ];

        return [
            'id' => 'D',
            'name' => 'المرحلة القضائية',
            'steps' => $steps,
            'current_step' => 1,
        ];
    }

    /**
     * Get recent follow-ups within N days
     */
    private function getRecentFollowUps($days)
    {
        return FollowUp::find()
            ->where(['contract_id' => $this->contract->id])
            ->andWhere(['>=', 'date_time', date('Y-m-d H:i:s', strtotime("-{$days} days"))])
            ->all();
    }

    /**
     * Determine current step in playbook
     */
    private function getCurrentStep($steps)
    {
        foreach ($steps as $i => $step) {
            if (!$step['done']) {
                return $i + 1;
            }
        }
        return count($steps);
    }

    private function riskLevelArabic($level)
    {
        $map = [
            'low' => 'منخفض',
            'med' => 'متوسط',
            'high' => 'مرتفع',
            'critical' => 'حرج',
        ];
        return isset($map[$level]) ? $map[$level] : $level;
    }
}
