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

/* ═══ Tabs ═══ */
.ja-tabs {
    display:flex;gap:4px;margin-bottom:16px;padding:4px;background:#F1F5F9;border-radius:10px;
}
.ja-tab {
    flex:1;padding:10px 16px;border:none;background:transparent;border-radius:8px;
    font-size:13px;font-weight:600;color:#64748B;cursor:pointer;transition:all .2s;
    display:flex;align-items:center;justify-content:center;gap:8px;
}
.ja-tab:hover { background:rgba(255,255,255,.6);color:#334155; }
.ja-tab.active { background:#fff;color:#1E293B;box-shadow:0 1px 3px rgba(0,0,0,.08); }
.ja-tab i { font-size:14px; }

/* ═══ Tree View ═══ */
.ja-tree-container {
    background:#fff;border-radius:12px;border:1px solid #E2E8F0;padding:20px;
    box-shadow:0 1px 3px rgba(0,0,0,.04);
}
.ja-tree-header {
    display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;
    padding-bottom:14px;border-bottom:2px solid #E2E8F0;flex-wrap:wrap;gap:10px;
}
.ja-tree-header h3 { font-size:16px;font-weight:800;color:#1E293B;margin:0;display:flex;align-items:center;gap:8px; }
.ja-tree-header h3 i { color:#3B82F6; }
.ja-tree-legend { display:flex;gap:14px;flex-wrap:wrap; }
.ja-legend-item { font-size:11px;font-weight:600;display:flex;align-items:center;gap:4px; }

/* Chain wrapper */
.ja-tree-chain {
    margin-bottom:20px;padding:16px;background:#FAFBFC;border-radius:12px;
    border:1px solid #E2E8F0;
}
.ja-tree-chain:hover { border-color:#CBD5E1; }

/* Tree node */
.ja-tree-node {
    display:flex;align-items:center;gap:12px;padding:10px 14px;background:#fff;
    border-radius:10px;border:1px solid #E2E8F0;transition:all .15s;
}
.ja-tree-node:hover { border-color:#93C5FD;box-shadow:0 2px 8px rgba(0,0,0,.04); }
.ja-tree-node-icon {
    width:38px;height:38px;border-radius:10px;display:flex;align-items:center;
    justify-content:center;font-size:16px;flex-shrink:0;
}
.ja-tree-node-body { flex:1;min-width:0; }
.ja-tree-node-name { font-weight:700;font-size:13px;color:#1E293B; }
.ja-tree-node-meta { display:flex;gap:8px;margin-top:2px;flex-wrap:wrap; }
.ja-tree-stage {
    font-size:10px;padding:1px 8px;border-radius:6px;background:#F1F5F9;color:#475569;font-weight:600;
}
.ja-tree-id { font-size:10px;color:#94A3B8;font-family:monospace; }
.ja-tree-node-actions { flex-shrink:0; }
.ja-tree-edit-btn {
    display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;
    border-radius:8px;color:#64748B;transition:all .15s;
}
.ja-tree-edit-btn:hover { background:#EFF6FF;color:#3B82F6;text-decoration:none; }

/* Children / branches */
.ja-tree-children { margin-top:8px;padding-right:20px;border-right:2px solid #E2E8F0; }
.ja-tree-children-deep { padding-right:20px;border-right:2px solid #FED7AA; }
.ja-tree-branch { margin-top:8px;position:relative; }
.ja-tree-connector {
    position:absolute;top:20px;right:-20px;width:18px;height:2px;background:#E2E8F0;
}
.ja-tree-connector-deep { background:#FED7AA; }

/* Orphan sections */
.ja-tree-orphan-section {
    margin-top:20px;padding-top:16px;border-top:2px dashed #E2E8F0;
}
.ja-tree-orphan-title {
    font-size:13px;font-weight:700;color:#94A3B8;margin-bottom:10px;
    display:flex;align-items:center;gap:6px;
}
.ja-tree-orphan { border-style:dashed;opacity:.8; }
.ja-tree-orphan:hover { opacity:1; }

/* Badge */
.ja-tree-badge {
    font-size:9px;padding:1px 6px;border-radius:6px;font-weight:600;
    display:inline-flex;align-items:center;gap:3px;
}
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

    <!-- ═══ Tabs: القائمة | شجرة التبعيات ═══ -->
    <div class="ja-tabs">
        <button class="ja-tab active" data-target="ja-panel-list"><i class="fa fa-list"></i> القائمة</button>
        <button class="ja-tab" data-target="ja-panel-tree"><i class="fa fa-sitemap"></i> شجرة التبعيات</button>
    </div>

    <!-- ═══ Panel 1: Grid List ═══ -->
    <div class="ja-panel" id="ja-panel-list">
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

    <!-- ═══ Panel 2: Dependency Tree ═══ -->
    <div class="ja-panel" id="ja-panel-tree" style="display:none">
        <?php
        // Build tree data
        $allActionsTree = (new \yii\db\Query())
            ->select(['id', 'name', 'action_nature', 'action_type', 'allowed_documents', 'allowed_statuses', 'parent_request_ids'])
            ->from('os_judiciary_actions')
            ->where(['or', ['is_deleted' => 0], ['is_deleted' => null]])
            ->orderBy(['action_type' => SORT_ASC, 'name' => SORT_ASC])
            ->all();

        $byNature = ['request' => [], 'document' => [], 'doc_status' => [], 'process' => []];
        $nameMapTree = [];
        foreach ($allActionsTree as $a) {
            $n = $a['action_nature'] ?: 'process';
            $byNature[$n][] = $a;
            $nameMapTree[$a['id']] = $a;
        }

        // Build connections: request → documents → statuses
        $connections = [];
        foreach ($byNature['request'] as $req) {
            $docIds = !empty($req['allowed_documents']) ? array_filter(array_map('intval', explode(',', $req['allowed_documents']))) : [];
            $statIds = !empty($req['allowed_statuses']) ? array_filter(array_map('intval', explode(',', $req['allowed_statuses']))) : [];
            $connections[$req['id']] = [
                'action' => $req,
                'documents' => [],
                'direct_statuses' => $statIds,
            ];
            foreach ($docIds as $did) {
                if (isset($nameMapTree[$did])) {
                    $connections[$req['id']]['documents'][$did] = [
                        'action' => $nameMapTree[$did],
                        'statuses' => [],
                    ];
                }
            }
        }
        // Link doc_statuses to their parent documents
        foreach ($byNature['doc_status'] as $ds) {
            $parentIds = !empty($ds['parent_request_ids']) ? array_filter(array_map('intval', explode(',', $ds['parent_request_ids']))) : [];
            foreach ($parentIds as $pid) {
                // Find which request owns this document
                foreach ($connections as $reqId => &$reqData) {
                    if (isset($reqData['documents'][$pid])) {
                        $reqData['documents'][$pid]['statuses'][] = $ds;
                    }
                }
            }
            unset($reqData);
        }
        // Orphan documents (not linked to any request)
        $linkedDocIds = [];
        foreach ($connections as $reqData) {
            $linkedDocIds = array_merge($linkedDocIds, array_keys($reqData['documents']));
        }
        $orphanDocs = array_filter($byNature['document'], function($d) use ($linkedDocIds) {
            return !in_array($d['id'], $linkedDocIds);
        });
        // Orphan statuses (not linked to any document)
        $linkedStatIds = [];
        foreach ($connections as $reqData) {
            foreach ($reqData['documents'] as $docData) {
                foreach ($docData['statuses'] as $s) $linkedStatIds[] = $s['id'];
            }
        }
        $orphanStatuses = array_filter($byNature['doc_status'], function($s) use ($linkedStatIds) {
            return !in_array($s['id'], $linkedStatIds);
        });

        $stageLabels = JudiciaryActions::getActionTypeList();
        ?>

        <div class="ja-tree-container">
            <div class="ja-tree-header">
                <h3><i class="fa fa-sitemap"></i> شجرة تبعيات الإجراءات</h3>
                <span class="ja-tree-legend">
                    <span class="ja-legend-item" style="color:#3B82F6"><i class="fa fa-file-text-o"></i> طلب</span>
                    <span class="ja-legend-item" style="color:#8B5CF6"><i class="fa fa-file-o"></i> كتاب</span>
                    <span class="ja-legend-item" style="color:#EA580C"><i class="fa fa-exchange"></i> حالة</span>
                    <span class="ja-legend-item" style="color:#64748B"><i class="fa fa-cog"></i> إجراء إداري</span>
                </span>
            </div>

            <?php if (!empty($connections)): ?>
            <?php foreach ($connections as $reqId => $reqData):
                $req = $reqData['action'];
                $stageLabel = $stageLabels[$req['action_type']] ?? 'عام';
                $docCount = count($reqData['documents']);
                $totalStatuses = 0;
                foreach ($reqData['documents'] as $dd) $totalStatuses += count($dd['statuses']);
            ?>
            <div class="ja-tree-chain">
                <!-- Request Node -->
                <div class="ja-tree-node ja-tree-request">
                    <div class="ja-tree-node-icon" style="background:#EFF6FF;color:#3B82F6"><i class="fa fa-file-text-o"></i></div>
                    <div class="ja-tree-node-body">
                        <div class="ja-tree-node-name"><?= Html::encode($req['name']) ?></div>
                        <div class="ja-tree-node-meta">
                            <span class="ja-tree-stage"><?= Html::encode($stageLabel) ?></span>
                            <span class="ja-tree-id">#<?= $req['id'] ?></span>
                            <?php if ($docCount > 0): ?>
                            <span class="ja-tree-badge" style="background:#DBEAFE;color:#1D4ED8"><i class="fa fa-file-o"></i> <?= $docCount ?> كتاب</span>
                            <?php endif; ?>
                            <?php if ($totalStatuses > 0): ?>
                            <span class="ja-tree-badge" style="background:#FED7AA;color:#C2410C"><i class="fa fa-exchange"></i> <?= $totalStatuses ?> حالة</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="ja-tree-node-actions">
                        <?= Html::a('<i class="fa fa-pencil"></i>', ['update', 'id' => $req['id']], ['role' => 'modal-remote', 'title' => 'تعديل العلاقات والتبعيات', 'class' => 'ja-tree-edit-btn']) ?>
                        <?= Html::a('<i class="fa fa-eye"></i>', ['view', 'id' => $req['id']], ['role' => 'modal-remote', 'title' => 'عرض التفاصيل', 'class' => 'ja-tree-edit-btn']) ?>
                    </div>
                </div>

                <?php if (!empty($reqData['documents'])): ?>
                <div class="ja-tree-children">
                    <?php foreach ($reqData['documents'] as $did => $docData):
                        $doc = $docData['action'];
                        $stCount = count($docData['statuses']);
                    ?>
                    <div class="ja-tree-branch">
                        <div class="ja-tree-connector"></div>
                        <!-- Document Node -->
                        <div class="ja-tree-node ja-tree-document">
                            <div class="ja-tree-node-icon" style="background:#F5F3FF;color:#8B5CF6"><i class="fa fa-file-o"></i></div>
                            <div class="ja-tree-node-body">
                                <div class="ja-tree-node-name"><?= Html::encode($doc['name']) ?></div>
                                <div class="ja-tree-node-meta">
                                    <span class="ja-tree-id">#<?= $doc['id'] ?></span>
                                    <?php if ($stCount > 0): ?>
                                    <span class="ja-tree-badge" style="background:#FED7AA;color:#C2410C"><i class="fa fa-exchange"></i> <?= $stCount ?> حالة</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="ja-tree-node-actions">
                                <?= Html::a('<i class="fa fa-pencil"></i>', ['update', 'id' => $doc['id']], ['role' => 'modal-remote', 'title' => 'تعديل', 'class' => 'ja-tree-edit-btn']) ?>
                                <?= Html::a('<i class="fa fa-eye"></i>', ['view', 'id' => $doc['id']], ['role' => 'modal-remote', 'title' => 'عرض', 'class' => 'ja-tree-edit-btn']) ?>
                            </div>
                        </div>

                        <?php if (!empty($docData['statuses'])): ?>
                        <div class="ja-tree-children ja-tree-children-deep">
                            <?php foreach ($docData['statuses'] as $st): ?>
                            <div class="ja-tree-branch">
                                <div class="ja-tree-connector ja-tree-connector-deep"></div>
                                <div class="ja-tree-node ja-tree-status">
                                    <div class="ja-tree-node-icon" style="background:#FFF7ED;color:#EA580C"><i class="fa fa-exchange"></i></div>
                                    <div class="ja-tree-node-body">
                                        <div class="ja-tree-node-name"><?= Html::encode($st['name']) ?></div>
                                        <div class="ja-tree-node-meta"><span class="ja-tree-id">#<?= $st['id'] ?></span></div>
                                    </div>
                                    <div class="ja-tree-node-actions">
                                        <?= Html::a('<i class="fa fa-pencil"></i>', ['update', 'id' => $st['id']], ['role' => 'modal-remote', 'title' => 'تعديل', 'class' => 'ja-tree-edit-btn']) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <!-- Orphan documents -->
            <?php if (!empty($orphanDocs)): ?>
            <div class="ja-tree-orphan-section">
                <h4 class="ja-tree-orphan-title"><i class="fa fa-unlink"></i> كتب غير مرتبطة بطلبات</h4>
                <?php foreach ($orphanDocs as $od): ?>
                <div class="ja-tree-node ja-tree-document ja-tree-orphan">
                    <div class="ja-tree-node-icon" style="background:#F5F3FF;color:#8B5CF6"><i class="fa fa-file-o"></i></div>
                    <div class="ja-tree-node-body">
                        <div class="ja-tree-node-name"><?= Html::encode($od['name']) ?></div>
                        <div class="ja-tree-node-meta"><span class="ja-tree-id">#<?= $od['id'] ?></span></div>
                    </div>
                    <div class="ja-tree-node-actions">
                        <?= Html::a('<i class="fa fa-pencil"></i>', ['update', 'id' => $od['id']], ['role' => 'modal-remote', 'title' => 'تعديل', 'class' => 'ja-tree-edit-btn']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Orphan statuses -->
            <?php if (!empty($orphanStatuses)): ?>
            <div class="ja-tree-orphan-section">
                <h4 class="ja-tree-orphan-title"><i class="fa fa-unlink"></i> حالات غير مرتبطة</h4>
                <?php foreach ($orphanStatuses as $os): ?>
                <div class="ja-tree-node ja-tree-status ja-tree-orphan">
                    <div class="ja-tree-node-icon" style="background:#FFF7ED;color:#EA580C"><i class="fa fa-exchange"></i></div>
                    <div class="ja-tree-node-body">
                        <div class="ja-tree-node-name"><?= Html::encode($os['name']) ?></div>
                        <div class="ja-tree-node-meta"><span class="ja-tree-id">#<?= $os['id'] ?></span></div>
                    </div>
                    <div class="ja-tree-node-actions">
                        <?= Html::a('<i class="fa fa-pencil"></i>', ['update', 'id' => $os['id']], ['role' => 'modal-remote', 'title' => 'تعديل', 'class' => 'ja-tree-edit-btn']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Process actions -->
            <?php if (!empty($byNature['process'])): ?>
            <div class="ja-tree-orphan-section">
                <h4 class="ja-tree-orphan-title"><i class="fa fa-cog"></i> إجراءات إدارية (مستقلة)</h4>
                <?php foreach ($byNature['process'] as $pr): ?>
                <div class="ja-tree-node ja-tree-process">
                    <div class="ja-tree-node-icon" style="background:#F1F5F9;color:#64748B"><i class="fa fa-cog"></i></div>
                    <div class="ja-tree-node-body">
                        <div class="ja-tree-node-name"><?= Html::encode($pr['name']) ?></div>
                        <div class="ja-tree-node-meta">
                            <span class="ja-tree-stage"><?= Html::encode($stageLabels[$pr['action_type']] ?? 'عام') ?></span>
                            <span class="ja-tree-id">#<?= $pr['id'] ?></span>
                        </div>
                    </div>
                    <div class="ja-tree-node-actions">
                        <?= Html::a('<i class="fa fa-pencil"></i>', ['update', 'id' => $pr['id']], ['role' => 'modal-remote', 'title' => 'تعديل', 'class' => 'ja-tree-edit-btn']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>

</div><!-- /.ja-page -->

<?php Modal::begin([
    'id' => 'ajaxCrudModal',
    'footer' => '',
    'size' => Modal::SIZE_LARGE,
]) ?>
<?php Modal::end(); ?>

<script>
$(function() {
    // Tab switching
    $('.ja-tab').on('click', function() {
        var target = $(this).data('target');
        $('.ja-tab').removeClass('active');
        $(this).addClass('active');
        $('.ja-panel').hide();
        $('#' + target).show();
    });
});
</script>
