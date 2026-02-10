<?php

namespace backend\modules\inventoryItems\models;

use Yii;

/**
 * This is the model class for table "os_contract_inventory_item".
 *
 * @property int $id
 * @property int $contract_id
 * @property int $item_id
 * @property string|null $code
 * @property string|null $notes
 *
 * @property Contracts $contract
 * @property InventoryItems $item
 */
class ContractInventoryItem extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public $number_row;
    public static function tableName()
    {
        return 'os_contract_inventory_item';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['item_id'], 'required'],
            [['contract_id', 'item_id'], 'integer'],
            [['code'], 'string', 'max' => 250],
            [['notes'], 'string', 'max' => 500],
            [['contract_id'], 'exist', 'skipOnError' => true, 'targetClass' => \backend\modules\contracts\models\Contracts::className(), 'targetAttribute' => ['contract_id' => 'id']],
            [['item_id'], 'exist', 'skipOnError' => true, 'targetClass' =>  InventoryItems::className(), 'targetAttribute' => ['item_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'contract_id' => 'Contract ID',
            'item_id' => 'Item ID',
            'code' => 'Code',
            'notes' => 'Notes',
        ];
    }

    /**
     * Gets query for [[Contract]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getContract()
    {
        return $this->hasOne(Contracts::className(), ['id' => 'contract_id']);
    }

    /**
     * Gets query for [[Item]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getItem()
    {
        return $this->hasOne(InventoryItems::className(), ['id' => 'item_id']);
    }
}
