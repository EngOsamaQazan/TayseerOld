<?php

namespace backend\modules\judiciaryActions\models;

use Yii;

/**
 * This is the model class for table "os_judiciary_actions".
 *
 * @property int $id
 * @property string $name
 * @property string $action_type
 */
class JudiciaryActions extends \yii\db\ActiveRecord
{
    /* أنواع الإجراءات */
    const TYPE_REQUEST        = 'request';         // طلب إجرائي
    const TYPE_COURT_LETTER   = 'court_letter';    // كتاب صادر عن المحكمة
    const TYPE_JUDGE_DECISION = 'judge_decision';  // قرار قاضي التنفيذ
    const TYPE_PARTY_PETITION = 'party_petition';  // استدعاء مقدم من أحد الأطراف
    const TYPE_INCOMING_INFO  = 'incoming_info';   // معلومه وارده

    /**
     * {@inheritdoc}
     */
    public $number_row;
    public static function tableName()
    {
        return 'os_judiciary_actions';
    }

    /**
     * قائمة أنواع الإجراءات
     */
    public static function getActionTypeList()
    {
        return [
            self::TYPE_REQUEST        => 'طلب إجرائي',
            self::TYPE_COURT_LETTER   => 'كتاب صادر عن المحكمة / دائرة التنفيذ',
            self::TYPE_JUDGE_DECISION => 'قرار قاضي التنفيذ',
            self::TYPE_PARTY_PETITION => 'استدعاء مقدم من أحد الأطراف (دائن أو مدين)',
            self::TYPE_INCOMING_INFO  => 'معلومه وارده',
        ];
    }

    /**
     * اسم نوع الإجراء
     */
    public function getActionTypeLabel()
    {
        $list = self::getActionTypeList();
        return $list[$this->action_type] ?? $this->action_type;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'action_type'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['action_type'], 'in', 'range' => [self::TYPE_REQUEST, self::TYPE_COURT_LETTER, self::TYPE_JUDGE_DECISION, self::TYPE_PARTY_PETITION, self::TYPE_INCOMING_INFO]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => 'الاسم',
            'action_type' => 'نوع الإجراء',
        ];
    }
     public function getJudiciaryCustomersActions()
    {
        return $this->hasMany(\backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions::className(), ['judiciary_actions_id' => 'id']);
    }
}
