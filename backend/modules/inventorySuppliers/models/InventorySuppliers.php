<?php

namespace backend\modules\inventorySuppliers\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;

class InventorySuppliers extends ActiveRecord
{
    public $number_row;

    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'last_update_by',
            ],
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('UNIX_TIMESTAMP()'),
            ],
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::class,
                'softDeleteAttributeValues' => ['is_deleted' => true],
                'replaceRegularDelete' => true,
            ],
        ];
    }

    public static function tableName()
    {
        return '{{%inventory_suppliers}}';
    }

    public function rules()
    {
        return [
            [['name', 'phone_number'], 'required'],
            [['company_id', 'created_by', 'created_at', 'updated_at', 'last_update_by', 'is_deleted', 'number_row'], 'integer'],
            [['name', 'adress'], 'string', 'max' => 250],
            [['phone_number'], 'string', 'max' => 50],
            [['phone_number'], 'unique'],
            [['name'], 'unique'],
            [['company_id', 'adress'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'             => 'م',
            'company_id'     => 'الشركة',
            'name'           => 'اسم المورد',
            'adress'         => 'العنوان',
            'phone_number'   => 'رقم الهاتف',
            'created_by'     => 'أنشئ بواسطة',
            'created_at'     => 'تاريخ الإنشاء',
            'updated_at'     => 'آخر تحديث',
            'last_update_by' => 'آخر تعديل بواسطة',
        ];
    }

    /* ── العلاقات (مصلحة) ── */
    public function getCreatedByUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'created_by']);
    }

    public function getCompany()
    {
        return $this->hasOne(\backend\modules\companies\models\Companies::class, ['id' => 'company_id']);
    }

    /* ── SoftDelete scope (مصلح — كان ناقص) ── */
    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::class);
        return $query->notDeleted();
    }
}
