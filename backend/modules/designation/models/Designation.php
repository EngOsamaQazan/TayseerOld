<?php

namespace backend\modules\designation\models;
use common\models\Model;
use Yii;
use \common\models\User;

/**
 * This is the model class for table "{{%designation}}".
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property int $created_by
 * @property int $created_at
 * @property int|null $updated_at
 *
 * @property User $createdBy
 * @property Profile[] $profiles
 */
class Designation extends Model
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%designation}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'status'], 'required'],
            [['status'], 'string'],
            [['created_by', 'created_at', 'updated_at'], 'integer'],
            [['title'], 'string', 'max' => 50],
            [['description'], 'string', 'max' => 250],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['created_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'title' => Yii::t('app', 'Title'),
            'description' => Yii::t('app', 'Description'),
            'status' => Yii::t('app', 'Status'),
            'created_by' => Yii::t('app', 'Created By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery|yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(\common\models\User::className(), ['id' => 'created_by']);
    }

    /**
     * Gets query for [[Profiles]].
     *
     * @return \yii\db\ActiveQuery|ProfileQuery
     */
    public function getProfiles()
    {
        return $this->hasMany(Profile::className(), ['job_title' => 'id']);
    }

    /**
     * {@inheritdoc}
     * @return DesignationQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new DesignationQuery(get_called_class());
    }
}
