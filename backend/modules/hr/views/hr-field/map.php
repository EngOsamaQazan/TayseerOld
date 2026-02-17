<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  خريطة تتبع الموظفين — Live Field Staff Map
 *  ──────────────────────────────────────
 *  Google Maps (عند توفر المفتاح) أو Leaflet — تحديث تلقائي كل 30 ثانية
 * ═══════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;

/** @var yii\web\View $this */
/** @var array $staffLocations */
/** @var int $activeSessionCount */
/** @var int $tasksInProgress */

$this->title = 'خريطة تتبع المناديب';

/* ─── Register HR CSS ─── */
$this->registerCssFile(Yii::getAlias('@web') . '/css/hr.css', ['depends' => ['yii\web\YiiAsset']]);

$staffLocations     = $staffLocations ?? [];
$activeSessionCount = $activeSessionCount ?? 0;
$tasksInProgress    = $tasksInProgress ?? 0;
$refreshUrl         = Url::to(['map']);
$googleMapsKey     = \common\models\SystemSettings::get('google_maps', 'api_key', null)
    ?? Yii::$app->params['googleMapsApiKey'] ?? null;
$useGoogleMaps     = !empty($googleMapsKey);
?>

<?php if (!$useGoogleMaps): ?>
<!-- Leaflet CSS (عند عدم وجود مفتاح Google) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<?php endif; ?>

<style>
/* ═══════════════════════════════════════
   Field Map — Layout
   ═══════════════════════════════════════ */
