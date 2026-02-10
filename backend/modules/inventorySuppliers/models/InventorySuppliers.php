<?php

namespace backend\modules\inventorySuppliers\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "os_inventory_suppliers".
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $adress
 * @property string $phone_number
 * @property int $created_by
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $last_update_by
 * @property int|null $number_row
 * @property int $is_deleted
 */
class InventorySuppliers extends \yii\db\ActiveRecord {
    public $number_row;
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
    public static function tableName() {
        return '{{%inventory_suppliers}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules() {
        return [
            [['company_id', 'name', 'adress', 'phone_number'], 'required'],
            [['company_id', 'created_by', 'created_at', 'updated_at', 'last_update_by', 'is_deleted','number_row'], 'integer'],
            [['name', 'adress'], 'string', 'max' => 250],
            [['phone_number'], 'string', 'max' => 50],
            [['phone_number'], 'unique'],
            [['name'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels() {
        return [
            'id' => Yii::t('app', 'ID'),
            'company_id' => Yii::t('app', 'Company ID'),
            'name' => Yii::t('app', 'Name'),
            'adress' => Yii::t('app', 'Adress'),
            'phone_number' => Yii::t('app', 'Phone Number'),
            'created_by' => Yii::t('app', 'Created By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'last_update_by' => Yii::t('app', 'Last Update By'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
        ];
    }

    public function getCreatedBy() {
        return $this->hasOne(\common\models\User::className(), ['id' => 'last_update_by']);
    }

    public function getCompany() {
        return $this->hasOne(\backend\modules\companies\models\Companies::className(), ['id' => 'company_id']);
    }

}
