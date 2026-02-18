<?php

namespace backend\modules\followUp\helper;

use Yii;
use DateTime;
use common\helper\LoanContract;
use backend\modules\expenses\models\Expenses;
use backend\modules\judiciary\models\Judiciary;
use backend\modules\contracts\models\Contracts;
use backend\modules\contractInstallment\models\ContractInstallment;
use backend\modules\loanScheduling\models\LoanScheduling;

class ContractCalculations
{
    public $contract_id, $judicary_contract, $contract_model, $modelf;

    /**
     * العقد الأصلي بدون أي تعديلات من التسويات
     * يُستخدم في "الحسابات الأصلية" فقط
     */
    public $original_contract;

    /** @var LoanScheduling|null آخر تسوية فعّالة */
    public $latestSettlement;

    public function __construct($contract_id)
    {
        $this->modelf = new LoanContract;
        $this->contract_id = $contract_id;
        $this->judicary_contract = Judiciary::find()->where([
            'contract_id' => $contract_id,
            'is_deleted' => 0
        ])->all();

        // العقد بعد دمج بيانات التسوية (للتوافق مع الكود القديم)
        $this->contract_model = $this->modelf->findContract($contract_id);

        // العقد الأصلي — مباشرة من قاعدة البيانات بدون تعديلات
        $this->original_contract = Contracts::findOne($contract_id);

        // آخر تسوية فعّالة
        $this->latestSettlement = LoanScheduling::find()
            ->where(['contract_id' => $contract_id])
            ->orderBy(['id' => SORT_DESC])
            ->one();
    }

    /* ═══════════════════════════════════════════════════
     *  الحسابات الأصلية — لا تنظر للتسويات نهائياً
     *  تستخدم original_contract (البيانات الحقيقية)
     * ═══════════════════════════════════════════════════ */

    /**
     * المبلغ الأصلي للعقد
     */
    public function getContractTotal(): float
    {
        return (float)($this->original_contract->total_value ?? 0);
    }

    /**
     * مجموع كل مصاريف Outcome على العقد (كل التصنيفات)
     */
    public function allExpenses(): float
    {
        return (float)(Expenses::find()
            ->where(['contract_id' => $this->contract_id])
            ->sum('amount') ?? 0);
    }

    /**
     * مجموع أتعاب المحاماة من القضايا
     */
    public function allLawyerCosts(): float
    {
        $total = 0;
        if (!empty($this->judicary_contract)) {
            foreach ($this->judicary_contract as $j) {
                $total += (float)($j->lawyer_cost ?? 0);
            }
        }
        return $total;
    }

    /**
     * مجموع رسوم القضية فقط (category_id=4)
     */
    public function caseCost(): float
    {
        return (float)(Expenses::find()
            ->where(['contract_id' => $this->contract_id, 'category_id' => 4])
            ->sum('amount') ?? 0);
    }

    /**
     * المبلغ الإجمالي = أصلي + كل Outcome + أتعاب محاماة
     */
    public function totalDebt(): float
    {
        return $this->getContractTotal() + $this->allExpenses() + $this->allLawyerCosts();
    }

    /**
     * المدفوع — مجموع كل Income على العقد (كل المدفوعات بلا استثناء)
     */
    public function paidAmount($without_loan_condtion = false): float
    {
        $paid = ContractInstallment::find()
            ->andWhere(['contract_id' => $this->contract_id])
            ->sum('amount');
        return (float)($paid ?? 0);
    }

    /**
     * المتبقي = المبلغ الإجمالي - المدفوع
     */
    public function remainingAmount(): float
    {
        return max(0, $this->totalDebt() - $this->paidAmount());
    }

    /**
     * عدد الأشهر من أول قسط (أصلي) حتى اليوم
     */
    public function timeInterval(): int
    {
        $firstDate = $this->original_contract->first_installment_date ?? null;
        if (empty($firstDate)) return 0;
        $d1 = new DateTime($firstDate);
        $d2 = new DateTime(date('Y-m-d'));
        $interval = $d2->diff($d1);
        return $interval->y * 12 + $interval->m;
    }