.map-kpis { display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
.map-kpi {
    display: flex; align-items: center; gap: 10px;
    background: #fff; padding: 12px 18px; border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06); min-width: 180px;
}
.map-kpi__icon {
    width: 42px; height: 42px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; color: #fff; flex-shrink: 0;
}
.map-kpi__icon.green { background: #27ae60; }
.map-kpi__icon.blue { background: #3498db; }
.map-kpi__icon.burgundy { background: #800020; }
.map-kpi__value { font-size: 22px; font-weight: 800; color: #2c3e50; line-height: 1; }
.map-kpi__label { font-size: 12px; color: #95a5a6; margin-top: 2px; }

.field-map-layout {
    display: flex; gap: 0;
    height: calc(100vh - 240px); min-height: 500px;
    border-radius: 10px; overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    background: #fff;
}

/* Sidebar */
.fms { width: 320px; flex-shrink: 0; background: #fff;
    border-left: 1px solid #e8e8e8; display: flex; flex-direction: column; }
.fms__head { padding: 14px 18px; border-bottom: 1px solid #e8e8e8; background: #fafafa; }
.fms__head h3 { margin: 0; font-size: 14px; font-weight: 700; color: #2c3e50;
    display: flex; align-items: center; gap: 6px; }
.fms__head h3 i { color: #800020; }
.fms__sub { font-size: 11px; color: #95a5a6; margin-top: 3px; }
.fms__list { flex: 1; overflow-y: auto; padding: 0; margin: 0; list-style: none; }
.fms__item {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 12px 18px; border-bottom: 1px solid #f5f5f5;
    cursor: pointer; transition: background .15s;
}
.fms__item:hover { background: #fdf0f3; }
.fms__avatar {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; color: #fff;
    flex-shrink: 0; background: #800020;
}
.fms__info { flex: 1; min-width: 0; }
.fms__name { font-size: 13px; font-weight: 600; color: #2c3e50;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.fms__meta { font-size: 11px; color: #95a5a6; margin-top: 2px; line-height: 1.5; }
.fms__meta i { width: 14px; display: inline-block; text-align: center; }
.fms__badge {
    display: inline-flex; align-items: center; gap: 3px;
    font-size: 10px; font-weight: 600; padding: 2px 8px;
    border-radius: 20px; color: #fff; flex-shrink: 0; margin-top: 2px;
}
.fms__empty {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; padding: 40px 20px;
    text-align: center; color: #bbb; flex: 1;
}
.fms__empty i { font-size: 40px; opacity: 0.3; margin-bottom: 10px; }

/* Map */
.field-map-container { flex: 1; position: relative; }
#fieldMap { width: 100%; height: 100%; }

/* Auto-refresh badge */
.auto-refresh-badge {
    position: absolute; top: 10px; left: 10px; z-index: 999;
    background: rgba(0,0,0,0.7); color: #fff;
    padding: 5px 12px; border-radius: 20px;
    font-size: 11px; display: flex; align-items: center; gap: 6px;
}
.auto-refresh-badge .pulse {
    width: 8px; height: 8px; border-radius: 50%; background: #27ae60;
    animation: pulse-anim 2s infinite;
}
@keyframes pulse-anim {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.3); }
}

/* Responsive */
@media (max-width: 992px) {
    .field-map-layout { flex-direction: column-reverse; height: auto; }
    .fms { width: 100%; max-height: 260px; border-left: none; border-top: 1px solid #e8e8e8; }
    .field-map-container { min-height: 400px; }
}
</style>

<div class="hr-page">

    <!-- العنوان -->
    <div class="hr-header">
        <h1><i class="fa fa-map"></i> <?= Html::encode($this->title) ?></h1>
        <div style="display:flex;gap:8px;align-items:center">
            <?= Html::a('<i class="fa fa-tasks"></i> المهام', ['index'], ['class' => 'hr-btn hr-btn--outline-primary hr-btn--sm']) ?>
            <?= Html::a('<i class="fa fa-history"></i> الجلسات', ['sessions'], ['class' => 'hr-btn hr-btn--outline-primary hr-btn--sm']) ?>
            <?= Html::a('<i class="fa fa-refresh"></i> تحديث', ['map'], ['class' => 'hr-btn hr-btn--info hr-btn--sm', 'id' => 'btnRefresh']) ?>
        </div>
    </div>

    <!-- KPIs -->
    <div class="map-kpis">
        <div class="map-kpi">
            <div class="map-kpi__icon green"><i class="fa fa-users"></i></div>
            <div>
                <div class="map-kpi__value" id="kpiActive"><?= $activeSessionCount ?></div>
                <div class="map-kpi__label">مندوب في الميدان</div>
            </div>
        </div>
        <div class="map-kpi">
            <div class="map-kpi__icon blue"><i class="fa fa-tasks"></i></div>
            <div>
                <div class="map-kpi__value" id="kpiTasks"><?= $tasksInProgress ?></div>
                <div class="map-kpi__label">مهمة قيد التنفيذ</div>
            </div>
        </div>
        <div class="map-kpi">
            <div class="map-kpi__icon burgundy"><i class="fa fa-clock-o"></i></div>
            <div>
                <div class="map-kpi__value" id="kpiTime"><?= date('H:i') ?></div>
                <div class="map-kpi__label">آخر تحديث</div>
            </div>
        </div>
    </div>

    <!-- الخريطة + الشريط الجانبي -->
    <div class="field-map-layout">

        <!-- الشريط الجانبي -->
        <div class="fms">
            <div class="fms__head">
                <h3><i class="fa fa-street-view"></i> المناديب النشطون</h3>
                <div class="fms__sub" id="sidebarCount"><?= $activeSessionCount ?> موظف في الميدان الآن</div>
            </div>
            <ul class="fms__list" id="sidebarList">
                <?php if (!empty($staffLocations)): ?>
                    <?php foreach ($staffLocations as $i => $sl):
                        $s = $sl['session'];
                        $pt = $sl['lastPoint'];
                        $task = $sl['currentTask'];
                        $name = $s['name'] ?: $s['username'];
                        $initials = mb_substr($name, 0, 1);
                        $lat = $pt ? $pt['latitude'] : null;
                        $lng = $pt ? $pt['longitude'] : null;
                        $time = $pt ? date('H:i', strtotime($pt['captured_at'])) : '—';
                        $battery = $pt && $pt['battery_level'] ? round($pt['battery_level'] * 100) . '%' : '';
                        $accuracy = $pt && $pt['accuracy'] ? round($pt['accuracy']) . 'm' : '';
                    ?>
                    <li class="fms__item" data-idx="<?= $i ?>" data-lat="<?= $lat ?>" data-lng="<?= $lng ?>">
                        <span class="fms__avatar"><?= $initials ?></span>
                        <div class="fms__info">
                            <div class="fms__name"><?= Html::encode($name) ?></div>
                            <div class="fms__meta">
                                <i class="fa fa-clock-o"></i> بدأ: <?= date('H:i', strtotime($s['started_at'])) ?>
                                <?php if ($time !== '—'): ?> &nbsp;|&nbsp; <i class="fa fa-crosshairs"></i> آخر موقع: <?= $time ?><?php endif; ?>
                            </div>
                            <div class="fms__meta">
                                <?php if ($battery): ?><i class="fa fa-battery-half"></i> <?= $battery ?> &nbsp;<?php endif; ?>
                                <?php if ($accuracy): ?><i class="fa fa-dot-circle-o"></i> دقة: <?= $accuracy ?><?php endif; ?>
                            </div>
                            <?php if ($task): ?>
                            <div class="fms__meta" style="color:#800020;font-weight:600">
                                <i class="fa fa-briefcase"></i> <?= Html::encode($task['title']) ?>
                                (<?= Html::encode($task['status']) ?>)
                            </div>
                            <?php endif; ?>
                        </div>
                        <span class="fms__badge" style="background:#27ae60"><i class="fa fa-circle"></i> نشط</span>
                    </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="fms__empty" id="sidebarEmpty">
                        <i class="fa fa-map-marker"></i>
                        <p>لا يوجد مناديب في الميدان حالياً</p>
                        <p style="margin-top:6px;font-size:11px">ستظهر هنا عند بدء أي مندوب جولته الميدانية</p>
                    </div>
                <?php endif; ?>
            </ul>
        </div>

        <!-- الخريطة -->
        <div class="field-map-container">
            <div class="auto-refresh-badge">
                <span class="pulse"></span>
                <span>تحديث تلقائي كل 30 ثانية</span>
                <?php if ($useGoogleMaps): ?><span style="margin-right:8px">|</span><span>خريطة Google</span><?php endif; ?>
            </div>
            <?php if (!$useGoogleMaps): ?>
            <div class="map-google-hint" style="position:absolute;top:44px;left:10px;z-index:998;background:rgba(255,255,255,0.95);padding:8px 12px;border-radius:8px;font-size:11px;color:#666;box-shadow:0 1px 4px rgba(0,0,0,0.1);max-width:320px">
                لتفعيل خريطة Google: من <strong>إعدادات النظام</strong> → <strong>خريطة Google</strong> أدخل مفتاح API واحفظ.
            </div>
            <?php endif; ?>
            <div id="fieldMap"></div>
        </div>

    </div>

</div>

<?php
$initialData = Json::encode($staffLocations);
$ajaxUrl = Json::encode($refreshUrl);
?>

<?php if ($useGoogleMaps): ?>
<script>
var fieldMapInitialData = <?= $initialData ?>;
var fieldMapRefreshUrl = <?= $ajaxUrl ?>;

function initGoogleFieldMap() {
    var jordanCenter = { lat: 31.95, lng: 35.93 };
    var map = new google.maps.Map(document.getElementById('fieldMap'), {
        center: jordanCenter,
        zoom: 15,
        mapTypeControl: true,
        mapTypeControlOptions: { style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR, position: google.maps.ControlPosition.TOP_RIGHT },
        zoomControl: true,
        zoomControlOptions: { position: google.maps.ControlPosition.RIGHT_CENTER },
        scaleControl: true,
        streetViewControl: true,
        fullscreenControl: true,
        styles: [],
        language: 'ar'
    });

    var markers = [], circles = [], infoWindow = new google.maps.InfoWindow();

    function buildInfoContent(sl) {
        var pt = sl.lastPoint, s = sl.session, task = sl.currentTask;
        var name = (s && (s.name || s.username)) || '—';
        var battery = (pt && pt.battery_level) ? Math.round(pt.battery_level * 100) + '%' : '—';
        var acc = (pt && pt.accuracy) ? Math.round(pt.accuracy) + ' م' : '—';
        var time = (pt && pt.captured_at) ? pt.captured_at.substring(11, 16) : '';
        var html = '<div style="direction:rtl;text-align:right;min-width:200px;font-family:Tahoma,sans-serif;padding:4px">' +
            '<div style="font-size:14px;font-weight:700;color:#800020;margin-bottom:6px">' + name + '</div>' +
            '<div style="font-size:12px;color:#555;line-height:1.8">' +
            'آخر تحديث: ' + time + '<br>دقة: ' + acc + '<br>بطارية: ' + battery;
        if (task && task.title) {
            html += '<br><b>' + (task.title || '') + '</b>';
            if (task.target_address) html += '<br>' + task.target_address;
        }
        html += '</div></div>';
        return html;
    }

    function renderMarkers(data) {
        markers.forEach(function(m) { m.setMap(null); });
        circles.forEach(function(c) { c.setMap(null); });
        markers = [];
        circles = [];
        var bounds = new google.maps.LatLngBounds();
        (data || []).forEach(function(sl, idx) {
            var pt = sl.lastPoint;
            if (!pt || !pt.latitude || !pt.longitude) return;
            var lat = parseFloat(pt.latitude);
            var lng = parseFloat(pt.longitude);
            var pos = { lat: lat, lng: lng };
            bounds.extend(pos);

            var marker = new google.maps.Marker({
                position: pos,
                map: map,
                title: (sl.session && (sl.session.name || sl.session.username)) || 'موظف',
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 12,
                    fillColor: '#800020',
                    fillOpacity: 1,
                    strokeColor: '#fff',
                    strokeWeight: 3
                },
                zIndex: 100 + idx
            });
            var content = buildInfoContent(sl);
            marker.staffData = sl;
            marker.addListener('click', function() {
                infoWindow.setContent(buildInfoContent(marker.staffData));
                infoWindow.open(map, marker);
            });
            markers.push(marker);

            if (pt.accuracy && pt.accuracy < 200) {
                var circle = new google.maps.Circle({
                    map: map,
                    center: pos,
                    radius: parseFloat(pt.accuracy),
                    fillColor: '#800020',
                    fillOpacity: 0.08,
                    strokeColor: '#800020',
                    strokeOpacity: 0.3,
                    strokeWeight: 1
                });
                circles.push(circle);
            }
        });
        if (markers.length > 0) {
            map.fitBounds(bounds, { padding: 50 });
            if (map.getZoom() > 16) map.setZoom(16);
        }
    }

    renderMarkers(fieldMapInitialData);

    $(document).on('click', '.fms__item', function() {
        var lat = $(this).data('lat');
        var lng = $(this).data('lng');
        if (lat && lng) {
            var pos = { lat: parseFloat(lat), lng: parseFloat(lng) };
            map.setCenter(pos);
            map.setZoom(16);
            markers.forEach(function(m) {
                var p = m.getPosition();
                if (Math.abs(p.lat() - lat) < 0.0001 && Math.abs(p.lng() - lng) < 0.0001) {
                    infoWindow.setContent(buildInfoContent(m.staffData || {}));
                    infoWindow.open(map, m);
                }
            });
        }
    });

    setInterval(function() {
        $.ajax({
            url: fieldMapRefreshUrl,
            type: 'GET',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(resp) {
                if (resp.staffLocations) {
                    renderMarkers(resp.staffLocations);
                    fieldMapInitialData = resp.staffLocations;
                    $('#kpiActive').text(resp.activeSessionCount || 0);
                    $('#kpiTasks').text(resp.tasksInProgress || 0);
                    var now = new Date();
                    $('#kpiTime').text(String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0'));
                    $('#sidebarCount').text((resp.activeSessionCount || 0) + ' موظف في الميدان الآن');
                }
            }
        });
    }, 30000);
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= Html::encode($googleMapsKey) ?>&callback=initGoogleFieldMap&language=ar" async defer></script>
<?php else: ?>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<?php
$js = <<<JS
(function(){
    var map = L.map('fieldMap', { center: [31.95, 35.93], zoom: 15 });
    var osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap', maxZoom: 19 }).addTo(map);
    var markersGroup = L.layerGroup().addTo(map);
    var initialData = {$initialData};
    function createIcon(c) {
        return L.divIcon({
            className: 'custom-marker',
            html: '<div style="width:32px;height:32px;border-radius:50%;background:'+c+';border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center"><i class="fa fa-user" style="color:#fff;font-size:14px"></i></div>',
            iconSize: [32, 32], iconAnchor: [16, 16], popupAnchor: [0, -20]
        });
    }
    function renderMarkers(data) {
        markersGroup.clearLayers();
        var bounds = [];
        data.forEach(function(sl) {
            var pt = sl.lastPoint;
            if (!pt || !pt.latitude || !pt.longitude) return;
            var lat = parseFloat(pt.latitude), lng = parseFloat(pt.longitude);
            var s = sl.session, name = (s && (s.name || s.username)) || '—', task = sl.currentTask;
            var battery = (pt.battery_level) ? Math.round(pt.battery_level*100)+'%' : '—';
            var acc = (pt.accuracy) ? Math.round(pt.accuracy)+' م' : '—';
            var time = (pt.captured_at) ? pt.captured_at.substring(11,16) : '';
            var popup = '<div style="direction:rtl;text-align:right;min-width:180px">' +
                '<div style="font-weight:700;color:#800020">'+name+'</div>' +
                'آخر تحديث: '+time+'<br>دقة: '+acc+'<br>بطارية: '+battery +
                (task && task.title ? '<br><b>'+task.title+'</b>' : '') + '</div>';
            var marker = L.marker([lat,lng], { icon: createIcon('#800020') }).bindPopup(popup);
            markersGroup.addLayer(marker);
            bounds.push([lat,lng]);
            if (pt.accuracy && pt.accuracy < 200) {
                L.circle([lat,lng], { radius: parseFloat(pt.accuracy), color: '#800020', fillColor: '#800020', fillOpacity: 0.08, weight: 1 }).addTo(markersGroup);
            }
        });
        if (bounds.length > 0) map.fitBounds(bounds, { padding: [50,50], maxZoom: 15 });
    }
    renderMarkers(initialData);
    setTimeout(function(){ map.invalidateSize(); }, 300);
    $(document).on('click', '.fms__item', function() {
        var lat = $(this).data('lat'), lng = $(this).data('lng');
        if (lat && lng) {
            map.setView([lat, lng], 16);
            markersGroup.eachLayer(function(layer) {
                if (layer.getLatLng) {
                    var ll = layer.getLatLng();
                    if (Math.abs(ll.lat - lat) < 0.0001 && Math.abs(ll.lng - lng) < 0.0001) layer.openPopup();
                }
            });
        }
    });
    setInterval(function() {
        $.ajax({ url: {$ajaxUrl}, type: 'GET', dataType: 'json', headers: { 'X-Requested-With': 'XMLHttpRequest' }, success: function(resp) {
            if (resp.staffLocations) {
                renderMarkers(resp.staffLocations);
                $('#kpiActive').text(resp.activeSessionCount||0);
                $('#kpiTasks').text(resp.tasksInProgress||0);
                $('#kpiTime').text(new Date().toTimeString().slice(0,5));
                $('#sidebarCount').text((resp.activeSessionCount||0)+' موظف في الميدان الآن');
            }
        }});
    }, 30000);
})();
JS;
$this->registerJs($js, \yii\web\View::POS_READY);
?>
<?php endif; ?>