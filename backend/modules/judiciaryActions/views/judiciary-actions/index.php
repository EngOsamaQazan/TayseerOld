<?php
use yii\helpers\Url;
use yii\helpers\Html;
use yii\bootstrap\Modal;
use kartik\grid\GridView;
use johnitvn\ajaxcrud\CrudAsset;
use backend\modules\judiciaryActions\models\JudiciaryActions;
use backend\widgets\ExportButtons;

$this->title = 'إدارة الإجراءات القضائية';
$this->params['breadcrumbs'][] = $this->title;

CrudAsset::register($this);

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

$usageCounts = \yii\helpers\ArrayHelper::map(
    (new \yii\db\Query())
        ->select(['judiciary_actions_id', 'COUNT(*) as cnt'])
        ->from('os_judiciary_customers_actions')
        ->where(['or', ['is_deleted' => 0], ['is_deleted' => null]])
        ->groupBy('judiciary_actions_id')
        ->all(),
    'judiciary_actions_id', 'cnt'
);

$natureStyles = [
    'request'    => ['icon' => 'fa-file-text-o', 'color' => '#3B82F6', 'bg' => '#EFF6FF', 'label' => 'طلبات إجرائية'],
    'document'   => ['icon' => 'fa-file-o',      'color' => '#8B5CF6', 'bg' => '#F5F3FF', 'label' => 'كتب ومذكرات'],
    'doc_status' => ['icon' => 'fa-exchange',     'color' => '#EA580C', 'bg' => '#FFF7ED', 'label' => 'حالات كتب'],
    'process'    => ['icon' => 'fa-cog',          'color' => '#64748B', 'bg' => '#F1F5F9', 'label' => 'إجراءات إدارية'],
];

$activeNature = Yii::$app->request->get('JudiciaryActionsSearch')['action_nature'] ?? '';

$this->params['breadcrumbs'] = [];
?>
<?php $this->beginBlock('content-header'); ?>&nbsp;<?php $this->endBlock(); ?>

<style>
.ja-page{direction:rtl;font-family:'Tajawal','Segoe UI',sans-serif}

/* ═══ Layout ═══ */
.content-wrapper>.content-header{display:none !important}
.content-wrapper>.content{padding-top:12px !important}

