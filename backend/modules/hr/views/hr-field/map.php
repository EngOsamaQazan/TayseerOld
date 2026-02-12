<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  خريطة الموظفين الميدانيين — Field Staff Map
 *  ──────────────────────────────────────
 *  خريطة Leaflet/OpenStreetMap مع قائمة الجلسات الميدانية النشطة
 * ═══════════════════════════════════════════════════════════════
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;

/** @var yii\web\View $this */
/** @var array $activeSessions — array of active field sessions */

$this->title = 'خريطة الموظفين الميدانيين';

/* ─── Register HR CSS ─── */
$this->registerCssFile(Yii::getAlias('@web') . '/css/hr.css', ['depends' => ['yii\web\YiiAsset']]);

/* ─── Safe defaults ─── */
$activeSessions = isset($activeSessions) ? $activeSessions : [];

/* ─── Session status map ─── */
$statusMap = [
    'active'      => ['label' => 'نشط',      'color' => '#27ae60', 'icon' => 'fa-circle'],
    'en_route'    => ['label' => 'في الطريق', 'color' => '#3498db', 'icon' => 'fa-road'],
    'arrived'     => ['label' => 'وصل',       'color' => '#f39c12', 'icon' => 'fa-map-pin'],
    'on_break'    => ['label' => 'استراحة',   'color' => '#95a5a6', 'icon' => 'fa-coffee'],
    'returning'   => ['label' => 'عائد',      'color' => '#9b59b6', 'icon' => 'fa-undo'],
];
?>

<style>
/* ═══════════════════════════════════════
   Field Map — Layout Styles
   ═══════════════════════════════════════ */
