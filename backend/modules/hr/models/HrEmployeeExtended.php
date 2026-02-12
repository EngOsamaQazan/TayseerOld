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
 * This is the model class for table "{{%hr_employee_extended}}".
 * بيانات الموظف الموسعة
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $employee_code
 * @property string|null $national_id
 * @property string|null $national_id_expiry
 * @property string|null $passport_number
 * @property string|null $passport_expiry
 * @property string|null $date_of_birth
 * @property string|null $blood_group
 * @property float|null $basic_salary
 * @property string|null $salary_currency
 * @property string|null $bank_name
 * @property string|null $iban
 * @property int|null $grade_id
 * @property int|null $branch_id
 * @property int|null $shift_id
 * @property string|null $contract_type
 * @property string|null $contract_start
 * @property string|null $contract_end
 * @property string|null $probation_end
 * @property string|null $notes
 * @property int $is_deleted
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $last_updated_by
 */
class HrEmployeeExtended extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_employee_extended}}';
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
            [['user_id'], 'required'],
            [['user_id', 'grade_id', 'branch_id', 'shift_id', 'is_deleted', 'created_at', 'created_by', 'updated_at', 'last_updated_by'], 'integer'],
            [['basic_salary'], 'number'],
            [['date_of_birth', 'national_id_expiry', 'passport_expiry', 'contract_start', 'contract_end', 'probation_end'], 'safe'],
            [['employee_code', 'national_id', 'passport_number'], 'string', 'max' => 50],
            [['blood_group'], 'string', 'max' => 10],
            [['salary_currency'], 'string', 'max' => 10],
            [['bank_name', 'iban'], 'string', 'max' => 100],
            [['contract_type'], 'string', 'max' => 50],
            [['notes'], 'string'],
            [['user_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'المعرف'),
            'user_id' => Yii::t('app', 'الموظف'),
            'employee_code' => Yii::t('app', 'الرقم الوظيفي'),
            'national_id' => Yii::t('app', 'رقم الهوية'),
            'national_id_expiry' => Yii::t('app', 'تاريخ انتهاء الهوية'),
            'passport_number' => Yii::t('app', 'رقم الجواز'),
            'passport_expiry' => Yii::t('app', 'تاريخ انتهاء الجواز'),
            'date_of_birth' => Yii::t('app', 'تاريخ الميلاد'),
            'blood_group' => Yii::t('app', 'فصيلة الدم'),
            'basic_salary' => Yii::t('app', 'الراتب الأساسي'),
            'salary_currency' => Yii::t('app', 'عملة الراتب'),
            'bank_name' => Yii::t('app', 'اسم البنك'),
            'iban' => Yii::t('app', 'رقم الآيبان'),
            'grade_id' => Yii::t('app', 'الدرجة الوظيفية'),
            'branch_id' => Yii::t('app', 'الفرع'),
            'shift_id' => Yii::t('app', 'الوردية'),
            'contract_type' => Yii::t('app', 'نوع العقد'),
            'contract_start' => Yii::t('app', 'بداية العقد'),
            'contract_end' => Yii::t('app', 'نهاية العقد'),
            'probation_end' => Yii::t('app', 'نهاية فترة التجربة'),
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
    public function getUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGrade()
    {
        return $this->hasOne(HrGrade::class, ['id' => 'grade_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBranch()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'branch_id'])
            ->from('os_location');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShift()
    {
        return $this->hasOne(HrWorkShift::class, ['id' => 'shift_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEmergencyContacts()
    {
        return $this->hasMany(HrEmergencyContact::class, ['user_id' => 'user_id']);
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
