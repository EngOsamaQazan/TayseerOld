<?php
/**
 * الدائرة القانونية — واجهة V2 حديثة ومتجاوبة
 * Legal Department — Modern responsive UI (same philosophy as contracts/index)
 */

use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\LinkPager;
use yii\bootstrap\Modal;
use common\helper\Permissions;
use backend\modules\contractInstallment\models\ContractInstallment;
use backend\modules\followUp\helper\ContractCalculations;
use backend\modules\judiciary\models\Judiciary;

/* Assets — same V2 design system */
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/contracts-v2.css?v=' . time());
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/contracts-v2.js?v=' . time(), [
    'depends' => [\yii\web\JqueryAsset::class],
]);
$this->registerCss('.content-header { display: none !important; }');

$this->title = 'الدائرة القانونية';
$this->params['breadcrumbs'][] = ['label' => 'العقود', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

/* Data */
$isManager  = Yii::$app->user->can(Permissions::MANAGER);
$models     = $dataProvider->getModels();
$pagination = $dataProvider->getPagination();
$sort       = $dataProvider->getSort();
$allUsers   = $isManager
    ? ArrayHelper::map(\common\models\User::find()->select(['id', 'username'])->asArray()->all(), 'id', 'username')
    : [];

/* Sort helper */
$sortOrders = $sort->getAttributeOrders();
$sortLink = function ($attribute, $label) use ($sort, $sortOrders) {
    $url = $sort->createUrl($attribute);
    $icon = '';
    if (isset($sortOrders[$attribute])) {
        $icon = $sortOrders[$attribute] === SORT_ASC
            ? ' <i class="fa fa-sort-up ct-sort-icon active"></i>'
            : ' <i class="fa fa-sort-down ct-sort-icon active"></i>';
    } else {
        $icon = ' <i class="fa fa-sort ct-sort-icon"></i>';
    }
    return '<a href="' . Html::encode($url) . '">' . $label . $icon . '</a>';
};

$begin = $pagination->getOffset() + 1;
$end   = $begin + count($models) - 1;

/* Pre-fetch judiciary records for all contracts to avoid N+1 */
$contractIds = ArrayHelper::getColumn($models, 'id');
$judiciaryMap = [];
if (!empty($contractIds)) {
    $judRecords = Judiciary::find()
        ->where(['contract_id' => $contractIds])
        ->with(['court', 'lawyer'])
        ->orderBy(['id' => SORT_DESC])
        ->all();
    foreach ($judRecords as $jud) {
        if (!isset($judiciaryMap[$jud->contract_id])) {
            $judiciaryMap[$jud->contract_id] = $jud;
        }
    }
}
?>

<div class="ct-page ct-legal-page" role="main" aria-label="صفحة الدائرة القانونية">

    <!-- Flash messages -->
    <?php foreach (['success' => 'check-circle', 'error' => 'exclamation-circle', 'warning' => 'exclamation-triangle'] as $type => $icon): ?>
        <?php if (Yii::$app->session->hasFlash($type)): ?>
            <div class="ct-alert ct-alert-<?= $type === 'error' ? 'danger' : $type ?>" role="alert">
                <i class="fa fa-<?= $icon ?>"></i>
                <span><?= Yii::$app->session->getFlash($type) ?></span>
                <button class="ct-alert-close" aria-label="إغلاق">&times;</button>
            </div>
        <?php endif ?>
    <?php endforeach ?>

    <!-- ===== PAGE HEADER ===== -->
    <div class="ct-page-hdr">
        <div class="ct-title-area">
            <h1><i class="fa fa-legal" style="margin-left:8px;opacity:.7"></i>الدائرة القانونية</h1>
            <span class="ct-count" aria-label="إجمالي العقود"><?= number_format($dataCount) ?></span>
        </div>
        <div class="ct-hdr-actions">
            <a href="<?= Url::to(['index']) ?>" class="ct-btn ct-btn-outline" aria-label="العودة لجميع العقود">
                <i class="fa fa-arrow-right"></i> <span class="ct-hide-xs">جميع العقود</span>
            </a>
            <button class="ct-btn ct-btn-outline ct-hide-sm" id="ctExportBtn" title="تصدير CSV">
                <i class="fa fa-download"></i> <span class="ct-hide-xs">تصدير</span>
            </button>
            <button class="ct-btn ct-btn-ghost ct-show-sm" id="ctFilterToggle" aria-label="فتح الفلاتر">
                <i class="fa fa-sliders" style="font-size:18px"></i>
            </button>
        </div>
    </div>

    <!-- ===== LEGAL SUMMARY CARDS ===== -->
    <?php
    $totalContracts = $dataCount;
    $totalRemaining = 0;
    $totalCaseCosts = 0;
    $totalLawyerCosts = 0;
    foreach ($models as $m) {
        $jud = $judiciaryMap[$m->id] ?? null;
        if ($jud) {
            $totalCaseCosts += $jud->case_cost ?? 0;
            $totalLawyerCosts += $jud->lawyer_cost ?? 0;
        }
    }
    ?>
    <div class="ct-legal-stats">
        <div class="ct-legal-stat-card">
            <div class="ct-legal-stat-icon" style="background:#FFF3E0;color:#E65100"><i class="fa fa-legal"></i></div>
            <div class="ct-legal-stat-body">
                <span class="ct-legal-stat-value"><?= number_format($totalContracts) ?></span>
                <span class="ct-legal-stat-label">عقد في الدائرة القانونية</span>
            </div>
        </div>
        <div class="ct-legal-stat-card">
            <div class="ct-legal-stat-icon" style="background:#FCE4EC;color:#C62828"><i class="fa fa-money"></i></div>
            <div class="ct-legal-stat-body">
                <span class="ct-legal-stat-value"><?= number_format($totalCaseCosts) ?></span>
                <span class="ct-legal-stat-label">تكاليف القضايا (الصفحة)</span>
            </div>
        </div>
        <div class="ct-legal-stat-card">
            <div class="ct-legal-stat-icon" style="background:#E8EAF6;color:#283593"><i class="fa fa-briefcase"></i></div>
            <div class="ct-legal-stat-body">
                <span class="ct-legal-stat-value"><?= number_format($totalLawyerCosts) ?></span>
                <span class="ct-legal-stat-label">أتعاب المحاماة (الصفحة)</span>
            </div>
        </div>
    </div>

    <!-- ===== FILTER SECTION ===== -->
    <div class="ct-filter-wrap" id="ctFilterWrap">
        <div class="ct-filter-backdrop" id="ctFilterBackdrop"></div>
        <div class="ct-filter-panel" id="ctFilterPanel">
            <div class="ct-drawer-handle ct-show-sm"></div>
            <div class="ct-filter-hdr">
                <h3><i class="fa fa-search"></i> بحث وفلترة</h3>
                <span class="ct-filter-toggle-icon ct-hide-sm"><i class="fa fa-chevron-up"></i></span>
                <button class="ct-btn ct-btn-ghost ct-show-sm" id="ctDrawerClose" aria-label="إغلاق"
                        style="font-size:20px;padding:4px 8px">&times;</button>
            </div>
            <div class="ct-filter-body">
                <?= $this->render('_legal_search_v2', ['model' => $searchModel]) ?>
            </div>
        </div>
    </div>

    <!-- ===== FILTER CHIPS ===== -->
    <div class="ct-chips" id="ctChips" aria-label="الفلاتر النشطة"></div>

    <!-- ===== TOOLBAR ===== -->
    <div class="ct-toolbar">
        <div class="ct-summary">
            <?php if ($dataCount > 0): ?>
                عرض <strong><?= number_format($begin) ?>–<?= number_format($end) ?></strong>
                من أصل <strong><?= number_format($dataCount) ?></strong> عقد
            <?php else: ?>
                لا توجد نتائج
            <?php endif ?>
        </div>
        <div class="ct-quick-search">
            <i class="fa fa-search"></i>
            <input type="text" id="ctQuickSearch" placeholder="بحث سريع في النتائج..."
                   aria-label="بحث سريع في النتائج المعروضة">
        </div>
    </div>

    <!-- ===== DATA TABLE ===== -->
    <div class="ct-table-wrap">
        <?php if (empty($models)): ?>
            <div class="ct-empty">
                <i class="fa fa-inbox"></i>
                <p>لا توجد عقود مطابقة لمعايير البحث</p>
                <a href="<?= Url::to(['legal-department']) ?>" class="ct-btn ct-btn-outline">
                    <i class="fa fa-refresh"></i> عرض جميع العقود
                </a>
            </div>
        <?php else: ?>
            <table class="ct-table" role="grid">
                <thead>
                    <tr>
                        <th class="ct-th-id"><?= $sortLink('id', '#') ?></th>
                        <th>العميل</th>
                        <th><?= $sortLink('seller_id', 'البائع') ?></th>
                        <th><?= $sortLink('Date_of_sale', 'تاريخ البيع') ?></th>
                        <th><?= $sortLink('total_value', 'الإجمالي') ?></th>
                        <th>المتبقي</th>
                        <th>رقم القضية</th>
                        <th>المحكمة</th>
                        <th>المحامي</th>
                        <th>تكاليف</th>
                        <th>المتابع</th>
                        <th style="text-align:center">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($models as $m):
                        /* Customer names */
                        $customerNames = implode('، ', ArrayHelper::map($m->customers, 'id', 'name')) ?: '—';

                        /* Total with judiciary costs */
                        $jud = $judiciaryMap[$m->id] ?? null;
                        $total = $m->total_value;
                        if ($jud) $total += ($jud->case_cost ?? 0) + ($jud->lawyer_cost ?? 0);

                        /* Remaining */
                        $totalForRemain = $m->total_value;
                        if ($jud) {
                            $caseCosts = \backend\modules\expenses\models\Expenses::find()
                                ->where(['contract_id' => $m->id, 'category_id' => 4])->sum('amount') ?? 0;
                            $totalForRemain += $caseCosts + ($jud->lawyer_cost ?? 0);
                        }
                        $paid = ContractInstallment::find()->where(['contract_id' => $m->id])->sum('amount') ?? 0;
                        $remaining = $totalForRemain - $paid;

                        $sellerName = $m->seller->name ?? '—';
                        $followName = $allUsers[$m->followed_by] ?? ($m->followedBy->username ?? '—');

                        /* Judiciary details */
                        $judNumber = $jud ? ($jud->judiciary_number . '/' . ($jud->year ?: '—')) : '—';
                        $courtName = $jud && $jud->court ? $jud->court->name : '—';
                        $lawyerName = $jud && $jud->lawyer ? $jud->lawyer->name : '—';
                        $judCosts = $jud ? number_format(($jud->case_cost ?? 0) + ($jud->lawyer_cost ?? 0), 0) : '—';
                    ?>
                    <tr data-id="<?= $m->id ?>">
                        <td class="ct-td-id" data-label="#">
                            <?= $m->id ?>
                        </td>
                        <td class="ct-td-customer" data-label="العميل" title="<?= Html::encode($customerNames) ?>">
                            <?= Html::encode($customerNames) ?>
                        </td>
                        <td class="ct-td-seller" data-label="البائع">
                            <?= Html::encode($sellerName) ?>
                        </td>
                        <td class="ct-td-date" data-label="تاريخ البيع">
                            <?= $m->Date_of_sale ?>
                        </td>
                        <td class="ct-td-money" data-label="الإجمالي">
                            <?= number_format($total, 0) ?>
                        </td>
                        <td class="ct-td-money ct-td-remain" data-label="المتبقي">
                            <?= number_format($remaining, 0) ?>
                        </td>
                        <td class="ct-td-legal-num" data-label="رقم القضية">
                            <?php if ($jud): ?>
                                <span class="ct-legal-case-num"><?= Html::encode($judNumber) ?></span>
                            <?php else: ?>
                                <span class="ct-text-muted">—</span>
                            <?php endif ?>
                        </td>
                        <td class="ct-td-court" data-label="المحكمة">
                            <?= Html::encode($courtName) ?>
                        </td>
                        <td class="ct-td-lawyer" data-label="المحامي">
                            <?= Html::encode($lawyerName) ?>
                        </td>
                        <td class="ct-td-money ct-td-costs" data-label="تكاليف">
                            <?= $judCosts ?>
                        </td>
                        <td class="ct-td-follow" data-label="المتابع">
                            <?php if ($isManager): ?>
                                <?= Html::dropDownList('followedBy', $m->followed_by, $allUsers, [
                                    'class' => 'ct-follow-select followUpUser',
                                    'data-contract-id' => $m->id,
                                    'prompt' => '-- اختر --',
                                ]) ?>
                            <?php else: ?>
                                <?= Html::encode($followName) ?>
                            <?php endif ?>
                        </td>
                        <td class="ct-td-actions" data-label="">
                            <div class="ct-act-wrap">
                                <button class="ct-act-trigger" aria-label="إجراءات العقد <?= $m->id ?>"
                                        aria-haspopup="true" tabindex="0">
                                    <i class="fa fa-ellipsis-v"></i>
                                </button>
                                <div class="ct-act-menu" role="menu">
                                    <a href="<?= Url::to(['/followUp/follow-up/panel', 'contract_id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-dashboard text-primary"></i> لوحة التحكم
                                    </a>
                                    <a href="<?= Url::to(['update', 'id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-pencil text-primary"></i> تعديل
                                    </a>
                                    <a href="<?= Url::to(['print-preview', 'id' => $m->id]) ?>" target="_blank" role="menuitem">
                                        <i class="fa fa-print text-info"></i> طباعة
                                    </a>
                                    <div class="ct-act-divider"></div>
                                    <a href="<?= Url::to(['/contractInstallment/contract-installment/index', 'contract_id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-money text-success"></i> الدفعات
                                    </a>
                                    <a href="<?= Url::to(['/followUp/follow-up/index', 'contract_id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-comments text-primary"></i> المتابعة
                                    </a>
                                    <a href="<?= Url::to(['/loanScheduling/loan-scheduling/create', 'contract_id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-calendar text-info"></i> جدولة
                                    </a>
                                    <?php if ($jud): ?>
                                    <div class="ct-act-divider"></div>
                                    <a href="<?= Url::to(['/judiciary/judiciary/update', 'id' => $jud->id, 'contract_id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-gavel text-danger"></i> ملف القضية
                                    </a>
                                    <a href="<?= Url::to(['/collection/collection/create', 'contract_id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-hand-paper-o text-warning"></i> تحصيل
                                    </a>
                                    <?php else: ?>
                                    <div class="ct-act-divider"></div>
                                    <a href="<?= Url::to(['/judiciary/judiciary/create', 'contract_id' => $m->id]) ?>" role="menuitem">
                                        <i class="fa fa-gavel text-danger"></i> إنشاء قضية
                                    </a>
                                    <?php endif ?>
                                    <?php if ($isManager): ?>
                                        <div class="ct-act-divider"></div>
                                        <a href="#" class="yeas-finish" data-url="<?= Url::to(['finish', 'id' => $m->id]) ?>" role="menuitem">
                                            <i class="fa fa-check-circle text-success"></i> إنهاء العقد
                                        </a>
                                        <a href="#" class="yeas-cancel" data-url="<?= Url::to(['cancel', 'id' => $m->id]) ?>" role="menuitem">
                                            <i class="fa fa-ban text-danger"></i> إلغاء العقد
                                        </a>
                                    <?php endif ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </div>

    <!-- ===== PAGINATION ===== -->
    <?php if ($dataCount > 0): ?>
    <div class="ct-pagination-wrap">
        <?= LinkPager::widget([
            'pagination' => $pagination,
            'prevPageLabel' => '<i class="fa fa-chevron-right"></i>',
            'nextPageLabel' => '<i class="fa fa-chevron-left"></i>',
            'firstPageLabel' => '<i class="fa fa-angle-double-right"></i>',
            'lastPageLabel' => '<i class="fa fa-angle-double-left"></i>',
            'maxButtonCount' => 7,
            'options' => ['class' => 'pagination', 'aria-label' => 'تصفح الصفحات'],
        ]) ?>
    </div>
    <?php endif ?>

</div><!-- /.ct-page -->

<!-- ===== MODALS ===== -->
<?php Modal::begin([
    'id' => 'finishContractModal',
    'header' => '<h4 class="modal-title"><i class="fa fa-check-circle text-success"></i> تأكيد إنهاء العقد</h4>',
    'size' => Modal::SIZE_SMALL,
]) ?>
<div class="ct-modal-body">
    <p class="lead">هل أنت متأكد من إنهاء هذا العقد؟</p>
    <p class="text-muted">سيتم تغيير حالة العقد إلى "منتهي"</p>
    <div class="ct-modal-actions">
        <a id="finishContractBtn" href="#" class="ct-btn ct-btn-primary" style="background:#28a745;border-color:#28a745">
            <i class="fa fa-check"></i> نعم، إنهاء
        </a>
        <button type="button" class="ct-btn ct-btn-outline" data-dismiss="modal">
            <i class="fa fa-times"></i> إلغاء
        </button>
    </div>
</div>
<?php Modal::end() ?>

<?php Modal::begin([
    'id' => 'cancelContractModal',
    'header' => '<h4 class="modal-title"><i class="fa fa-ban text-danger"></i> تأكيد إلغاء العقد</h4>',
    'size' => Modal::SIZE_SMALL,
]) ?>
<div class="ct-modal-body">
    <p class="lead">هل أنت متأكد من إلغاء هذا العقد؟</p>
    <p class="text-danger"><i class="fa fa-exclamation-triangle"></i> تحذير: لا يمكن التراجع عن هذا الإجراء</p>
    <div class="ct-modal-actions">
        <a id="cancelContractBtn" href="#" class="ct-btn ct-btn-primary" style="background:#dc3545;border-color:#dc3545">
            <i class="fa fa-ban"></i> نعم، إلغاء
        </a>
        <button type="button" class="ct-btn ct-btn-outline" data-dismiss="modal">
            <i class="fa fa-times"></i> تراجع
        </button>
    </div>
</div>
<?php Modal::end() ?>

<?php Modal::begin(['id' => 'ajaxCrudModal', 'footer' => '']) ?>
<?php Modal::end() ?>

<?php
$this->registerJs(<<<'JS'
$('.ct-alert-close').on('click', function(){ $(this).closest('.ct-alert').fadeOut(300); });
setTimeout(function(){ $('.ct-alert').fadeOut(500); }, 5000);
JS
);
?>
