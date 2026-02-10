<?php

namespace backend\modules\followUp\helper;

use Yii;
use DateTime;
use common\helper\LoanContract;
use backend\modules\expenses\models\Expenses;
use backend\modules\judiciary\models\Judiciary;
use backend\modules\contractInstallment\models\ContractInstallment;
use phpDocumentor\Reflection\Types\Boolean;

class ContractCalculations
{
    public $contract_id, $judicary_contract, $contract_model, $modelf;

    public function __construct($contract_id)
    {
        $this->modelf = new LoanContract;
        $this->contract_id = $contract_id;
        $this->judicary_contract = Judiciary::find()->where([
            'contract_id' => $contract_id,
            'is_deleted' => 0
        ])->all();

        $this->contract_model = $this->modelf->findContract($contract_id);
    }

    public function getContractTotalWithlawyerAndCaseCost(): int
    {
        if ($this->hasJdicary()) {
            return $this->getContractTotal() + $this->lawyerCost() + $this->caseCost();
        }
        return $this->getContractTotal();
    }

    public function getContractTotal(): int
    {
        return $this->contract_model->total_value;
    }


    public function hasJdicary(): bool
    {
        return !empty($this->judicary_contract);
    }

    public function timeInterval()
    {
        $d1 = new DateTime($this->contract_model->first_installment_date);
        $d2 = new DateTime(date('Y-m-d'));
        $interval = $d2->diff($d1);
        return $interval->y * 12 + $interval->m;
    }

    public function caseCost()
    {
        $sum_case_cost = 0;
        if (!empty($this->judicary_contract)) {
            $all_case_cost = Expenses::find()->where(['contract_id' => $this->contract_model->id])->andWhere(['category_id' => 4])->all();

            foreach ($all_case_cost as $case_cost) {
                $sum_case_cost = $sum_case_cost + $case_cost->amount;
            }
            return $sum_case_cost;
        }
        return $sum_case_cost;
    }

    public function totalCosts()
    {
        $total_costs = 0;
        if (!empty($this->judicary_contract)) {
            $costs = \backend\modules\judiciary\models\Judiciary::find()
                ->where([
                    'contract_id' => $this->contract_model->id,
                    'is_deleted' => 0 // Assuming '0' means the record is not deleted
                ])
                ->all();
            $total_costs = $this->contract_model->total_value;
            foreach ($costs as $cost) {
                $total_costs += $cost->lawyer_cost;
            }
            $total_costs += $this->caseCost();
            return $total_costs;
        }

        // echo 'contract_model->monthly_installment_value:' . $this->contract_model->monthly_installment_value . '</br>';
        return $this->contract_model->total_value;
    }

    public function amountShouldBePaid()
    {
        $batches_should_be_paid_count = $this->timeInterval() + 1;
        // check if the total installment's less than total contact cost
        $total = (($batches_should_be_paid_count * $this->contract_model->monthly_installment_value) < $this->totalCosts()) ? $batches_should_be_paid_count * $this->contract_model->monthly_installment_value : $this->totalCosts();
        return $total;
    }

    public function paidAmount($without_loan_condtion = false)
    {
        if ($this->contract_model->is_loan == 1 and !$without_loan_condtion) {
            $paid_amount = ContractInstallment::find()
                ->andWhere(['contract_id' => $this->contract_model->id])->andwhere(['>', 'date', $this->contract_model->loan_scheduling_new_instalment_date])->sum('amount');
        } else {
            $paid_amount = ContractInstallment::find()
                ->andWhere(['contract_id' => $this->contract_model->id])
                ->sum('amount');
        }
        return $paid_amount;
    }

    public function deservedAmount()
    {
        return(date('Y-m-d') >= $this->contract_model->first_installment_date) ? $this->amountShouldBePaid() - $this->paidAmount() : 0;
    }

    public function remainingAmount()
    {
        $total_value = ($this->totalCosts() > 0) ? $this->totalCosts() : 0;
        return $total_value - $this->paidAmount();
    }

    public function calculationRemainingAmount()
    {
        $remaining_amount = ($this->getContractTotalWithlawyerAndCaseCost() + $this->customerReferance()) - $this->paidAmount(true);
        return $remaining_amount;
    }

    public function customerReferance()
    {
        return Expenses::find()->andWhere(['contract_id' => $this->contract_model->id])->andWhere(['category_id' => 19])->sum('amount');
    }

    public function lawyerCost()
    {
        if (!empty($this->judicary_contract)) {
            $cost = Judiciary::find()->where(['contract_id' => $this->contract_model->id])->orderBy(['contract_id' => SORT_DESC])->one();
            if (empty($cost)) {
                return Yii::t('app', 'لا يوجد');
            } else {
                return $cost->lawyer_cost;
            }
        }
    }
    public function getExecutedAmount()
    {

        $paid_amount = ($this->paidAmount() > 0) ? $this->paidAmount() : 0;

        return($this->contract_model->total_value - $paid_amount) + $this->lawyerCost();
    }

}
