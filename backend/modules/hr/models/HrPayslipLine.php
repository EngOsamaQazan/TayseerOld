<?php

namespace backend\modules\hr\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%hr_payslip_line}}".
 * بنود كشف الراتب
 *
 * @property int $id
 * @property int $payslip_id
 * @property int $component_id
 * @property string|null $component_type
 * @property float|null $amount
 * @property string|null $notes
 */
class HrPayslipLine extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_payslip_line}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['payslip_id', 'component_id'], 'required'],
            [['payslip_id', 'component_id'], 'integer'],
            [['amount'], 'number'],
            [['component_type'], 'string', 'max' => 30],
            [['notes'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'المعرف'),
            'payslip_id' => Yii::t('app', 'كشف الراتب'),
            'component_id' => Yii::t('app', 'مكون الراتب'),
            'component_type' => Yii::t('app', 'نوع المكون'),
            'amount' => Yii::t('app', 'المبلغ'),
            'notes' => Yii::t('app', 'ملاحظات'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPayslip()
    {
        return $this->hasOne(HrPayslip::class, ['id' => 'payslip_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getComponent()
    {
        return $this->hasOne(HrSalaryComponent::class, ['id' => 'component_id']);
    }
}
