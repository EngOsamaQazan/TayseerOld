<?php

namespace backend\modules\hr\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;

/**
 * This is the model class for table "{{%hr_payslip}}".
 * كشف الراتب
 *
 * @property int $id
 * @property int $payroll_run_id
 * @property int $user_id
 * @property float|null $gross_salary
 * @property float|null $total_deductions
 * @property float|null $net_salary
 * @property string|null $status
 * @property string|null $notes
 * @property int $is_deleted
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $last_updated_by
 */
class HrPayslip extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_payslip}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'last_updated_by',
            ],
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('UNIX_TIMESTAMP()'),
            ],
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::class,
                'softDeleteAttributeValues' => [
                    'is_deleted' => true,
                ],
                'replaceRegularDelete' => true,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['payroll_run_id', 'user_id'], 'required'],
            [['payroll_run_id', 'user_id', 'is_deleted', 'created_at', 'created_by', 'updated_at', 'last_updated_by'], 'integer'],
            [['gross_salary', 'total_deductions', 'net_salary'], 'number'],
            [['status'], 'string', 'max' => 30],
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
            'payroll_run_id' => Yii::t('app', 'دورة الرواتب'),
            'user_id' => Yii::t('app', 'الموظف'),
            'gross_salary' => Yii::t('app', 'إجمالي الراتب'),
            'total_deductions' => Yii::t('app', 'إجمالي الخصومات'),
            'net_salary' => Yii::t('app', 'صافي الراتب'),
            'status' => Yii::t('app', 'الحالة'),
            'notes' => Yii::t('app', 'ملاحظات'),
            'is_deleted' => Yii::t('app', 'محذوف'),
            'created_at' => Yii::t('app', 'تاريخ الإنشاء'),
            'created_by' => Yii::t('app', 'أنشئ بواسطة'),
            'updated_at' => Yii::t('app', 'تاريخ التعديل'),
            'last_updated_by' => Yii::t('app', 'عُدّل بواسطة'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPayrollRun()
    {
        return $this->hasOne(HrPayrollRun::class, ['id' => 'payroll_run_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLines()
    {
        return $this->hasMany(HrPayslipLine::class, ['payslip_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     */
    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::class);
        return $query->notDeleted();
    }
}
