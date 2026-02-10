<?php

namespace backend\modules\companies\models;

use backend\modules\companyBanks\models\CompanyBanks;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;
use \common\models\User;
/**
* This is the model class for table "{{%companies}}" 
*
 * @property int $id
 * @property string $name
 * @property string $phone_number
 * @property string $bank_info
 * @property string $logo
 * @property int $created_by
 * @property int $created_at
 * @property int|null $updated_at
 * @property int $is_deleted
 * @property int $number_row
 * @property int $last_updated_by
 * @property boolean $is_primary_company
 * @property int|null $is_primary_company
 * @property int|null $last_updated_by
 * @property string|null $company_social_security_number
 * @property string|null $company_tax_number
 * @property string|null $company_email
 *
 * @property InventoryStockLocations[] $inventoryStockLocations
 * @property InventorySuppliers[] $inventorySuppliers
 */
class Companies extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public $number_row;
    public static function tableName()
    {
        return '{{%companies}}';
    }
    public function behaviors()
    {
        return [

            [
                'class' => TimestampBehavior::className(),
                'value' => time(),
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
            [['name', 'phone_number'], 'required'],
            [['is_primary_company'],'boolean'],
            [['created_by', 'created_at', 'updated_at','is_deleted','number_row'], 'integer'],
            [['name', 'phone_number'], 'string', 'max' => 50],
            [['logo'],'file','extensions' => 'png, jpg'],
            [['company_email'], 'email'],
            [['company_social_security_number', 'company_tax_number','company_address'], 'string', 'max' => 255],

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
            'phone_number' => Yii::t('app', 'Phone Number'),
            'logo' => Yii::t('app', 'Logo'),
            'created_by' => Yii::t('app', 'Created By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
            'is_primary_company' => Yii::t('app', 'Is Primary Company'),
            'company_social_security_number' => Yii::t('app', 'Company Social Security Number'), 
            'company_tax_number' => Yii::t('app', 'Company Tax Number'), 
            'company_email' => Yii::t('app', 'Company Email'),
            'company_address' => Yii::t('app', 'Company Address'),
             
        ];
    }

    /**
     * Gets query for [[InventoryStockLocations]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInventoryStockLocations()
    {
        return $this->hasMany(InventoryStockLocations::className(), ['company_id' => 'id']);
    }

    /**
     * Gets query for [[InventorySuppliers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInventorySuppliers()
    {
        return $this->hasMany(InventorySuppliers::className(), ['company_id' => 'id']);
    }
    public function getCreatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }

    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::className());
        return $query->notDeleted();
    }

    public function getPrimeryBankAccount()
    {
        return $this->hasOne(CompanyBanks::className(), ['company_id' => 'id']);
    }
}
