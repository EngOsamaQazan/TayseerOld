<?php
/**
 * نموذج إضافة مجموعة أصناف دفعة واحدة
 */
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use backend\modules\inventorySuppliers\models\InventorySuppliers;

$suppliers = ArrayHelper::map(InventorySuppliers::find()->andWhere(['is_deleted' => 0])->all(), 'id', 'name');
?>

<style>
.batch-form-wrap { padding: 10px; }
.batch-row { display: flex; gap: 8px; align-items: flex-end; margin-bottom: 8px; padding: 10px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; }
.batch-row .bf-col { flex: 1; min-width: 0; }
.batch-row .bf-col label { font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 3px; display: block; }
.batch-row .bf-col input, .batch-row .bf-col select { width: 100%; padding: 6px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; }
.batch-row .bf-col input:focus, .batch-row .bf-col select:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 0 2px rgba(59,130,246,0.15); }
.bf-num { width: 30px !important; flex: none !important; text-align: center; font-weight: 700; color: #64748b; font-size: 14px; padding-top: 22px; }
.bf-remove { flex: none !important; width: 32px; }
.bf-remove button { background: #fee2e2; color: #dc2626; border: none; width: 30px; height: 30px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.bf-remove button:hover { background: #fecaca; }
.bf-actions { display: flex; gap: 8px; margin-top: 10px; }
.bf-add-btn { background: #0ea5e9; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; }
.bf-add-btn:hover { background: #0284c7; }
.bf-hint { color: #94a3b8; font-size: 12px; margin-top: 6px; }
</style>

<div class="batch-form-wrap">
    <form id="batchItemsForm" method="post">
        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
        
        <div id="batchRows">
            <?php for ($i = 0; $i < 3; $i++): ?>
            <div class="batch-row" data-idx="<?= $i ?>">
                <div class="bf-num"><?= $i + 1 ?></div>
                <div class="bf-col" style="flex:2">
                    <?php if ($i === 0): ?><label>اسم الصنف *</label><?php endif ?>
                    <input type="text" name="items[<?= $i ?>][item_name]" placeholder="اسم الصنف" required>
                </div>
                <div class="bf-col">
                    <?php if ($i === 0): ?><label>الباركود</label><?php endif ?>
                    <input type="text" name="items[<?= $i ?>][item_barcode]" placeholder="الباركود" style="direction:ltr;font-family:monospace">
                </div>
                <div class="bf-col">
                    <?php if ($i === 0): ?><label>الرقم التسلسلي</label><?php endif ?>
                    <input type="text" name="items[<?= $i ?>][serial_number]" placeholder="الرقم التسلسلي" style="direction:ltr;font-family:monospace">
                </div>
                <div class="bf-col">
                    <?php if ($i === 0): ?><label>التصنيف</label><?php endif ?>
                    <input type="text" name="items[<?= $i ?>][category]" placeholder="التصنيف">
                </div>
                <div class="bf-col">
                    <?php if ($i === 0): ?><label>سعر الوحدة</label><?php endif ?>
                    <input type="number" name="items[<?= $i ?>][unit_price]" placeholder="0.00" step="0.01" style="direction:ltr">
                </div>
                <div class="bf-col">
                    <?php if ($i === 0): ?><label>المورد</label><?php endif ?>
                    <select name="items[<?= $i ?>][supplier_id]">
                        <option value="">— المورد —</option>
                        <?php foreach ($suppliers as $sid => $sname): ?>
                            <option value="<?= $sid ?>"><?= Html::encode($sname) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="bf-remove">
                    <?php if ($i > 0): ?>
                    <button type="button" onclick="removeBatchRow(this)" title="حذف"><i class="fa fa-times"></i></button>
                    <?php endif ?>
                </div>
            </div>
            <?php endfor ?>
        </div>

        <div class="bf-actions">
            <button type="button" class="bf-add-btn" onclick="addBatchRow()"><i class="fa fa-plus"></i> إضافة سطر</button>
        </div>
        <p class="bf-hint"><i class="fa fa-info-circle"></i> أضف أسطراً بحسب الحاجة — الحقل المطلوب هو "اسم الصنف" فقط.</p>
    </form>
</div>

<script>
var batchIdx = 3;
var suppliersHtml = <?= json_encode(
    '<option value="">— المورد —</option>' .
    implode('', array_map(function($id, $name) {
        return '<option value="' . $id . '">' . Html::encode($name) . '</option>';
    }, array_keys($suppliers), $suppliers))
) ?>;

function addBatchRow() {
    var i = batchIdx++;
    var html = '<div class="batch-row" data-idx="' + i + '">' +
        '<div class="bf-num">' + (i + 1) + '</div>' +
        '<div class="bf-col" style="flex:2"><input type="text" name="items[' + i + '][item_name]" placeholder="اسم الصنف" required></div>' +
        '<div class="bf-col"><input type="text" name="items[' + i + '][item_barcode]" placeholder="الباركود" style="direction:ltr;font-family:monospace"></div>' +
        '<div class="bf-col"><input type="text" name="items[' + i + '][serial_number]" placeholder="الرقم التسلسلي" style="direction:ltr;font-family:monospace"></div>' +
        '<div class="bf-col"><input type="text" name="items[' + i + '][category]" placeholder="التصنيف"></div>' +
        '<div class="bf-col"><input type="number" name="items[' + i + '][unit_price]" placeholder="0.00" step="0.01" style="direction:ltr"></div>' +
        '<div class="bf-col"><select name="items[' + i + '][supplier_id]">' + suppliersHtml + '</select></div>' +
        '<div class="bf-remove"><button type="button" onclick="removeBatchRow(this)" title="حذف"><i class="fa fa-times"></i></button></div>' +
    '</div>';
    document.getElementById('batchRows').insertAdjacentHTML('beforeend', html);
    renumberRows();
}

function removeBatchRow(btn) {
    btn.closest('.batch-row').remove();
    renumberRows();
}

function renumberRows() {
    var rows = document.querySelectorAll('#batchRows .batch-row');
    rows.forEach(function(row, idx) {
        row.querySelector('.bf-num').textContent = (idx + 1);
    });
}
</script>