    /**
     * عدد الأشهر من تاريخ التسوية حتى اليوم
     */
    public function settlementTimeInterval(): int
    {
        if (!$this->latestSettlement) return 0;
        $firstDate = $this->latestSettlement->first_installment_date ?? null;
        if (empty($firstDate)) return 0;
        $d1 = new DateTime($firstDate);
        $d2 = new DateTime(date('Y-m-d'));
        if ($d2 < $d1) return 0;
        $interval = $d2->diff($d1);
        return $interval->y * 12 + $interval->m;
    }

    /**
     * المبلغ المستحق حتى اليوم
     * قضائي بدون تسوية → كامل الدين مستحق فوراً
     * عقد عليه تسوية → حساب حسب أقساط التسوية
     * عقد عادي → حساب حسب أقساط العقد الأصلي
     */
    public function amountShouldBePaid(): float
    {
        $total = $this->totalDebt();

        if ($this->hasJdicary() && !$this->latestSettlement) {
            return $total;
        }

        if ($this->latestSettlement) {
            $months = $this->settlementTimeInterval() + 1;
            $monthly = (float)($this->latestSettlement->monthly_installment ?? 0);
            return min($months * $monthly, $total);
        }

        $months = $this->timeInterval() + 1;
        $monthly = (float)($this->original_contract->monthly_installment_value ?? 0);
        return min($months * $monthly, $total);
    }

    /**
     * المتأخر = المستحق حتى اليوم - المدفوع
     */
    public function deservedAmount(): float
    {
        if ($this->hasJdicary() && !$this->latestSettlement) {
            return max(0, $this->totalDebt() - $this->paidAmount());
        }

        $firstDate = $this->latestSettlement
            ? ($this->latestSettlement->first_installment_date ?? null)
            : ($this->original_contract->first_installment_date ?? null);

        if (empty($firstDate) || date('Y-m-d') < $firstDate) {
            return 0;
        }
        return max(0, $this->amountShouldBePaid() - $this->paidAmount());
    }

    /**
     * القسط الفعّال (تسوية أو أصلي)
     */
    public function effectiveInstallment(): float
    {
        if ($this->latestSettlement) {
            return (float)($this->latestSettlement->monthly_installment ?? 0);
        }
        return (float)($this->original_contract->monthly_installment_value ?? 0);
    }

    /* ═══════════════════════════════════════════════════
     *  هل العقد عليه قضية؟
     * ═══════════════════════════════════════════════════ */

    public function hasJdicary(): bool
    {
        return !empty($this->judicary_contract);
    }

    /* ═══════════════════════════════════════════════════
     *  دوال مساعدة (للتوافق مع الكود القديم)
     * ═══════════════════════════════════════════════════ */

    /** @deprecated use totalDebt() */
    public function totalCosts(): float
    {
        return $this->totalDebt();
    }

    /** @deprecated use totalDebt() */
    public function getContractTotalWithlawyerAndCaseCost(): float
    {
        return $this->totalDebt();
    }

    /** @deprecated use remainingAmount() */
    public function calculationRemainingAmount(): float
    {
        return $this->remainingAmount();
    }

    /** مصاريف مرجعية (category_id=19) */
    public function customerReferance(): float
    {
        return (float)(Expenses::find()
            ->where(['contract_id' => $this->contract_id, 'category_id' => 19])
            ->sum('amount') ?? 0);
    }

    /** أتعاب محاماة (أول قضية) — للتوافق القديم */
    public function lawyerCost()
    {
        if (!empty($this->judicary_contract)) {
            $cost = Judiciary::find()
                ->where(['contract_id' => $this->contract_id])
                ->orderBy(['id' => SORT_DESC])
                ->one();
            return $cost ? (float)$cost->lawyer_cost : 0;
        }
        return 0;
    }

    /** المبلغ التنفيذي */
    public function getExecutedAmount(): float
    {
        $paid = max(0, $this->paidAmount());
        return ($this->original_contract->total_value - $paid) + $this->allLawyerCosts();
    }
}
