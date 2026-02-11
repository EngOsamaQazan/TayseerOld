<?php

namespace backend\modules\inventoryInvoices\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;

class InventoryInvoices extends \yii\db\ActiveRecord
{
    /* أنواع الفواتير */
    const TYPE_CASH    = 0;
    const TYPE_CREDIT  = 1;
    const TYPE_MIXED   = 2;

    /* حالات الفاتورة */
    const STATUS_DRAFT    = 'draft';
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public static function tableName()
    {
        return 'os_inventory_invoices';
    }

    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'last_updated_by',
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

    public function rules()
    {
        return [
            [['suppliers_id'], 'required'],
            [['inventory_items_id', 'company_id', 'type', 'suppliers_id', 'created_at', 'updated_at', 'created_by', 'last_updated_by', 'is_deleted', 'approved_by', 'approved_at'], 'integer'],
            [['total_amount'], 'number'],
            [['date'], 'safe'],
            [['invoice_number'], 'string', 'max' => 50],
            [['rejection_reason'], 'string', 'max' => 500],
            [['invoice_notes'], 'string'],
            [['status'], 'in', 'range' => [self::STATUS_DRAFT, self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED]],
            [['status'], 'default', 'value' => self::STATUS_APPROVED],
            [['invoice_number', 'invoice_notes', 'rejection_reason', 'company_id', 'type'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'               => 'م',
            'invoice_number'   => 'رقم الفاتورة',
            'company_id'       => 'الشركة',
            'total_amount'     => 'المبلغ الإجمالي',
            'type'             => 'طريقة الدفع',
            'suppliers_id'     => 'المورد',
            'status'           => 'الحالة',
            'approved_by'      => 'تمت الموافقة بواسطة',
            'approved_at'      => 'تاريخ الموافقة',
            'rejection_reason' => 'سبب الرفض',
            'invoice_notes'    => 'ملاحظات',
            'date'             => 'التاريخ',
            'created_at'       => 'تاريخ الإنشاء',
            'updated_at'       => 'آخر تحديث',
            'created_by'       => 'أنشئ بواسطة',
            'last_updated_by'  => 'آخر تعديل بواسطة',
        ];
    }

    /* مصلح: لم يعد يفرض تاريخ اليوم — يسمح للمستخدم بتحديد التاريخ */
    public function beforeSave($insert)
    {
        if ($insert && empty($this->date)) {
            $this->date = date('Y-m-d');
        }
        return parent::beforeSave($insert);
    }

    /* ── العلاقات ── */
    public function getCreatedBy()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'created_by']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'last_updated_by']);
    }

    public function getCompany()
    {
        return $this->hasOne(\backend\modules\companies\models\Companies::class, ['id' => 'company_id']);
    }

    public function getSuppliers()
    {
        return $this->hasOne(\backend\modules\inventorySuppliers\models\InventorySuppliers::class, ['id' => 'suppliers_id']);
    }

    public function getLineItems()
    {
        return $this->hasMany(\backend\modules\itemsInventoryInvoices\models\ItemsInventoryInvoices::class, ['inventory_invoices_id' => 'id'])
            ->andWhere(['is_deleted' => 0]);
    }

    public function getApprovedByUser()
    {
        return $this->hasOne(\common\models\User::class, ['id' => 'approved_by']);
    }

    /* ── مساعدات ── */
    public static function getTypeList()
    {
        return [
            self::TYPE_CASH   => 'نقدي',
            self::TYPE_CREDIT => 'ذمم (آجل)',
            self::TYPE_MIXED  => 'مختلط',
        ];
    }

    public function getTypeLabel()
    {
        $list = self::getTypeList();
        return $list[$this->type] ?? '-';
    }

    public static function getStatusList()
    {
        return [
            self::STATUS_DRAFT    => 'مسودة',
            self::STATUS_PENDING  => 'معلق',
            self::STATUS_APPROVED => 'معتمد',
            self::STATUS_REJECTED => 'مرفوض',
        ];
    }

    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::class);
        return $query->notDeleted();
    }
}
