<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\select2\Select2;
use backend\modules\jobs\models\JobsType;
use backend\modules\jobs\models\Jobs;
use backend\modules\jobs\models\JobsPhone;
use backend\modules\jobs\models\JobsWorkingHours;

/* @var $this yii\web\View */
/* @var $model Jobs */
/* @var $form ActiveForm */

$this->registerCssFile(Yii::getAlias('@web') . '/css/fin-transactions.css', ['depends' => ['yii\web\YiiAsset']]);

$existingHours = [];
if (!$model->isNewRecord) {
    foreach (JobsWorkingHours::find()->where(['job_id' => $model->id])->all() as $h) {
        $existingHours[$h->day_of_week] = $h;
    }
}
$existingPhones = [];
if (!$model->isNewRecord) {
    $existingPhones = $model->getPhones()->all();
}

$dayNames = Jobs::getDayNames();
$searchSimilarUrl = Url::to(['search-similar']);
$modelId = $model->isNewRecord ? 0 : $model->id;
?>

<style>
.job-form-page { --fin-border:#e2e8f0; --fin-r:10px; --fin-shadow:0 1px 3px rgba(0,0,0,.05); max-width:960px; margin:0 auto; font-family:'Cairo','Segoe UI',Tahoma,sans-serif; }
.job-section { background:#fff; border:1px solid var(--fin-border); border-radius:var(--fin-r); box-shadow:var(--fin-shadow); margin-bottom:20px; overflow:hidden; }
.job-section-hdr { padding:14px 20px; font-size:14px; font-weight:800; color:#334155; background:#f8fafc; border-bottom:1px solid var(--fin-border); display:flex; align-items:center; gap:8px; cursor:pointer; user-select:none; }
.job-section-hdr .toggle-icon { margin-right:auto; transition:transform .2s; }
.job-section-hdr.collapsed .toggle-icon { transform:rotate(-90deg); }
.job-section-body { padding:20px; }
.job-section-body.collapsed { display:none; }

/* Duplicate detection */
.job-similar-wrap { position:relative; }
.job-similar-list { position:absolute; z-index:100; top:100%; right:0; left:0; background:#fff; border:1px solid #fbbf24; border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,.12); max-height:220px; overflow-y:auto; display:none; }
.job-similar-list.show { display:block; }
.job-similar-hdr { padding:8px 14px; background:#fef3c7; font-weight:700; font-size:12px; color:#92400e; border-bottom:1px solid #fde68a; }
.job-similar-row { padding:8px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; display:flex; align-items:center; gap:8px; }
.job-similar-row:last-child { border-bottom:none; }
.job-similar-row:hover { background:#fefce8; }
.job-similar-name { font-weight:700; color:#1e293b; }
.job-similar-meta { font-size:11px; color:#94a3b8; }

/* Map */
#job-map-container { height:350px; border-radius:8px; border:1px solid var(--fin-border); overflow:hidden; }
.map-search-wrap { position:relative; margin-bottom:12px; }
.map-search-wrap input { height:44px; font-size:14px; padding-right:40px; border-radius:8px; border:1.5px solid var(--fin-border); }
.map-search-wrap .search-icon { position:absolute; top:12px; right:14px; color:#94a3b8; }
.map-search-results { position:absolute; z-index:200; top:100%; right:0; left:0; background:#fff; border:1px solid var(--fin-border); border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,.1); max-height:200px; overflow-y:auto; display:none; }
.map-search-results.show { display:block; }
.map-search-results .result-item { padding:10px 14px; border-bottom:1px solid #f1f5f9; cursor:pointer; font-size:13px; }
.map-search-results .result-item:hover { background:#f0f9ff; }

/* Smart location input */
.smart-loc-input { position:relative; }
.smart-loc-input textarea { font-family:monospace; font-size:13px; direction:ltr; resize:none; border-radius:8px; border:1.5px solid var(--fin-border); }
.smart-loc-hint { font-size:11px; color:#94a3b8; margin-top:4px; }
.smart-loc-parsed { margin-top:6px; padding:8px 12px; background:#dcfce7; border-radius:6px; font-size:12px; color:#15803d; font-weight:600; display:none; }
.smart-loc-parsed.show { display:block; }

/* Phone rows */
.phone-row { display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; padding:12px 0; border-bottom:1px solid #f1f5f9; }
.phone-row:last-child { border-bottom:none; }
.phone-row .form-group { margin-bottom:0; }
.phone-field { flex:1; min-width:140px; }
.phone-field label { font-size:11px; font-weight:700; color:#64748b; margin-bottom:3px; }
.phone-field input, .phone-field select { height:38px; font-size:13px; border-radius:6px; border:1.5px solid var(--fin-border); }

/* Working hours */
.wh-table { width:100%; border-collapse:collapse; }
.wh-table th { padding:8px 12px; font-size:12px; font-weight:700; color:#64748b; background:#f8fafc; text-align:right; }
.wh-table td { padding:8px 12px; border-bottom:1px solid #f1f5f9; }
.wh-table .closed-row td { background:#fef2f2; opacity:.6; }
.wh-table input[type="time"] { height:36px; border-radius:6px; border:1.5px solid var(--fin-border); font-size:13px; direction:ltr; }

/* Save bar */
.job-save-bar { display:flex; justify-content:center; gap:12px; padding:20px; }
.job-save-bar .btn { font-weight:700; border-radius:8px; padding:10px 32px; font-size:15px; }
</style>

<div class="job-form-page">
    <?php $form = ActiveForm::begin(['id' => 'jobs-form']); ?>

    <!-- ═══ 1. البيانات الأساسية ═══ -->
    <div class="job-section">
        <div class="job-section-hdr" onclick="$(this).toggleClass('collapsed').next().toggleClass('collapsed')">
            <i class="fa fa-building"></i> البيانات الأساسية
            <i class="fa fa-chevron-down toggle-icon"></i>
        </div>
        <div class="job-section-body">
            <div class="row">
                <div class="col-md-5">
                    <div class="job-similar-wrap">
                        <?= $form->field($model, 'name')->textInput([
                            'maxlength' => true,
                            'placeholder' => 'أدخل اسم جهة العمل',
                            'id' => 'job-name-input',
                            'autocomplete' => 'off',
                        ]) ?>
                        <div id="job-similar-list" class="job-similar-list"></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'job_type')->widget(Select2::class, [
                        'data' => ArrayHelper::map(JobsType::find()->all(), 'id', 'name'),
                        'options' => ['placeholder' => 'اختر نوع جهة العمل'],
                        'pluginOptions' => ['allowClear' => true],
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'status')->dropDownList([1 => 'فعال', 0 => 'غير فعال'], ['class' => 'form-control']) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'email')->textInput(['maxlength' => true, 'placeholder' => 'example@domain.com', 'dir' => 'ltr']) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'website')->textInput(['maxlength' => true, 'placeholder' => 'https://www.example.com', 'dir' => 'ltr']) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'notes')->textarea(['rows' => 1, 'placeholder' => 'ملاحظات إضافية']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ 2. أرقام الهواتف ═══ -->
    <div class="job-section">
        <div class="job-section-hdr" onclick="$(this).toggleClass('collapsed').next().toggleClass('collapsed')">
            <i class="fa fa-phone"></i> أرقام الهواتف وجهات الاتصال
            <span id="phone-count-badge" style="background:#e0e7ff;color:#4338ca;padding:2px 10px;border-radius:12px;font-size:12px;margin-right:6px">0</span>
            <i class="fa fa-chevron-down toggle-icon"></i>
        </div>
        <div class="job-section-body">
            <div id="phones-container">
                <?php if (!empty($existingPhones)): ?>
                    <p class="text-muted" style="font-size:12px"><i class="fa fa-info-circle"></i> الأرقام المحفوظة سابقاً تُدار من صفحة العرض. أضف أرقاماً جديدة هنا.</p>
                    <?php foreach ($existingPhones as $p): ?>
                    <div style="padding:6px 12px;background:#f1f5f9;border-radius:6px;margin-bottom:6px;font-size:13px;display:flex;gap:12px;align-items:center">
                        <i class="fa fa-<?= $p->phone_type === 'mobile' ? 'mobile' : 'phone' ?>" style="color:#64748b"></i>
                        <strong style="direction:ltr"><?= Html::encode($p->phone_number) ?></strong>
                        <?php if ($p->employee_name): ?><span style="color:#64748b">— <?= Html::encode($p->employee_name) ?></span><?php endif ?>
                        <?php if ($p->employee_position): ?><span style="color:#94a3b8">(<?= Html::encode($p->employee_position) ?>)</span><?php endif ?>
                    </div>
                    <?php endforeach ?>
                    <hr style="margin:12px 0">
                <?php endif ?>
                <!-- Dynamic rows inserted here by JS -->
            </div>
            <button type="button" class="btn btn-sm btn-success" id="btn-add-phone" style="border-radius:8px;font-weight:700">
                <i class="fa fa-plus"></i> إضافة رقم هاتف
            </button>
        </div>
    </div>

    <!-- ═══ 3. العنوان والموقع الجغرافي ═══ -->
    <div class="job-section">
        <div class="job-section-hdr" onclick="$(this).toggleClass('collapsed').next().toggleClass('collapsed')">
            <i class="fa fa-map-marker"></i> العنوان والموقع الجغرافي
            <i class="fa fa-chevron-down toggle-icon"></i>
        </div>
        <div class="job-section-body">
            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($model, 'address_city')->textInput(['placeholder' => 'المدينة']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'address_area')->textInput(['placeholder' => 'المنطقة أو الحي']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'address_street')->textInput(['placeholder' => 'الشارع والعنوان التفصيلي']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'address_building')->textInput(['placeholder' => 'المبنى / الطابق / الرقم']) ?>
                </div>
            </div>

            <!-- Smart location input -->
            <div class="row" style="margin-bottom:16px">
                <div class="col-md-12">
                    <div class="smart-loc-input">
                        <label style="font-weight:700;font-size:13px;color:#334155"><i class="fa fa-paste"></i> لصق موقع (من جوجل ماب أو أي مصدر)</label>
                        <textarea id="smart-location-paste" class="form-control" rows="2" placeholder="الصق هنا: إحداثيات (31.95, 35.91) أو رابط جوجل ماب أو عنوان نصي أو Plus Code..."></textarea>
                        <div class="smart-loc-hint">يقبل: إحداثيات عددية، روابط Google Maps، عناوين نصية، Plus Codes (مثل 85RM+JV عمان)</div>
                        <div id="smart-loc-parsed" class="smart-loc-parsed"></div>
                    </div>
                </div>
            </div>

            <!-- Map search -->
            <div class="row">
                <div class="col-md-12">
                    <div class="map-search-wrap">
                        <i class="fa fa-search search-icon"></i>
                        <input type="text" id="map-search-input" class="form-control" placeholder="ابحث عن موقع على الخريطة (مثل: شركة الدهانات الوطنية عمان)...">
                        <div id="map-search-results" class="map-search-results"></div>
                    </div>
                    <div id="job-map-container"></div>
                </div>
            </div>

            <!-- Lat/Lng hidden fields -->
            <div class="row" style="margin-top:12px">
                <div class="col-md-3">
                    <?= $form->field($model, 'latitude')->textInput(['placeholder' => 'خط العرض', 'dir' => 'ltr', 'id' => 'job-latitude', 'style' => 'background:#f8fafc;font-family:monospace;font-size:13px']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'longitude')->textInput(['placeholder' => 'خط الطول', 'dir' => 'ltr', 'id' => 'job-longitude', 'style' => 'background:#f8fafc;font-family:monospace;font-size:13px']) ?>
                </div>
                <div class="col-md-6" style="padding-top:26px">
                    <button type="button" class="btn btn-info btn-sm" id="btn-get-location" style="border-radius:8px;font-weight:600">
                        <i class="fa fa-crosshairs"></i> موقعي الحالي
                    </button>
                    <button type="button" class="btn btn-warning btn-sm" id="btn-clear-location" style="border-radius:8px;font-weight:600">
                        <i class="fa fa-eraser"></i> مسح الموقع
                    </button>
                    <?php if (!$model->isNewRecord && $model->latitude && $model->longitude): ?>
                        <a href="<?= $model->getMapUrl() ?>" target="_blank" class="btn btn-success btn-sm" style="border-radius:8px;font-weight:600">
                            <i class="fa fa-external-link"></i> فتح في جوجل ماب
                        </a>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ 4. أوقات العمل ═══ -->
    <div class="job-section">
        <div class="job-section-hdr collapsed" onclick="$(this).toggleClass('collapsed').next().toggleClass('collapsed')">
            <i class="fa fa-clock-o"></i> أوقات العمل
            <i class="fa fa-chevron-down toggle-icon"></i>
        </div>
        <div class="job-section-body collapsed">
            <table class="wh-table">
                <thead><tr><th style="width:120px">اليوم</th><th style="width:140px">البداية</th><th style="width:140px">النهاية</th><th style="width:70px;text-align:center">مغلق</th><th>ملاحظات</th></tr></thead>
                <tbody>
                    <?php foreach ($dayNames as $dayNum => $dayName):
                        $existing = $existingHours[$dayNum] ?? null;
                        $isClosed = $existing ? $existing->is_closed : ($dayNum == 5 ? 1 : 0);
                        $openTime = $existing ? $existing->opening_time : ($dayNum == 5 ? '' : '08:00');
                        $closeTime = $existing ? $existing->closing_time : ($dayNum == 5 ? '' : '16:00');
                        $notes = $existing ? $existing->notes : '';
                    ?>
                    <tr class="working-hours-row <?= $isClosed ? 'closed-row' : '' ?>">
                        <td><strong><?= $dayName ?></strong><input type="hidden" name="WorkingHours[<?= $dayNum ?>][day_of_week]" value="<?= $dayNum ?>"></td>
                        <td><input type="time" class="form-control input-sm wh-time" name="WorkingHours[<?= $dayNum ?>][opening_time]" value="<?= $openTime ?>" <?= $isClosed ? 'disabled' : '' ?>></td>
                        <td><input type="time" class="form-control input-sm wh-time" name="WorkingHours[<?= $dayNum ?>][closing_time]" value="<?= $closeTime ?>" <?= $isClosed ? 'disabled' : '' ?>></td>
                        <td class="text-center"><input type="checkbox" class="wh-closed-toggle" name="WorkingHours[<?= $dayNum ?>][is_closed]" value="1" <?= $isClosed ? 'checked' : '' ?>></td>
                        <td><input type="text" class="form-control input-sm" name="WorkingHours[<?= $dayNum ?>][notes]" value="<?= Html::encode($notes) ?>" placeholder="ملاحظات" style="border-radius:6px;border:1.5px solid #e2e8f0"></td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ═══ أزرار الحفظ ═══ -->
    <div class="job-save-bar">
        <?= Html::submitButton(
            $model->isNewRecord ? '<i class="fa fa-plus"></i> إنشاء جهة العمل' : '<i class="fa fa-save"></i> حفظ التغييرات',
            ['class' => $model->isNewRecord ? 'btn btn-success btn-lg' : 'btn btn-primary btn-lg']
        ) ?>
        <?= Html::a('<i class="fa fa-times"></i> إلغاء', ['index'], ['class' => 'btn btn-default btn-lg']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<!-- Leaflet CSS/JS from CDN -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<?php
$js = <<<JS

/* ═══════════════════════════════════════════════════════════
 *  1. كشف الأسماء المتشابهة (Duplicate Detection)
 * ═══════════════════════════════════════════════════════════ */
var similarTimer = null;
$('#job-name-input').on('input', function(){
    clearTimeout(similarTimer);
    var q = $(this).val().trim();
    if (q.length < 2) { $('#job-similar-list').removeClass('show').empty(); return; }
    similarTimer = setTimeout(function(){
        $.getJSON('$searchSimilarUrl', { q: q, exclude: $modelId }, function(data){
            var results = data.results || [];
            if (results.length === 0) { $('#job-similar-list').removeClass('show').empty(); return; }
            var html = '<div class="job-similar-hdr"><i class="fa fa-exclamation-triangle"></i> جهات عمل مشابهة موجودة:</div>';
            results.forEach(function(r){
                html += '<div class="job-similar-row">';
                html += '<span class="job-similar-name">' + r.name + '</span>';
                if (r.city || r.type) html += '<span class="job-similar-meta">' + [r.type, r.city].filter(Boolean).join(' — ') + '</span>';
                html += '</div>';
            });
            $('#job-similar-list').html(html).addClass('show');
        });
    }, 400);
});
$('#job-name-input').on('blur', function(){ setTimeout(function(){ $('#job-similar-list').removeClass('show'); }, 300); });
$('#job-name-input').on('focus', function(){ if ($('#job-similar-list').children().length > 0) $('#job-similar-list').addClass('show'); });

/* ═══════════════════════════════════════════════════════════
 *  2. أرقام الهواتف الديناميكية
 * ═══════════════════════════════════════════════════════════ */
var phoneIdx = 0;
function updatePhoneBadge() {
    var count = $('#phones-container .phone-row').length;
    var existing = <?= count($existingPhones) ?>;
    $('#phone-count-badge').text(count + existing);
}
$('#btn-add-phone').on('click', function(){
    var html = '<div class="phone-row" data-phone-idx="'+phoneIdx+'">';
    html += '<div class="phone-field" style="flex:0 0 130px"><label>رقم الهاتف</label><input type="text" name="Phones['+phoneIdx+'][phone_number]" class="form-control" placeholder="07XXXXXXXX" dir="ltr" required></div>';
    html += '<div class="phone-field" style="flex:0 0 120px"><label>النوع</label><select name="Phones['+phoneIdx+'][phone_type]" class="form-control"><option value="office">هاتف مكتب</option><option value="mobile">موبايل</option><option value="fax">فاكس</option><option value="whatsapp">واتساب</option></select></div>';
    html += '<div class="phone-field"><label>اسم المسؤول</label><input type="text" name="Phones['+phoneIdx+'][employee_name]" class="form-control" placeholder="اسم الشخص المسؤول"></div>';
    html += '<div class="phone-field"><label>المسمى الوظيفي</label><input type="text" name="Phones['+phoneIdx+'][employee_position]" class="form-control" placeholder="مدير، محاسب..."></div>';
    html += '<div style="padding-bottom:2px"><button type="button" class="btn btn-xs btn-danger btn-remove-phone" style="border-radius:6px;height:38px;width:38px"><i class="fa fa-times"></i></button></div>';
    html += '</div>';
    $('#phones-container').append(html);
    phoneIdx++;
    updatePhoneBadge();
});
$(document).on('click', '.btn-remove-phone', function(){ $(this).closest('.phone-row').remove(); updatePhoneBadge(); });

/* ═══════════════════════════════════════════════════════════
 *  3. الخريطة التفاعلية (Leaflet + OpenStreetMap)
 * ═══════════════════════════════════════════════════════════ */
var defaultLat = 31.95;
var defaultLng = 35.91;
var initLat = parseFloat($('#job-latitude').val()) || defaultLat;
var initLng = parseFloat($('#job-longitude').val()) || defaultLng;
var initZoom = ($('#job-latitude').val() && $('#job-longitude').val()) ? 15 : 8;

var map = L.map('job-map-container').setView([initLat, initLng], initZoom);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap',
    maxZoom: 19
}).addTo(map);

var marker = null;
function setMapMarker(lat, lng, flyTo) {
    if (marker) map.removeLayer(marker);
    marker = L.marker([lat, lng], {draggable: true}).addTo(map);
    marker.on('dragend', function(e){
        var pos = e.target.getLatLng();
        $('#job-latitude').val(pos.lat.toFixed(8));
        $('#job-longitude').val(pos.lng.toFixed(8));
    });
    $('#job-latitude').val(lat.toFixed ? lat.toFixed(8) : lat);
    $('#job-longitude').val(lng.toFixed ? lng.toFixed(8) : lng);
    if (flyTo !== false) map.flyTo([lat, lng], 16);
}

if ($('#job-latitude').val() && $('#job-longitude').val()) {
    setMapMarker(initLat, initLng, false);
}

map.on('click', function(e){
    setMapMarker(e.latlng.lat, e.latlng.lng, false);
});

/* بحث على الخريطة (Nominatim) */
var searchTimer = null;
$('#map-search-input').on('input', function(){
    clearTimeout(searchTimer);
    var q = $(this).val().trim();
    if (q.length < 3) { $('#map-search-results').removeClass('show').empty(); return; }
    searchTimer = setTimeout(function(){
        $.getJSON('https://nominatim.openstreetmap.org/search', {
            q: q, format: 'json', limit: 6, addressdetails: 1, 'accept-language': 'ar'
        }, function(data){
            if (!data || data.length === 0) { $('#map-search-results').html('<div class="result-item text-muted">لا توجد نتائج</div>').addClass('show'); return; }
            var html = '';
            data.forEach(function(r){
                html += '<div class="result-item" data-lat="'+r.lat+'" data-lng="'+r.lon+'">';
                html += '<i class="fa fa-map-marker" style="color:#dc2626;margin-left:6px"></i> '+r.display_name;
                html += '</div>';
            });
            $('#map-search-results').html(html).addClass('show');
        });
    }, 500);
});
$(document).on('click', '#map-search-results .result-item', function(){
    var lat = parseFloat($(this).data('lat'));
    var lng = parseFloat($(this).data('lng'));
    setMapMarker(lat, lng, true);
    $('#map-search-input').val($(this).text().trim());
    $('#map-search-results').removeClass('show');
});
$('#map-search-input').on('blur', function(){ setTimeout(function(){ $('#map-search-results').removeClass('show'); }, 300); });

/* ═══════════════════════════════════════════════════════════
 *  4. لصق الموقع الذكي (Smart Location Paste)
 * ═══════════════════════════════════════════════════════════ */
$('#smart-location-paste').on('input', function(){
    var raw = $(this).val().trim();
    if (!raw) { $('#smart-loc-parsed').removeClass('show'); return; }
    var coords = parseLocationInput(raw);
    if (coords) {
        setMapMarker(coords.lat, coords.lng, true);
        $('#smart-loc-parsed').html('<i class="fa fa-check-circle"></i> تم التعرف على الموقع: ' + coords.lat.toFixed(6) + ', ' + coords.lng.toFixed(6)).addClass('show');
    } else {
        $('#smart-loc-parsed').html('<i class="fa fa-search"></i> جاري البحث عن العنوان...').addClass('show').css({background:'#fef3c7',color:'#92400e'});
        $.getJSON('https://nominatim.openstreetmap.org/search', {q: raw, format: 'json', limit: 1, 'accept-language': 'ar'}, function(data){
            if (data && data.length > 0) {
                var lat = parseFloat(data[0].lat), lng = parseFloat(data[0].lon);
                setMapMarker(lat, lng, true);
                $('#smart-loc-parsed').html('<i class="fa fa-check-circle"></i> ' + data[0].display_name + ' (' + lat.toFixed(6) + ', ' + lng.toFixed(6) + ')').css({background:'#dcfce7',color:'#15803d'}).addClass('show');
            } else {
                $('#smart-loc-parsed').html('<i class="fa fa-exclamation-circle"></i> لم يتم التعرف على الموقع. جرب إحداثيات عددية أو رابط جوجل ماب.').css({background:'#fee2e2',color:'#b91c1c'}).addClass('show');
            }
        });
    }
});

function parseLocationInput(raw) {
    /* 1. Decimal coordinates: 31.95, 35.91 or 31.95 35.91 */
    var m = raw.match(/(-?\\d+\\.\\d+)[,\\s]+(-?\\d+\\.\\d+)/);
    if (m) {
        var a = parseFloat(m[1]), b = parseFloat(m[2]);
        if (Math.abs(a) <= 90 && Math.abs(b) <= 180) return {lat: a, lng: b};
        if (Math.abs(b) <= 90 && Math.abs(a) <= 180) return {lat: b, lng: a};
    }
    /* 2. Google Maps URL: /@31.95,35.91 or ?q=31.95,35.91 or !3d31.95!4d35.91 */
    m = raw.match(/@(-?\\d+\\.\\d+),(-?\\d+\\.\\d+)/);
    if (m) return {lat: parseFloat(m[1]), lng: parseFloat(m[2])};
    m = raw.match(/[?&]q=(-?\\d+\\.\\d+),(-?\\d+\\.\\d+)/);
    if (m) return {lat: parseFloat(m[1]), lng: parseFloat(m[2])};
    m = raw.match(/!3d(-?\\d+\\.\\d+).*!4d(-?\\d+\\.\\d+)/);
    if (m) return {lat: parseFloat(m[1]), lng: parseFloat(m[2])};
    /* 3. DMS: 31°57'14.0"N 35°54'38.2"E */
    var dmsRe = /(\\d+)[°](\\d+)[′'](\\d+\\.?\\d*)[″"]?\\s*([NSns])\\s*,?\\s*(\\d+)[°](\\d+)[′'](\\d+\\.?\\d*)[″"]?\\s*([EWew])/;
    m = raw.match(dmsRe);
    if (m) {
        var lat = parseInt(m[1]) + parseInt(m[2])/60 + parseFloat(m[3])/3600;
        if (m[4].toLowerCase() === 's') lat = -lat;
        var lng = parseInt(m[5]) + parseInt(m[6])/60 + parseFloat(m[7])/3600;
        if (m[8].toLowerCase() === 'w') lng = -lng;
        return {lat: lat, lng: lng};
    }
    return null;
}

/* ═══════════════════════════════════════════════════════════
 *  5. أدوات مساعدة
 * ═══════════════════════════════════════════════════════════ */
$('#btn-get-location').on('click', function(){
    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري...');
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos){
            setMapMarker(pos.coords.latitude, pos.coords.longitude, true);
            btn.prop('disabled', false).html('<i class="fa fa-crosshairs"></i> موقعي الحالي');
        }, function(err){
            alert('لم نتمكن من تحديد موقعك: ' + err.message);
            btn.prop('disabled', false).html('<i class="fa fa-crosshairs"></i> موقعي الحالي');
        }, {enableHighAccuracy: true, timeout: 10000});
    } else {
        alert('المتصفح لا يدعم تحديد الموقع');
        btn.prop('disabled', false).html('<i class="fa fa-crosshairs"></i> موقعي الحالي');
    }
});

$('#btn-clear-location').on('click', function(){
    if (marker) { map.removeLayer(marker); marker = null; }
    $('#job-latitude, #job-longitude').val('');
    map.setView([defaultLat, defaultLng], 8);
    $('#smart-location-paste').val('');
    $('#smart-loc-parsed').removeClass('show');
});

/* Working hours toggle */
$('.wh-closed-toggle').on('change', function(){
    var row = $(this).closest('tr');
    var times = row.find('.wh-time');
    if ($(this).is(':checked')) { times.prop('disabled', true).val(''); row.addClass('closed-row'); }
    else { times.prop('disabled', false); row.removeClass('closed-row'); }
});

/* Fix Leaflet map rendering (sometimes tiles don't load on hidden tabs) */
setTimeout(function(){ map.invalidateSize(); }, 500);

updatePhoneBadge();
JS;
$this->registerJs($js);
?>
