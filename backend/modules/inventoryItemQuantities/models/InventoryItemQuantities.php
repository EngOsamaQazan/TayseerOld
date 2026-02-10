<?php

namespace backend\modules\InventoryItemQuantities\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

use backend\modules\inventorySuppliers\models\InventorySuppliers;
use backend\modules\inventoryStockLocations\models\InventoryStockLocations;
use backend\modules\inventoryItems\models\InventoryItems;
use common\models\User;
/**
 * This is the model class for table "{{%inventory_item_quantities}}".
 *
 * @property int $id
 * @property int $item_id
 * @property int $locations_id
 * @property int $suppliers_id
 * @property int $quantity
 * @property int $created_at
 * @property int $created_by
 * @property int $company_id
 * @property int $number_row
 *
 * @property User $createdBy
 * @property InventoryItems $item
 * @property InventoryStockLocations $locations
 * @property InventorySuppliers $suppliers
 */
class InventoryItemQuantities extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public $number_row;
    public static function tableName()
    {
        return '{{%inventory_item_quantities}}';
    }
    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'last_updated_by',
            ],
            [
                'class' => TimestampBehavior::className(),
                'value' => new Expression('UNIX_TIMESTAMP()'),
            ],
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                'softDeleteAttributeValues' => [
                    'is_deleted' => true
                ],

                'replaceRegularDelete' => true // mutate native `delete()` method
            ],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['item_id', 'locations_id', 'suppliers_id', 'quantity'], 'required'],
            [['item_id', 'locations_id', 'suppliers_id', 'quantity', 'created_at', 'created_by','company_id','number_row'], 'integer'],
            [['locations_id'], 'exist', 'skipOnError' => true, 'targetClass' => InventoryStockLocations::className(), 'targetAttribute' => ['locations_id' => 'id']],
            [['suppliers_id'], 'exist', 'skipOnError' => true, 'targetClass' => InventorySuppliers::className(), 'targetAttribute' => ['suppliers_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['created_by' => 'id']],
            [['item_id'], 'exist', 'skipOnError' => true, 'targetClass' => InventoryItems::className(), 'targetAttribute' => ['item_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'item_id' => Yii::t('app', 'Item ID'),
            'locations_id' => Yii::t('app', 'Locations ID'),
            'suppliers_id' => Yii::t('app', 'Suppliers ID'),
            'quantity' => Yii::t('app', 'Quantity'),
            'created_at' => Yii::t('app', 'Created At'),
            'created_by' => Yii::t('app', 'Created By'),
        ];
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(\common\models\User::className(), ['id' => 'created_by']);
    }

    /**
     * Gets query for [[Item]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInventoryItems()
    {
        return $this->hasOne(\backend\modules\inventoryItems\models\InventoryItems::className(), ['id' => 'item_id']);
    }

    /**
     * Gets query for [[Locations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInventoryStockLocations()
    {
        return $this->hasOne(\backend\modules\inventoryStockLocations\models\InventoryStockLocations::className(), ['id' => 'locations_id']);
    }

    /**
     * Gets query for [[Suppliers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInventorySuppliers()
    {
        return $this->hasOne(\backend\modules\inventorySuppliers\models\InventorySuppliers::className(), ['id' => 'suppliers_id']);
    }
    public function getLocations()
    {
        return $this->hasOne(\backend\modules\inventoryStockLocations\models\InventoryStockLocations::className(), ['id' => 'suppliers_id']);
    }
    public function getCompany() {
        return $this->hasOne(\backend\modules\companies\models\Companies::className(), ['id' => 'company_id']);
    }
}
