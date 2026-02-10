<?php

namespace backend\modules\judiciaryActions\models;

use Yii;

/**
 * This is the model class for table "os_judiciary_actions".
 *
 * @property int $id
 * @property string $name
 */
class JudiciaryActions extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public $number_row;
    public static function tableName()
    {
        return 'os_judiciary_actions';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
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
        ];
    }
     public function getJudiciaryCustomersActions()
    {
        return $this->hasMany(\backend\modules\judiciaryCustomersActions\models\JudiciaryCustomersActions::className(), ['judiciary_actions_id' => 'id']);
    }
}