/* ═══ Header ═══ */
.ja-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px}
.ja-header-title{font-size:18px;font-weight:700;color:#1E293B;display:flex;align-items:center;gap:8px;margin:0}
.ja-header-title i{color:#3B82F6;font-size:16px}
.ja-header-actions{display:flex;gap:6px;flex-wrap:wrap}
.ja-header-actions .btn{border-radius:8px;font-size:12px;font-weight:600;padding:6px 14px}

/* ═══ Stats ═══ */
.ja-stats{display:flex;gap:8px;margin-bottom:12px;flex-wrap:nowrap;overflow-x:auto}
.ja-stat{background:#fff;border:1px solid #E2E8F0;border-radius:8px;padding:8px 14px;display:flex;align-items:center;gap:8px;transition:all .15s;text-decoration:none !important;cursor:pointer;flex:1;min-width:0;white-space:nowrap}
.ja-stat:hover{box-shadow:0 2px 8px rgba(0,0,0,.05)}
.ja-stat.active{border-width:2px}
.ja-stat-icon{width:30px;height:30px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0}
.ja-stat-num{font-size:16px;font-weight:800;line-height:1}
.ja-stat-label{font-size:9px;color:#94A3B8;margin-top:1px}

/* ═══ Tabs ═══ */
.ja-tabs{display:flex;gap:0;margin-bottom:10px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;overflow:hidden}
.ja-tab{flex:1;padding:8px 14px;border:none;background:transparent;font-size:12px;font-weight:600;color:#64748B;cursor:pointer;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:6px;border-bottom:2px solid transparent}
.ja-tab:hover{background:#fff;color:#334155}
.ja-tab.active{background:#fff;color:#2563EB;border-bottom-color:#2563EB}
.ja-tab i{font-size:13px}

/* ═══ Grid Card ═══ */
.ja-grid-card{border-radius:12px}
.ja-grid-card .panel{margin:0 !important;border:1px solid #E2E8F0 !important;border-radius:12px !important;box-shadow:none !important}
.ja-grid-card .panel-heading{background:#FAFBFC !important;border-bottom:1px solid #E2E8F0 !important;padding:8px 12px !important;border-radius:12px 12px 0 0 !important}
.ja-grid-card .panel-heading .pull-right{display:flex;align-items:center;gap:3px}
.ja-grid-card .panel-heading .pull-right .btn{border-radius:6px !important;border:1px solid #E2E8F0 !important;background:#fff !important;color:#64748B !important;transition:all .15s;padding:4px 8px !important;font-size:12px}
.ja-grid-card .panel-heading .pull-right .btn:hover{background:#F1F5F9 !important;color:#1E293B !important}
.ja-grid-card .panel-body{border:none !important;padding:0 !important}
.ja-grid-card .panel-footer{background:#FAFBFC !important;border-top:1px solid #E2E8F0 !important;padding:6px 10px !important;border-radius:0 0 12px 12px !important}
.ja-grid-card .kv-panel-before{border-bottom:1px solid #F1F5F9 !important;padding:6px 10px !important;background:#fff !important}
.ja-grid-card .kv-panel-after{padding:6px 10px !important}
.ja-grid-card .kv-grid-container{padding:0 !important}
.ja-grid-card .table-responsive{margin:0 !important;overflow-x:hidden !important}
.ja-grid-card .kv-grid-table{margin-bottom:0 !important;width:100% !important}

/* Table styling */
.ja-grid-card .kv-grid-table{border:none !important;table-layout:fixed !important;width:100% !important}
.ja-grid-card .kv-grid-table thead th{background:#FAFBFC !important;font-weight:700 !important;font-size:11px !important;color:#64748B !important;border-bottom:2px solid #E2E8F0 !important;padding:6px 4px !important;white-space:nowrap}
.ja-grid-card .kv-grid-table thead th a{color:#64748B !important;text-decoration:none !important}
.ja-grid-card .kv-grid-table thead th a:hover{color:#334155 !important}
.ja-grid-card .kv-grid-table tbody td{font-size:12px;vertical-align:middle;padding:6px 4px !important;border-bottom:1px solid #F1F5F9 !important;border-top:none !important}
.ja-grid-card .kv-grid-table tbody tr{transition:background .15s}
.ja-grid-card .kv-grid-table tbody tr:hover{background:#F8FAFC !important}
.ja-grid-card .kv-grid-table .filters td{padding:2px 2px !important;background:#fff !important}
.ja-grid-card .kv-grid-table .filters input,.ja-grid-card .kv-grid-table .filters select{border-radius:5px !important;border:1px solid #E2E8F0 !important;font-size:10px !important;padding:3px 4px !important;transition:border-color .2s;width:100% !important;height:auto !important}
.ja-grid-card .kv-grid-table .filters input:focus,.ja-grid-card .kv-grid-table .filters select:focus{border-color:#3B82F6 !important;box-shadow:0 0 0 2px rgba(59,130,246,.08) !important}
.ja-grid-card .table-bordered{border:none !important}
.ja-grid-card .table-bordered>thead>tr>th,.ja-grid-card .table-bordered>tbody>tr>td{border-right:none !important;border-left:none !important}
/* Action buttons (custom DataColumn) */
.ja-action-btns{display:inline-flex;gap:3px;align-items:center}
.ja-act{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:7px;text-decoration:none !important;transition:all .2s;font-size:13px}
.ja-act-view{color:#8B5CF6;background:#F5F3FF}
.ja-act-view:hover{background:#EDE9FE;color:#7C3AED;transform:scale(1.12);text-decoration:none}
.ja-act-edit{color:#3B82F6;background:#EFF6FF}
.ja-act-edit:hover{background:#DBEAFE;color:#2563EB;transform:scale(1.12);text-decoration:none}
.ja-act-del{color:#EF4444;background:#FEF2F2}
.ja-act-del:hover{background:#FEE2E2;color:#DC2626;transform:scale(1.12);text-decoration:none}

/* Pagination */
.ja-grid-card .pagination>li>a,.ja-grid-card .pagination>li>span{border-radius:6px !important;margin:0 1px;border:1px solid #E2E8F0;color:#64748B;font-size:11px;padding:4px 8px}
.ja-grid-card .pagination>.active>a,.ja-grid-card .pagination>.active>span{background:#2563EB !important;border-color:#2563EB !important;color:#fff !important}
.ja-grid-card .pagination>li>a:hover{background:#F1F5F9;border-color:#CBD5E1}

/* Nature badge in grid */
.ja-nature-badge{display:inline-flex;align-items:center;gap:3px;padding:2px 6px;border-radius:6px;font-size:10px;font-weight:600;white-space:nowrap}
.ja-rel-pill{display:inline-block;padding:1px 5px;border-radius:4px;font-size:9px;font-weight:500;margin:1px;max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

/* Deleted row */
.ja-deleted{opacity:.4;text-decoration:line-through}

/* ═══ Tree View ═══ */
.ja-tree-card{background:#fff;border:1px solid #E2E8F0;border-radius:12px;overflow:hidden}
.ja-tree-header{padding:16px 20px;background:#FAFBFC;border-bottom:1px solid #E2E8F0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.ja-tree-header h3{font-size:15px;font-weight:700;color:#334155;margin:0;display:flex;align-items:center;gap:8px}
.ja-tree-header h3 i{color:#3B82F6}
.ja-tree-legend{display:flex;gap:14px;flex-wrap:wrap}
.ja-legend-item{font-size:11px;font-weight:600;display:flex;align-items:center;gap:4px}
.ja-tree-body{padding:20px;transition:opacity .3s ease}

.ja-tree-chain{margin-bottom:16px;padding:16px;background:#FAFBFC;border-radius:12px;border:1px solid #E2E8F0;transition:border-color .2s}
.ja-tree-chain:hover{border-color:#CBD5E1}

.ja-tree-node{display:flex;align-items:center;gap:12px;padding:10px 14px;background:#fff;border-radius:10px;border:1px solid #E2E8F0;transition:all .15s}
.ja-tree-node:hover{border-color:#93C5FD;box-shadow:0 2px 8px rgba(0,0,0,.04)}
.ja-tree-node-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.ja-tree-node-body{flex:1;min-width:0}
.ja-tree-node-name{font-weight:700;font-size:13px;color:#1E293B}
.ja-tree-node-meta{display:flex;gap:8px;margin-top:2px;flex-wrap:wrap}
.ja-tree-stage{font-size:10px;padding:1px 8px;border-radius:6px;background:#F1F5F9;color:#475569;font-weight:600}
.ja-tree-id{font-size:10px;color:#94A3B8;font-family:monospace}
.ja-tree-node-actions{flex-shrink:0;display:flex;gap:4px}
.ja-tree-edit-btn{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;color:#64748B;transition:all .15s}
.ja-tree-edit-btn:hover{background:#EFF6FF;color:#3B82F6;text-decoration:none}
.ja-tree-badge{font-size:9px;padding:2px 8px;border-radius:6px;font-weight:600;display:inline-flex;align-items:center;gap:3px}
.ja-tree-badge-link{text-decoration:none !important;cursor:pointer;transition:all .15s}
.ja-tree-badge-link:hover{filter:brightness(.9);transform:scale(1.05);text-decoration:none !important}
.ja-tree-delete-btn{color:#EF4444 !important}
.ja-tree-delete-btn:hover{background:#FEF2F2 !important;color:#DC2626 !important}

.ja-tree-children{margin-top:8px;padding-right:20px;border-right:2px solid #E2E8F0}
.ja-tree-children-deep{padding-right:20px;border-right:2px solid #FED7AA}
.ja-tree-branch{margin-top:8px;position:relative}
.ja-tree-connector{position:absolute;top:20px;right:-20px;width:18px;height:2px;background:#E2E8F0}
.ja-tree-connector-deep{background:#FED7AA}

.ja-tree-orphan-section{margin-top:20px;padding-top:16px;border-top:2px dashed #E2E8F0}
.ja-tree-orphan-title{font-size:13px;font-weight:700;color:#94A3B8;margin-bottom:10px;display:flex;align-items:center;gap:6px}
.ja-tree-orphan{border-style:dashed;opacity:.8}
.ja-tree-orphan:hover{opacity:1}

/* ═══ Quick Re-link ═══ */
.ja-move-btn{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;color:#16A34A;cursor:pointer;transition:all .15s;border:none;background:transparent;font-size:13px;padding:0}
.ja-move-btn:hover{background:#F0FDF4;color:#15803D;transform:scale(1.12)}
.ja-move-dropdown{position:absolute;top:100%;right:0;z-index:200;background:#fff;border:1px solid #E2E8F0;border-radius:10px;box-shadow:0 8px 30px rgba(0,0,0,.12);min-width:220px;max-height:260px;overflow-y:auto;display:none;font-size:12px;direction:rtl}
.ja-move-dropdown.show{display:block}
.ja-move-dropdown-title{padding:8px 12px;font-size:11px;font-weight:700;color:#64748B;border-bottom:1px solid #F1F5F9;background:#FAFBFC;border-radius:10px 10px 0 0;display:flex;align-items:center;gap:6px}
.ja-move-item{display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;transition:background .15s;border-bottom:1px solid #F8FAFC}
.ja-move-item:last-child{border-bottom:none}
.ja-move-item:hover{background:#F0FDF4}
.ja-move-item.current{background:#EFF6FF;font-weight:700;cursor:default}
.ja-move-item .ja-move-name{flex:1}
.ja-move-item .ja-move-id{font-size:9px;color:#94A3B8;font-family:monospace}

@media(max-width:768px){
    .ja-header{flex-direction:column;align-items:flex-start}
    .ja-stats{flex-wrap:wrap}
    .ja-stat{flex:0 0 auto;min-width:100px}
}
</style>

<div class="ja-page">

    <div class="ja-header">
        <h1 class="ja-header-title"><i class="fa fa-gavel"></i> <?= $this->title ?></h1>
        <div class="ja-header-actions">
            <?= ExportButtons::widget([
                'excelRoute' => ['export-excel'],
                'pdfRoute' => ['export-pdf'],
                'excelBtnClass' => 'btn btn-default',
                'pdfBtnClass' => 'btn btn-default',
            ]) ?>
            <?= Html::a('<i class="fa fa-plus"></i> إضافة إجراء', ['create'], [
                'class' => 'btn btn-primary',
                'role' => 'modal-remote',
            ]) ?>
        </div>
    </div>

    <div class="ja-stats">
        <?php foreach ($natureStyles as $nk => $ns): ?>
        <a href="<?= Url::to(['index', 'JudiciaryActionsSearch[action_nature]' => $nk]) ?>"
           class="ja-stat <?= $activeNature === $nk ? 'active' : '' ?>"
           style="<?= $activeNature === $nk ? 'border-color:'.$ns['color'] : '' ?>">
            <div class="ja-stat-icon" style="background:<?= $ns['bg'] ?>;color:<?= $ns['color'] ?>">
                <i class="fa <?= $ns['icon'] ?>"></i>
            </div>
            <div>
                <div class="ja-stat-num" style="color:<?= $ns['color'] ?>"><?= $statMap[$nk] ?? 0 ?></div>
                <div class="ja-stat-label"><?= $ns['label'] ?></div>
            </div>
        </a>
        <?php endforeach; ?>
        <a href="<?= Url::to(['index']) ?>" class="ja-stat <?= empty($activeNature) ? 'active' : '' ?>"
           style="<?= empty($activeNature) ? 'border-color:#16A34A' : '' ?>">
            <div class="ja-stat-icon" style="background:#F0FDF4;color:#16A34A">
                <i class="fa fa-list"></i>
            </div>
            <div>
                <div class="ja-stat-num" style="color:#16A34A"><?= $totalActive ?></div>
                <div class="ja-stat-label">الكل</div>
            </div>
        </a>
    </div>

    <div class="ja-tabs">
        <button class="ja-tab active" data-target="ja-panel-list"><i class="fa fa-th-list"></i> القائمة</button>
        <button class="ja-tab" data-target="ja-panel-tree"><i class="fa fa-sitemap"></i> شجرة التبعيات</button>
    </div>

    <!-- ═══ Panel 1: Grid ═══ -->
    <div class="ja-panel" id="ja-panel-list">
        <div class="ja-grid-card">
            <div id="ajaxCrudDatatable">
                <?= GridView::widget([
                    'id' => 'crud-datatable',
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'pjax' => true,
                    'summary' => '<div style="font-size:12px;color:#94A3B8;padding:4px 0">عرض {begin}-{end} من {totalCount} إجراء</div>',
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
                            '{toggleData}'
                        ],
                    ],
                    'striped' => false,
                    'condensed' => true,
                    'responsive' => true,
                    'panel' => [
                        'type' => 'default',
                        'heading' => '<i class="fa fa-list" style="margin-left:4px;font-size:12px"></i> <span style="font-weight:700;font-size:12px">قائمة الإجراءات</span>',
                    ],
                    'rowOptions' => function ($model) {
                        if ($model->is_deleted) return ['class' => 'ja-deleted'];
                        return [];
                    },
                ]) ?>
            </div>
        </div>
    </div>

    <!-- ═══ Panel 2: Tree ═══ -->
    <div class="ja-panel" id="ja-panel-tree" style="display:none">
        <?php
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
        foreach ($byNature['doc_status'] as $ds) {
            $parentIds = !empty($ds['parent_request_ids']) ? array_filter(array_map('intval', explode(',', $ds['parent_request_ids']))) : [];
            foreach ($parentIds as $pid) {
                foreach ($connections as $reqId => &$reqData) {
                    if (isset($reqData['documents'][$pid])) {
                        $reqData['documents'][$pid]['statuses'][] = $ds;
                    }
                }
            }
            unset($reqData);
        }
        $linkedDocIds = [];
        foreach ($connections as $reqData) {
            $linkedDocIds = array_merge($linkedDocIds, array_keys($reqData['documents']));
        }
        $orphanDocs = array_filter($byNature['document'], function($d) use ($linkedDocIds) {
            return !in_array($d['id'], $linkedDocIds);
        });
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

        <?php
        $requestListJson = [];
        foreach ($byNature['request'] as $r) {
            $requestListJson[] = ['id' => (int)$r['id'], 'name' => $r['name']];
        }
        $documentListJson = [];
        foreach ($byNature['document'] as $d) {
            $documentListJson[] = ['id' => (int)$d['id'], 'name' => $d['name']];
        }
        ?>
        <script>
        var JA_REQUESTS = <?= json_encode($requestListJson, JSON_UNESCAPED_UNICODE) ?>;
        var JA_DOCUMENTS = <?= json_encode($documentListJson, JSON_UNESCAPED_UNICODE) ?>;
        var JA_RELINK_URL = '<?= Url::to(["quick-relink"]) ?>';
        </script>

        <?php
        /**
         * Renders the usage badge as a clickable modal link
         */
        $renderUsageBadge = function($id) use ($usageCounts) {
            $count = (int)($usageCounts[$id] ?? 0);
            if ($count === 0) return '<span class="ja-tree-badge" style="background:#F1F5F9;color:#94A3B8"><i class="fa fa-circle-o"></i> 0</span>';
            $color = $count > 50 ? '#16A34A' : ($count > 10 ? '#2563EB' : '#64748B');
            $bg    = $count > 50 ? '#DCFCE7' : ($count > 10 ? '#DBEAFE' : '#F1F5F9');
            $url   = Url::to(['usage-details', 'id' => $id]);
            return Html::a(
                '<i class="fa fa-check-circle"></i> ' . number_format($count) . ' استخدام',
                $url,
                ['role' => 'modal-remote', 'title' => 'عرض القضايا المرتبطة', 'class' => 'ja-tree-badge ja-tree-badge-link', 'style' => "background:$bg;color:$color"]
            );
        };

        /**
         * Renders action buttons for a tree node
         * $nature: action_nature of the node
         * $parentId: the ID of the parent this node is shown under (0 if top-level/orphan)
         */
        $renderNodeActions = function($id, $nature = '', $parentId = 0) {
            $moveBtn = '';
            if ($nature === 'document' || $nature === 'doc_status') {
                $listType = $nature === 'document' ? 'requests' : 'documents';
                $moveBtn = '<div style="position:relative;display:inline-flex">'
                    . '<button type="button" class="ja-move-btn" title="نقل" data-item-id="' . $id . '" data-nature="' . $nature . '" data-parent-id="' . $parentId . '" data-list="' . $listType . '" onclick="JATree.showMoveDropdown(this)"><i class="fa fa-random"></i></button>'
                    . '<div class="ja-move-dropdown" id="ja-move-dd-' . $id . '-' . $parentId . '"></div>'
                    . '</div>';
            }
            return '<div class="ja-tree-node-actions">'
                . $moveBtn
                . Html::a('<i class="fa fa-eye"></i>', ['view', 'id' => $id], ['role' => 'modal-remote', 'title' => 'عرض', 'class' => 'ja-tree-edit-btn'])
                . Html::a('<i class="fa fa-pencil"></i>', ['update', 'id' => $id], ['role' => 'modal-remote', 'title' => 'تعديل', 'class' => 'ja-tree-edit-btn'])
                . Html::a('<i class="fa fa-trash-o"></i>', ['confirm-delete', 'id' => $id], ['role' => 'modal-remote', 'title' => 'حذف', 'class' => 'ja-tree-edit-btn ja-tree-delete-btn', 'data-confirm' => false, 'data-method' => false])
                . '</div>';
        };
        ?>

        <div class="ja-tree-card">
            <div class="ja-tree-header">
                <h3><i class="fa fa-sitemap"></i> شجرة تبعيات الإجراءات</h3>
                <span class="ja-tree-legend">
                    <span class="ja-legend-item" style="color:#3B82F6"><i class="fa fa-file-text-o"></i> طلب</span>
                    <span class="ja-legend-item" style="color:#8B5CF6"><i class="fa fa-file-o"></i> كتاب</span>
                    <span class="ja-legend-item" style="color:#EA580C"><i class="fa fa-exchange"></i> حالة</span>
                    <span class="ja-legend-item" style="color:#64748B"><i class="fa fa-cog"></i> إداري</span>
                </span>
            </div>
            <div class="ja-tree-body">
                <?php if (!empty($connections)): ?>
                <?php foreach ($connections as $reqId => $reqData):
                    $req = $reqData['action'];
                    $stageLabel = $stageLabels[$req['action_type']] ?? 'عام';
                    $docCount = count($reqData['documents']);
                    $totalStatuses = 0;
                    foreach ($reqData['documents'] as $dd) $totalStatuses += count($dd['statuses']);
                ?>
                <div class="ja-tree-chain">
                    <div class="ja-tree-node">
                        <div class="ja-tree-node-icon" style="background:#EFF6FF;color:#3B82F6"><i class="fa fa-file-text-o"></i></div>
                        <div class="ja-tree-node-body">
                            <div class="ja-tree-node-name"><?= Html::encode($req['name']) ?></div>
                            <div class="ja-tree-node-meta">
                                <span class="ja-tree-stage"><?= Html::encode($stageLabel) ?></span>
                                <span class="ja-tree-id">#<?= $req['id'] ?></span>
                                <?= $renderUsageBadge($req['id']) ?>
                                <?php if ($docCount > 0): ?>
                                <span class="ja-tree-badge" style="background:#DBEAFE;color:#1D4ED8"><i class="fa fa-file-o"></i> <?= $docCount ?> كتاب</span>
                                <?php endif; ?>
                                <?php if ($totalStatuses > 0): ?>
                                <span class="ja-tree-badge" style="background:#FED7AA;color:#C2410C"><i class="fa fa-exchange"></i> <?= $totalStatuses ?> حالة</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?= $renderNodeActions($req['id']) ?>
                    </div>

                    <?php if (!empty($reqData['documents'])): ?>
                    <div class="ja-tree-children">
                        <?php foreach ($reqData['documents'] as $did => $docData):
                            $doc = $docData['action'];
                            $stCount = count($docData['statuses']);
                        ?>
                        <div class="ja-tree-branch">
                            <div class="ja-tree-connector"></div>
                            <div class="ja-tree-node">
                                <div class="ja-tree-node-icon" style="background:#F5F3FF;color:#8B5CF6"><i class="fa fa-file-o"></i></div>
                                <div class="ja-tree-node-body">
                                    <div class="ja-tree-node-name"><?= Html::encode($doc['name']) ?></div>
                                    <div class="ja-tree-node-meta">
                                        <span class="ja-tree-id">#<?= $doc['id'] ?></span>
                                        <?= $renderUsageBadge($doc['id']) ?>
                                        <?php if ($stCount > 0): ?>
                                        <span class="ja-tree-badge" style="background:#FED7AA;color:#C2410C"><i class="fa fa-exchange"></i> <?= $stCount ?> حالة</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?= $renderNodeActions($doc['id'], 'document', $reqId) ?>
                            </div>

                            <?php if (!empty($docData['statuses'])): ?>
                            <div class="ja-tree-children ja-tree-children-deep">
                                <?php foreach ($docData['statuses'] as $st): ?>
                                <div class="ja-tree-branch">
                                    <div class="ja-tree-connector ja-tree-connector-deep"></div>
                                    <div class="ja-tree-node">
                                        <div class="ja-tree-node-icon" style="background:#FFF7ED;color:#EA580C"><i class="fa fa-exchange"></i></div>
                                        <div class="ja-tree-node-body">
                                            <div class="ja-tree-node-name"><?= Html::encode($st['name']) ?></div>
                                            <div class="ja-tree-node-meta">
                                                <span class="ja-tree-id">#<?= $st['id'] ?></span>
                                                <?= $renderUsageBadge($st['id']) ?>
                                            </div>
                                        </div>
                                        <?= $renderNodeActions($st['id'], 'doc_status', $did) ?>
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

                <?php if (!empty($orphanDocs)): ?>
                <div class="ja-tree-orphan-section">
                    <h4 class="ja-tree-orphan-title"><i class="fa fa-unlink"></i> كتب غير مرتبطة بطلبات</h4>
                    <?php foreach ($orphanDocs as $od): ?>
                    <div class="ja-tree-node ja-tree-orphan" style="margin-bottom:8px">
                        <div class="ja-tree-node-icon" style="background:#F5F3FF;color:#8B5CF6"><i class="fa fa-file-o"></i></div>
                        <div class="ja-tree-node-body">
                            <div class="ja-tree-node-name"><?= Html::encode($od['name']) ?></div>
                            <div class="ja-tree-node-meta">
                                <span class="ja-tree-id">#<?= $od['id'] ?></span>
                                <?= $renderUsageBadge($od['id']) ?>
                            </div>
                        </div>
                        <?= $renderNodeActions($od['id'], 'document', 0) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($orphanStatuses)): ?>
                <div class="ja-tree-orphan-section">
                    <h4 class="ja-tree-orphan-title"><i class="fa fa-unlink"></i> حالات غير مرتبطة</h4>
                    <?php foreach ($orphanStatuses as $os): ?>
                    <div class="ja-tree-node ja-tree-orphan" style="margin-bottom:8px">
                        <div class="ja-tree-node-icon" style="background:#FFF7ED;color:#EA580C"><i class="fa fa-exchange"></i></div>
                        <div class="ja-tree-node-body">
                            <div class="ja-tree-node-name"><?= Html::encode($os['name']) ?></div>
                            <div class="ja-tree-node-meta">
                                <span class="ja-tree-id">#<?= $os['id'] ?></span>
                                <?= $renderUsageBadge($os['id']) ?>
                            </div>
                        </div>
                        <?= $renderNodeActions($os['id'], 'doc_status', 0) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($byNature['process'])): ?>
                <div class="ja-tree-orphan-section">
                    <h4 class="ja-tree-orphan-title"><i class="fa fa-cog"></i> إجراءات إدارية (مستقلة)</h4>
                    <?php foreach ($byNature['process'] as $pr): ?>
                    <div class="ja-tree-node" style="margin-bottom:8px">
                        <div class="ja-tree-node-icon" style="background:#F1F5F9;color:#64748B"><i class="fa fa-cog"></i></div>
                        <div class="ja-tree-node-body">
                            <div class="ja-tree-node-name"><?= Html::encode($pr['name']) ?></div>
                            <div class="ja-tree-node-meta">
                                <span class="ja-tree-stage"><?= Html::encode($stageLabels[$pr['action_type']] ?? 'عام') ?></span>
                                <span class="ja-tree-id">#<?= $pr['id'] ?></span>
                                <?= $renderUsageBadge($pr['id']) ?>
                            </div>
                        </div>
                        <?= $renderNodeActions($pr['id']) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '', 'size' => Modal::SIZE_LARGE]) ?>
<?php Modal::end(); ?>

<script>
document.addEventListener('click', function(e) {
    var tab = e.target.closest('.ja-tab');
    if (!tab) return;
    e.preventDefault();
    var target = tab.getAttribute('data-target');
    if (!target) return;
    document.querySelectorAll('.ja-tab').forEach(function(t){ t.classList.remove('active'); });
    tab.classList.add('active');
    document.querySelectorAll('.ja-panel').forEach(function(p){ p.style.display = 'none'; });
    var panel = document.getElementById(target);
    if (panel) panel.style.display = 'block';
});

function refreshTreeContent() {
    var treeBody = document.querySelector('.ja-tree-body');
    if (treeBody) treeBody.style.opacity = '0.5';

    var xhr = new XMLHttpRequest();
    xhr.open('GET', location.pathname + location.search);
    xhr.onload = function() {
        if (xhr.status === 200) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(xhr.responseText, 'text/html');
            var newBody = doc.querySelector('.ja-tree-body');
            var curBody = document.querySelector('.ja-tree-body');
            if (newBody && curBody) {
                curBody.innerHTML = newBody.innerHTML;
                curBody.style.opacity = '1';
            } else {
                location.reload();
                return;
            }
            var scripts = doc.querySelectorAll('script');
            for (var i = 0; i < scripts.length; i++) {
                var text = scripts[i].textContent, m;
                m = text.match(/var JA_REQUESTS\s*=\s*(\[[\s\S]*?\]);/);
                if (m) try { JA_REQUESTS = JSON.parse(m[1]); } catch(e){}
                m = text.match(/var JA_DOCUMENTS\s*=\s*(\[[\s\S]*?\]);/);
                if (m) try { JA_DOCUMENTS = JSON.parse(m[1]); } catch(e){}
            }
        } else {
            location.reload();
        }
    };
    xhr.onerror = function() { location.reload(); };
    xhr.send();
}

var JATree = (function(){
    var openDD = null;

    function closeAll(){
        if (openDD) { openDD.classList.remove('show'); openDD = null; }
    }

    document.addEventListener('click', function(e){
        if (!e.target.closest('.ja-move-btn') && !e.target.closest('.ja-move-dropdown')) closeAll();
    });

    function showMoveDropdown(btn){
        var itemId = parseInt(btn.dataset.itemId);
        var nature = btn.dataset.nature;
        var parentId = parseInt(btn.dataset.parentId) || 0;
        var listType = btn.dataset.list;
        var list = listType === 'requests' ? JA_REQUESTS : JA_DOCUMENTS;
        var dd = btn.nextElementSibling;

        if (openDD === dd && dd.classList.contains('show')) { closeAll(); return; }
        closeAll();

        var title = nature === 'document' ? 'نقل إلى طلب' : 'نقل إلى كتاب';
        var html = '<div class="ja-move-dropdown-title"><i class="fa fa-random"></i> ' + title + '</div>';
        for (var i = 0; i < list.length; i++) {
            var item = list[i];
            var isCurrent = item.id === parentId;
            html += '<div class="ja-move-item' + (isCurrent ? ' current' : '') + '"'
                + ' data-target-id="' + item.id + '"'
                + ' data-item-id="' + itemId + '"'
                + ' data-old-parent="' + parentId + '"'
                + (isCurrent ? '' : ' onclick="JATree.doRelink(this)"')
                + '>'
                + '<span class="ja-move-name">' + (isCurrent ? '<i class="fa fa-check" style="color:#16A34A;margin-left:4px"></i>' : '') + item.name + '</span>'
                + '<span class="ja-move-id">#' + item.id + '</span>'
                + '</div>';
        }
        dd.innerHTML = html;
        dd.classList.add('show');
        openDD = dd;
    }

    function doRelink(el){
        var itemId = parseInt(el.dataset.itemId);
        var newParentId = parseInt(el.dataset.targetId);
        var oldParentId = parseInt(el.dataset.oldParent) || 0;

        el.innerHTML = '<i class="fa fa-spinner fa-spin"></i> جاري النقل...';
        el.style.pointerEvents = 'none';

        jQuery.post(JA_RELINK_URL, {
            item_id: itemId,
            new_parent_id: newParentId,
            old_parent_id: oldParentId,
            _csrf: yii.getCsrfToken()
        }).done(function(res){
            if (res.success) {
                refreshTreeContent();
                try { jQuery.pjax.reload({container:'#crud-datatable-pjax'}); } catch(e){}
            } else {
                alert(res.message || 'حدث خطأ');
                el.style.pointerEvents = '';
            }
        }).fail(function(){
            alert('فشل الاتصال بالسيرفر');
            el.style.pointerEvents = '';
        });
    }

    return { showMoveDropdown: showMoveDropdown, doRelink: doRelink };
})();
</script>

<?php
$reloadJs = <<<JS
(function(){
    var needsReload = false;
    $('#ajaxCrudModal').on('show.bs.modal', function(){ needsReload = false; });
    $(document).ajaxComplete(function(e, xhr){
        try {
            var d = typeof xhr.responseJSON !== 'undefined' ? xhr.responseJSON : JSON.parse(xhr.responseText);
            if (d && d.forceReload) needsReload = true;
        } catch(ex){}
    });
    $('#ajaxCrudModal').on('hidden.bs.modal', function(){
        if (needsReload) {
            refreshTreeContent();
            needsReload = false;
        }
    });
})();
JS;
$this->registerJs($reloadJs, $this::POS_READY);
?>
