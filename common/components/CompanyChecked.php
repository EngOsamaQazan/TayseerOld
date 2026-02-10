<?php


namespace common\components;

use backend\modules\bancks\Bancks;
use backend\modules\companies\models\Companies;
use backend\modules\companyBanks\models\CompanyBanks;

class CompanyChecked
{
    public $id ;
    function therePrimaryCompany()
    {

        $companies = Companies::find()->all();
        foreach ($companies as $company) {
            if ($company->is_primary_company == 1) {
                return 0;
            }
        }
        return 1;
    }

    function findPrimaryCompany()
    {
        $companies = Companies::find()->all();
        foreach ($companies as $company) {
            if ($company->is_primary_company == 1) {
                return $company;
            }
        }
        return '';
    }

    function findPrimaryCompanyBancks()
    {
        $CompanyChecked = new CompanyChecked();
        $primary_banck = $CompanyChecked->findPrimaryCompany();
        $bancks = CompanyBanks::find()->where(['company_id' => $primary_banck->id])->all();
        $all_bancks = '';
        foreach ($bancks as $banck) {
            $bankName = \backend\modules\bancks\models\Bancks::findOne(['id'=>$banck->bank_id]);
            if ($all_bancks == '') {
                $all_bancks .= $bankName->name . ' رقم الحساب' .$banck->bank_number;
            } else {
                $all_bancks .= ',' .$bankName->name . ' رقم الحساب ' .$banck->bank_number;
            }

        }
        return $all_bancks;
    }
    function is_primary_company(){

        $companies = Companies::find()->all();
        foreach ($companies as $company) {
            if ($company->is_primary_company == 1) {
                return $company->id;
            }
        }
        return 0;
    }
    function findCompany(){
        $company = Companies::find()->where(['id'=>$this->id])->one();
 
        return $company;
    }
}