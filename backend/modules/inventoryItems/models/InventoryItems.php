<?php


namespace backend\modules\inventoryItems\models;
use Yii;

use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveRecord;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use yii2tech\ar\softdelete\SoftDeleteQueryBehavior;
use \common\models\User;
/**
 * This is the model class for table "os_inventory_items".
 *
 * @property int $id
 * @property string $item_name
 * @property string $item_barcode
 * @property int $created_at
 * @property int $updated_at
 * @property int $created_by
 * @property int|null $last_update_by
 * @property int $is_deleted
 * @property int $number_row
 * @property int $remaining_amount
 */
class InventoryItems extends \yii\db\ActiveRecord {

    public   $remaining_amount;
    public function behaviors() {
        return [
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'last_update_by',
            ],
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
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

    public static function tableName()
    {
        return 'os_inventory_items';
    }

    /**
     * {@inheritdoc}
     */
    public function rules() {
        return [
            [['item_name', 'item_barcode', ], 'required'],
            [['created_at', 'updated_at', 'created_by', 'last_update_by', 'is_deleted'], 'integer'],
            [['item_name'], 'string', 'max' => 50],
            [['item_barcode'], 'string', 'max' => 30],
            [['item_barcode'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels() {
        return [
            'id' => Yii::t('app', 'ID'),
            'item_name' => Yii::t('app', 'Item Name'),
            'item_barcode' => Yii::t('app', 'Item Barcode'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'created_by' => Yii::t('app', 'Created By'),
            'last_update_by' => Yii::t('app', 'Last Update By'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
        ];
    }
    public function  getCreatedBy(){
            return $this->hasOne(User::className(), ['id' => 'last_update_by']);
    }
    public function  getLastUpdateBy(){
        return $this->hasOne(User::className(), ['id' => 'last_update_by']);
    }

    public static function find()
    {
        $query = parent::find();
        $query->attachBehavior('softDelete', SoftDeleteQueryBehavior::className());
        return $query->notDeleted();
    }
}
