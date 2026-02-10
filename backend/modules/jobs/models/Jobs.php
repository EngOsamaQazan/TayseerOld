<?php

namespace backend\modules\jobs\models;

use Yii;

/**
 * This is the model class for table "os_jobs".
 *
 * @property int $id
 * @property string|null $name
 * @property int|null $job_type
 */
class Jobs extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'os_jobs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['job_type','name'], 'required'],
            [['job_type'], 'integer'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'job_type' => Yii::t('app', 'Job Type'),
        ];
    }
    public function getJobsType()
    {
        return $this->hasMany(JobsType::class, ['id' => 'job_type'])->one();
    }
}
