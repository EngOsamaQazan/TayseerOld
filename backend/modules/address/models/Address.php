<?php

namespace backend\modules\address\models;

use Yii;

/**
 * This is the model class for table "{{%address}}".
 *
 * @property int $id
 * @property int $customers_id
 * @property string $address
 * @property string $created_at
 * @property string $updated_at
 * @property int $is_deleted
 *
 * @property Customers $customers
 */
class Address extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%address}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[ 'is_deleted','customers_id'], 'integer'],
            [[ 'address','address_type'], 'required'],
            [['address_type', 'updated_at'], 'safe'],
            [['address'], 'string', 'max' => 255],
            [['address_type'], 'integer', 'max' => 11],
           [['customers_id'], 'exist', 'skipOnError' => true, 'targetClass' => \backend\modules\customers\models\Customers::className(), 'targetAttribute' => ['customers_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     * {@inheritdoc}d
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'customers_id' => Yii::t('app', 'Customers ID'),
            'address_type' => Yii::t('app', 'Address Type'),
            'address' => Yii::t('app', 'Address'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCustomers()
    {
        return $this->hasOne(Customers::className(), ['id' => 'customers_id']);
    }

    /**
     * {@inheritdoc}
     * @return AddressQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new AddressQuery(get_called_class());
    }
}
