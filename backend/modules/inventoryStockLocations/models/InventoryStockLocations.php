<?php

namespace  backend\modules\inventoryStockLocations\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "os_inventory_stock_locations".
 *
 * @property int $id
 * @property string $locations_name
 * @property int $company_id
 * @property int $created_by
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $last_update_by
 * @property int $is_deleted
 * @property int $number_row
 */
class InventoryStockLocations extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public $number_row;
    public static function tableName()
    {
        return 'os_inventory_stock_locations';
    }
    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'last_update_by',
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
            [['locations_name', 'company_id'], 'required'],
            [['company_id', 'created_by', 'created_at', 'updated_at', 'last_update_by', 'is_deleted','number_row'], 'integer'],
            [['locations_name'], 'string', 'max' => 250],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'locations_name' => Yii::t('app', 'Locations Name'),
            'company_id' => Yii::t('app', 'Company ID'),
            'created_by' => Yii::t('app', 'Created By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'last_update_by' => Yii::t('app', 'Last Update By'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
        ];
    }
    public function  getCreatedBy(){
        return $this->hasOne(\common\models\User::className(), ['id' => 'last_update_by']);
    }
    public function  getUpdateBy(){
        return $this->hasOne(\common\models\User::className(), ['id' => 'last_update_by']);
    }
    public function  getCompanies(){
        return $this->hasOne( backend\modules\companies\models\Companies::className(), ['id' => 'company_id']);
    }
}
