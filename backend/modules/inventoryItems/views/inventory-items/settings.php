<?php
/**
 * شاشة الإعدادات — إدارة الموردين والمواقع في صفحة واحدة
 */
use yii\helpers\Url;
use yii\helpers\Html;

$this->title = 'إدارة المخزون';
$this->registerCssFile(Yii::getAlias('@web') . '/css/fin-transactions.css', ['depends' => ['yii\web\YiiAsset']]);
$csrfToken = Yii::$app->request->csrfToken;
?>

<?= $this->render('@app/views/layouts/_inventory-tabs', ['activeTab' => 'settings']) ?>

<style>
.st-page {
    max-width: 1100px;
    --fin-font: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
    --fin-credit: #166534; --fin-credit-bg: #dcfce7;
    --fin-debit: #991b1b; --fin-debit-bg: #fee2e2;
    --fin-gold: #92400e; --fin-gold-bg: #fef3c7;
    --fin-neutral: #475569; --fin-neutral-bg: #f1f5f9;
    --fin-border: #cbd5e1; --fin-bg: #f8fafc; --fin-surface: #ffffff;
    --fin-text: #1e293b; --fin-text2: #475569;
    --fin-r: 10px; --fin-r-sm: 6px;
    --fin-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.04);
    --fin-shadow-md: 0 4px 12px rgba(0,0,0,0.06);
    --fin-primary: #075985; --clr-primary-400: #075985; --clr-primary-600: #0c4a6e;
}
.st-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 800px) { .st-grid { grid-template-columns: 1fr; } }

.st-card { background: #fff; border-radius: 12px; border: 1px solid #cbd5e1; box-shadow: 0 1px 4px rgba(0,0,0,0.06); overflow: hidden; }
.st-card-head { padding: 14px 20px; background: #f1f5f9; border-bottom: 1px solid #cbd5e1; display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 14px; color: #0f172a; }
.st-card-body { padding: 0; max-height: 420px; overflow-y: auto; }

.st-row { display: flex; align-items: center; padding: 10px 20px; border-bottom: 1px solid #f1f5f9; gap: 12px; }
.st-row:last-child { border-bottom: none; }
.st-row:hover { background: #f8fafc; }
.st-row-name { flex: 1; font-weight: 600; font-size: 13.5px; color: #1e293b; }
.st-row-meta { font-size: 12px; color: #94a3b8; }
.st-row-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }

/* ── نموذج إضافة سريعة ── */
.st-add { padding: 14px 20px; border-top: 1px solid #e2e8f0; background: #fafbfc; display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.st-add input { flex: 1; min-width: 120px; padding: 7px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; }
.st-add input:focus { border-color: #0369a1; outline: none; box-shadow: 0 0 0 3px rgba(3,105,161,0.1); }
.st-add-btn { padding: 7px 16px; background: #0369a1; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; }
.st-add-btn:hover { background: #075985; }

.st-empty { padding: 30px; text-align: center; color: #94a3b8; font-size: 13px; }
.st-count { background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 12px; font-size: 11.5px; font-weight: 700; margin-right: auto; }
</style>

<div class="st-page">
    <div class="st-grid">

        <!-- ═══ الموردين ═══ -->
        <div class="st-card">
            <div class="st-card-head">
                <i class="fa fa-truck" style="color:#0369a1"></i> الموردين
                <span class="st-count"><?= count($suppliers) ?></span>
            </div>
            <div class="st-card-body" id="suppliers-list">
                <?php if (empty($suppliers)): ?>
                    <div class="st-empty"><i class="fa fa-truck"></i><br>لا يوجد موردين</div>
                <?php else: ?>
                    <?php foreach ($suppliers as $s): ?>
                    <div class="st-row">
                        <div class="st-row-icon" style="background:#e0f2fe;color:#0369a1"><i class="fa fa-user"></i></div>
                        <div>
                            <div class="st-row-name"><?= Html::encode($s->name) ?></div>
                            <div class="st-row-meta">
                                <?= Html::encode($s->phone_number) ?>
                                <?= $s->adress ? ' · ' . Html::encode($s->adress) : '' ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach ?>
                <?php endif ?>
            </div>
            <div class="st-add">
                <input type="text" id="sup-name" placeholder="اسم المورد *">
                <input type="text" id="sup-phone" placeholder="رقم الهاتف *" style="max-width:140px">
                <button class="st-add-btn" id="btn-add-supplier"><i class="fa fa-plus"></i> إضافة</button>
            </div>
        </div>

        <!-- ═══ المواقع ═══ -->
        <div class="st-card">
            <div class="st-card-head">
                <i class="fa fa-map-marker" style="color:#7c3aed"></i> مواقع التخزين
                <span class="st-count"><?= count($locations) ?></span>
            </div>
            <div class="st-card-body" id="locations-list">
                <?php if (empty($locations)): ?>
                    <div class="st-empty"><i class="fa fa-map-marker"></i><br>لا يوجد مواقع</div>
                <?php else: ?>
                    <?php foreach ($locations as $loc): ?>
                    <div class="st-row">
                        <div class="st-row-icon" style="background:#ede9fe;color:#7c3aed"><i class="fa fa-map-marker"></i></div>
                        <div>
                            <div class="st-row-name"><?= Html::encode($loc->locations_name) ?></div>
                        </div>
                    </div>
                    <?php endforeach ?>
                <?php endif ?>
            </div>
            <div class="st-add">
                <input type="text" id="loc-name" placeholder="اسم الموقع *">
                <button class="st-add-btn" id="btn-add-location"><i class="fa fa-plus"></i> إضافة</button>
            </div>
        </div>

    </div>
</div>

<?php
$addSupplierUrl = Url::to(['quick-add-supplier']);
$addLocationUrl = Url::to(['quick-add-location']);

$js = <<<JS
$('#btn-add-supplier').click(function(){
    var name = $('#sup-name').val().trim();
    var phone = $('#sup-phone').val().trim();
    if (!name) { alert('أدخل اسم المورد'); $('#sup-name').focus(); return; }
    if (!phone) { alert('أدخل رقم الهاتف'); $('#sup-phone').focus(); return; }
    $(this).prop('disabled', true);
    $.post('$addSupplierUrl', { name: name, phone: phone, _csrf: '$csrfToken' }, function(resp){
        if (resp.success) {
            location.reload();
        } else {
            alert(resp.message);
        }
    }, 'json').always(function(){ $('#btn-add-supplier').prop('disabled', false); });
});

$('#btn-add-location').click(function(){
    var name = $('#loc-name').val().trim();
    if (!name) { alert('أدخل اسم الموقع'); $('#loc-name').focus(); return; }
    $(this).prop('disabled', true);
    $.post('$addLocationUrl', { name: name, _csrf: '$csrfToken' }, function(resp){
        if (resp.success) {
            location.reload();
        } else {
            alert(resp.message);
        }
    }, 'json').always(function(){ $('#btn-add-location').prop('disabled', false); });
});

// Enter key support
$('#sup-name, #sup-phone').keypress(function(e){ if(e.which==13) $('#btn-add-supplier').click(); });
$('#loc-name').keypress(function(e){ if(e.which==13) $('#btn-add-location').click(); });
JS;
$this->registerJs($js);
?>
