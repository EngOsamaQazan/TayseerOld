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
 * This is the model class for table "{{%hr_attendance}}".
 * سجل الحضور والانصراف
 *
 * @property int $id
 * @property int $user_id
 * @property string $attendance_date
 * @property string|null $check_in
 * @property string|null $check_out
 * @property string|null $status
 * @property string|null $source
 * @property int|null $shift_id
 * @property float|null $work_hours
 * @property float|null $overtime_hours
 * @property string|null $late_minutes
 * @property string|null $early_leave_minutes
 * @property string|null $notes
 * @property int $is_deleted
 * @property int $created_at
 * @property int $created_by
 * @property int $updated_at
 * @property int $last_updated_by
 */
class HrAttendance extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%hr_attendance}}';
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
            [['user_id', 'attendance_date'], 'required'],
            [['user_id', 'shift_id', 'is_deleted', 'created_at', 'created_by', 'updated_at', 'last_updated_by'], 'integer'],
            [['attendance_date', 'check_in', 'check_out'], 'safe'],
            [['work_hours', 'overtime_hours'], 'number'],
            [['late_minutes', 'early_leave_minutes'], 'string', 'max' => 10],
            [['status'], 'string', 'max' => 30],
            [['source'], 'string', 'max' => 30],
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
            'user_id' => Yii::t('app', 'الموظف'),
            'attendance_date' => Yii::t('app', 'تاريخ الحضور'),
            'check_in' => Yii::t('app', 'وقت الحضور'),
            'check_out' => Yii::t('app', 'وقت الانصراف'),
            'status' => Yii::t('app', 'الحالة'),
            'source' => Yii::t('app', 'المصدر'),
            'shift_id' => Yii::t('app', 'الوردية'),
            'work_hours' => Yii::t('app', 'ساعات العمل'),
            'overtime_hours' => Yii::t('app', 'ساعات العمل الإضافي'),
            'late_minutes' => Yii::t('app', 'دقائق التأخير'),
            'early_leave_minutes' => Yii::t('app', 'دقائق المغادرة المبكرة'),
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
    public function getShift()
    {
        return $this->hasOne(HrWorkShift::class, ['id' => 'shift_id']);
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
