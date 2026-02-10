<?php

namespace backend\modules\shareholders\models;

use Yii;

/**
 * This is the model class for table "{{%shareholders}}".
 *
 * @property int $id
 * @property string $name
 *
 * @property Shares[] $shares
 */
class Shareholders extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%shareholders}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 250],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getShares()
    {
        return $this->hasMany(Shares::className(), ['shareholder_id' => 'id']);
    }

    /**
     * @inheritdoc
     * @return ShareholdersQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ShareholdersQuery(get_called_class());
    }
}
