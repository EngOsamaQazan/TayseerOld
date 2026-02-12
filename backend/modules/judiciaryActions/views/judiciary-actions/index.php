<?php
/**
 * إدارة الإجراءات القضائية — تصميم OCP
 * يعرض الإجراءات مجمعة حسب الطبيعة مع العلاقات
 */
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use backend\modules\judiciaryActions\models\JudiciaryActions;

$this->title = 'إدارة الإجراءات القضائية';
$this->params['breadcrumbs'][] = $this->title;

CrudAsset::register($this);

// Stats
$totalActive = (int)($searchCounter ?? 0);
$natureStats = (new \yii\db\Query())
    ->select(['action_nature', 'COUNT(*) as cnt'])
    ->from('os_judiciary_actions')
    ->where(['or', ['is_deleted' => 0], ['is_deleted' => null]])
    ->groupBy('action_nature')
    ->all();
$statMap = [];
foreach ($natureStats as $ns) {
    $statMap[$ns['action_nature'] ?: 'other'] = (int)$ns['cnt'];
}

$natureStyles = [
    'request'    => ['icon' => 'fa-file-text-o', 'color' => '#3B82F6', 'bg' => '#EFF6FF', 'label' => 'طلبات إجرائية'],
    'document'   => ['icon' => 'fa-file-o',      'color' => '#8B5CF6', 'bg' => '#F5F3FF', 'label' => 'كتب ومذكرات'],
    'doc_status' => ['icon' => 'fa-exchange',     'color' => '#EA580C', 'bg' => '#FFF7ED', 'label' => 'حالات كتب'],
    'process'    => ['icon' => 'fa-cog',          'color' => '#64748B', 'bg' => '#F1F5F9', 'label' => 'إجراءات إدارية'],
];
?>

<style>
.ja-page { direction:rtl;font-family:'Tajawal',sans-serif; }
.ja-header { display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px; }
.ja-header h1 { font-size:22px;font-weight:800;color:#1E293B;margin:0;display:flex;align-items:center;gap:10px; }
.ja-header h1 i { color:#3B82F6; }

/* Stats row */
.ja-stats { display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap; }
.ja-stat-card {
    flex:1;min-width:140px;padding:14px 16px;border-radius:12px;border:1px solid #E2E8F0;
    background:#fff;display:flex;align-items:center;gap:12px;transition:all .2s;cursor:pointer;
}
.ja-stat-card:hover { box-shadow:0 4px 12px rgba(0,0,0,.06);transform:translateY(-1px); }
.ja-stat-card.active { border-width:2px; }
.ja-stat-icon { width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px; }
.ja-stat-num { font-size:22px;font-weight:800;color:#1E293B; }
.ja-stat-label { font-size:11px;color:#94A3B8; }

/* Grid overrides */
.ja-grid .panel { border-radius:12px;overflow:hidden;border:1px solid #E2E8F0;box-shadow:0 1px 3px rgba(0,0,0,.04); }
.ja-grid .panel-heading { background:#F8FAFC !important;border-bottom:1px solid #E2E8F0;padding:10px 16px; }
.ja-grid .kv-grid-table th { background:#F8FAFC;font-weight:700;font-size:12px;color:#475569; }
.ja-grid .kv-grid-table td { font-size:13px;vertical-align:middle; }

/* Nature badge in grid */
.ja-nature-badge {
    display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:8px;
    font-size:11px;font-weight:600;white-space:nowrap;
}
/* Relationship pills */
.ja-rel-pill {
    display:inline-block;padding:1px 6px;border-radius:6px;font-size:10px;
    background:#F1F5F9;color:#475569;margin:1px;
}
/* Deleted row */
.ja-deleted { opacity:.4;text-decoration:line-through; }
</style>

<div class="ja-page">
    <!-- Header -->
    <div class="ja-header">
        <h1><i class="fa fa-gavel"></i> <?= $this->title ?></h1>
        <div style="display:flex;gap:8px">
            <?= Html::a('<i class="fa fa-plus"></i> إضافة إجراء جديد', ['create'], [
                'class' => 'btn btn-primary',
                'role' => 'modal-remote',
                'style' => 'border-radius:8px;font-size:13px;padding:8px 20px;font-weight:600'
            ]) ?>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="ja-stats">
        <?php foreach ($natureStyles as $nk => $ns): ?>
        <a href="<?= Url::to(['index', 'JudiciaryActionsSearch[action_nature]' => $nk]) ?>" class="ja-stat-card <?= (Yii::$app->request->get('JudiciaryActionsSearch')['action_nature'] ?? '') === $nk ? 'active' : '' ?>" style="<?= (Yii::$app->request->get('JudiciaryActionsSearch')['action_nature'] ?? '') === $nk ? 'border-color:'.$ns['color'] : '' ?>;text-decoration:none">
            <div class="ja-stat-icon" style="background:<?= $ns['bg'] ?>;color:<?= $ns['color'] ?>">
                <i class="fa <?= $ns['icon'] ?>"></i>
            </div>
            <div>
                <div class="ja-stat-num" style="color:<?= $ns['color'] ?>"><?= $statMap[$nk] ?? 0 ?></div>
                <div class="ja-stat-label"><?= $ns['label'] ?></div>
            </div>
        </a>
        <?php endforeach; ?>
        <a href="<?= Url::to(['index']) ?>" class="ja-stat-card" style="text-decoration:none">
            <div class="ja-stat-icon" style="background:#F0FDF4;color:#16A34A">
                <i class="fa fa-list"></i>
            </div>
            <div>
                <div class="ja-stat-num" style="color:#16A34A"><?= $totalActive ?></div>
                <div class="ja-stat-label">الكل</div>
            </div>
        </a>
    </div>

    <!-- Grid -->
    <div class="ja-grid">
        <div id="ajaxCrudDatatable">
            <?= GridView::widget([
                'id' => 'crud-datatable',
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'pjax' => true,
                'summary' => '<div style="font-size:12px;color:#94A3B8;padding:6px 0">عرض {begin}-{end} من {totalCount} إجراء</div>',
                'columns' => require(__DIR__ . '/_columns.php'),
                'toolbar' => [
                    ['content' =>
                        Html::a('<i class="fa fa-plus"></i>', ['create'], [
                            'title' => 'إضافة إجراء جديد',
                            'class' => 'btn btn-default',
                            'role' => 'modal-remote',
                        ]) .
                        Html::a('<i class="fa fa-repeat"></i>', [''], [
                            'data-pjax' => 1,
                            'class' => 'btn btn-default',
                            'title' => 'تحديث',
                        ]) .
                        '{toggleData}' .
                        '{export}'
                    ],
                ],
                'striped' => true,
                'condensed' => true,
                'responsive' => true,
                'panel' => [
                    'type' => 'default',
                    'heading' => '<i class="fa fa-list"></i> قائمة الإجراءات القضائية',
                ],
                'rowOptions' => function ($model) {
                    if ($model->is_deleted) {
                        return ['class' => 'ja-deleted'];
                    }
                    return [];
                },
            ]) ?>
        </div>
    </div>
</div>

<?php Modal::begin([
    'id' => 'ajaxCrudModal',
    'footer' => '',
    'size' => Modal::SIZE_LARGE,
]) ?>
<?php Modal::end(); ?>