.field-map-layout {
    display: flex;
    gap: 0;
    height: calc(100vh - 140px);
    min-height: 500px;
    border-radius: var(--hr-radius-md, 10px);
    overflow: hidden;
    box-shadow: var(--hr-shadow-md);
    background: var(--hr-card-bg, #fff);
}

/* Sidebar */
.field-map-sidebar {
    width: 320px;
    flex-shrink: 0;
    background: var(--hr-card-bg, #fff);
    border-left: 1px solid var(--hr-border, #e0e0e0);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.field-map-sidebar__header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--hr-border, #e0e0e0);
    background: var(--hr-bg, #f4f6f9);
}
.field-map-sidebar__header h3 {
    margin: 0; font-size: 15px; font-weight: 700;
    color: var(--hr-text, #2c3e50);
    display: flex; align-items: center; gap: 8px;
}
.field-map-sidebar__header h3 i { color: var(--hr-primary, #800020); }
.field-map-sidebar__count {
    font-size: 12px; color: var(--hr-text-muted, #95a5a6);
    margin-top: 4px;
}
.field-map-sidebar__list {
    flex: 1;
    overflow-y: auto;
    padding: 0;
    margin: 0;
    list-style: none;
}
.field-map-sidebar__item {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 20px;
    border-bottom: 1px solid #f2f3f5;
    transition: var(--hr-transition-fast, all 0.15s ease);
    cursor: pointer;
}
.field-map-sidebar__item:hover {
    background: var(--hr-primary-50, #fdf0f3);
}
.field-map-sidebar__avatar {
    width: 38px; height: 38px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700; color: #fff;
    flex-shrink: 0;
    background: var(--hr-primary, #800020);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.field-map-sidebar__info { flex: 1; min-width: 0; }
.field-map-sidebar__name {
    font-size: 13px; font-weight: 600;
    color: var(--hr-text, #2c3e50);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.field-map-sidebar__detail {
    font-size: 11px; color: var(--hr-text-muted, #95a5a6);
    margin-top: 2px;
}
.field-map-sidebar__status {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11px; font-weight: 600; padding: 2px 10px;
    border-radius: 20px; color: #fff; flex-shrink: 0;
}

/* Map container */
.field-map-container {
    flex: 1;
    position: relative;
    min-height: 400px;
}
#fieldMap { width: 100%; height: 100%; }

/* Empty sidebar */
.field-map-empty {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; padding: 40px 20px;
    text-align: center; color: var(--hr-text-muted, #95a5a6);
    flex: 1;
}
.field-map-empty i { font-size: 36px; opacity: 0.3; margin-bottom: 12px; }
.field-map-empty p { margin: 0; font-size: 13px; }

/* Responsive */
@media (max-width: 992px) {
    .field-map-layout {
        flex-direction: column-reverse;
        height: auto;
    }
    .field-map-sidebar { width: 100%; max-height: 280px; border-left: none; border-top: 1px solid var(--hr-border); }
    .field-map-container { min-height: 400px; }
}
</style>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

<div class="hr-page">

    <!-- ╔═══════════════════════════════════════╗
         ║  العنوان وأزرار الإجراءات             ║
         ╚═══════════════════════════════════════╝ -->
    <div class="hr-header">
        <h1><i class="fa fa-map"></i> <?= Html::encode($this->title) ?></h1>
        <div style="display:flex;gap:8px;align-items:center">
            <?= Html::a(
                '<i class="fa fa-list"></i> قائمة المهام',
                Url::to(['index']),
                ['class' => 'hr-btn hr-btn--outline-primary hr-btn--sm']
            ) ?>
            <?= Html::a(
                '<i class="fa fa-refresh"></i> تحديث',
                Url::to(['map']),
                ['class' => 'hr-btn hr-btn--info hr-btn--sm', 'id' => 'btn-refresh-map']
            ) ?>
        </div>
    </div>

    <!-- ╔═══════════════════════════════════════╗
         ║  الخريطة + الشريط الجانبي             ║
         ╚═══════════════════════════════════════╝ -->
    <div class="field-map-layout">

        <!-- الشريط الجانبي — قائمة الجلسات النشطة -->
        <div class="field-map-sidebar">
            <div class="field-map-sidebar__header">
                <h3><i class="fa fa-users"></i> الجلسات النشطة</h3>
                <div class="field-map-sidebar__count"><?= count($activeSessions) ?> موظف في الميدان</div>
            </div>
            <?php if (!empty($activeSessions)): ?>
            <ul class="field-map-sidebar__list">
                <?php foreach ($activeSessions as $session):
                    $name   = Html::encode($session['employee_name'] ?? '—');
                    $status = $session['status'] ?? 'active';
                    $sInfo  = $statusMap[$status] ?? ['label' => $status, 'color' => '#95a5a6', 'icon' => 'fa-circle'];
                    $initials = mb_substr($name, 0, 1);
                    $startTime = isset($session['start_time']) ? Html::encode($session['start_time']) : '';
                ?>
                <li class="field-map-sidebar__item" data-session-id="<?= $session['id'] ?? '' ?>">
                    <span class="field-map-sidebar__avatar"><?= $initials ?></span>
                    <div class="field-map-sidebar__info">
                        <div class="field-map-sidebar__name"><?= $name ?></div>
                        <?php if ($startTime): ?>
                        <div class="field-map-sidebar__detail"><i class="fa fa-clock-o"></i> بدأ: <?= $startTime ?></div>
                        <?php endif; ?>
                    </div>
                    <span class="field-map-sidebar__status" style="background:<?= $sInfo['color'] ?>">
                        <i class="fa <?= $sInfo['icon'] ?>"></i> <?= $sInfo['label'] ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <div class="field-map-empty">
                <i class="fa fa-map-marker"></i>
                <p>لا توجد جلسات ميدانية نشطة حالياً</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- حاوية الخريطة -->
        <div class="field-map-container">
            <div id="fieldMap"></div>
        </div>

    </div>

</div><!-- /.hr-page -->

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<?php
/* ═══════════════════════════════════════════════════════════════
 *  JavaScript — Initialize map & prepare for markers
 * ═══════════════════════════════════════════════════════════════ */
$sessionsJson = Json::encode($activeSessions);
$js = <<<JS

// ─── Initialize Leaflet map centered on Jordan ───
var fieldMap = L.map('fieldMap', {
    center: [31.95, 35.93],
    zoom: 9,
    zoomControl: true,
    attributionControl: true
});

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19
}).addTo(fieldMap);

// ─── Marker group for field staff ───
var markersGroup = L.layerGroup().addTo(fieldMap);

// ─── Placeholder: populate markers from sessions data ───
var sessions = {$sessionsJson};
if (sessions && sessions.length > 0) {
    sessions.forEach(function(s) {
        if (s.latitude && s.longitude) {
            var marker = L.marker([parseFloat(s.latitude), parseFloat(s.longitude)])
                .bindPopup(
                    '<div style="direction:rtl;text-align:right;min-width:140px">' +
                    '<strong>' + (s.employee_name || '—') + '</strong><br>' +
                    '<small>' + (s.status || '') + '</small>' +
                    '</div>'
                );
            markersGroup.addLayer(marker);
        }
    });

    // Fit map to markers if any exist
    if (markersGroup.getLayers().length > 0) {
        fieldMap.fitBounds(markersGroup.getBounds().pad(0.2));
    }
}

// ─── Click sidebar item to pan to marker ───
$('.field-map-sidebar__item').on('click', function() {
    var sessionId = $(this).data('session-id');
    // Future: pan to marker location via AJAX
});

// ─── Fix map size after layout render ───
setTimeout(function() { fieldMap.invalidateSize(); }, 200);

JS;

$this->registerJs($js, \yii\web\View::POS_READY);
?>
