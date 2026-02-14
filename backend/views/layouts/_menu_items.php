<?php
/**
 * القائمة الجانبية — ترتيب ستاندرد ERP
 * ─────────────────────────────────────
 * لوحة التحكم → العملاء → العقود → المتابعة → المالية
 * → القانوني → التقارير → الموظفين → المخزون → الديوان
 * → المستثمرين → التقييم → الصلاحيات → الإعدادات
 */

use yii\helpers\Url;
use common\helper\Permissions;

$mainMenuItems = [

    // ═══════════════════════════════════════
    //  لوحة التحكم
    // ═══════════════════════════════════════
    ['label' => 'لوحة التحكم', 'icon' => 'tachometer', 'url' => ['/site/index']],

    ['label' => 'العمليات', 'options' => ['class' => 'header']],

    // ─── 1. العملاء ───
    ['label' => 'العملاء', 'icon' => 'users', 'url' => ['/customers/customers/index'], 'privilege' => Permissions::CUSTOMERS],

    // ─── 2. العقود ───
    ['label' => 'العقود', 'icon' => 'file-text', 'url' => ['/contracts/contracts/index'], 'privilege' => Permissions::CONTRACTS],

    // ─── 3. المتابعة ───
    ['label' => 'تقرير المتابعة', 'icon' => 'phone', 'url' => ['/followUpReport/follow-up-report/index'], 'privilege' => Permissions::FOLLOW_UP_REPORT],

    // ─── 4. الإدارة المالية ───
    ['label' => 'الإدارة المالية', 'icon' => 'money', 'url' => ['/financialTransaction/financial-transaction/index'], 'privilege' => [Permissions::FINANCIAL_TRANSACTION, Permissions::INCOME, Permissions::EXPENSES, Permissions::LOAN_SCHEDULING]],

    // ─── 5. القسم القانوني ───
    [
        'label' => 'القسم القانوني',
        'icon'  => 'gavel',
        'items' => [
            ['label' => 'التحويل للقانوني', 'icon' => 'exchange',      'url' => ['/contracts/contracts/legal-department'],                        'privilege' => Permissions::TRANSFER_TO_LEGAL_DEPARTMENT],
            ['label' => 'القضايا',          'icon' => 'balance-scale',  'url' => ['/judiciary/judiciary/index'],                                   'privilege' => Permissions::JUDICIARY],
            ['label' => 'كشف المثابرة',     'icon' => 'line-chart',     'url' => ['/judiciary/judiciary/cases-report'],                            'privilege' => Permissions::JUDICIARY],
            ['label' => 'إجراءات الأطراف',  'icon' => 'legal',          'url' => ['/judiciaryCustomersActions/judiciary-customers-actions/index'], 'privilege' => Permissions::JUDICIARY_CUSTOMERS_ACTION],
            ['label' => 'التحصيل',          'icon' => 'handshake-o',    'url' => ['/collection/collection/index'],                                'privilege' => Permissions::COLLECTION],
        ],
    ],

    // ─── 6. التقارير ───
    ['label' => 'التقارير', 'icon' => 'bar-chart', 'url' => ['/reports/reports/index'], 'privilege' => Permissions::REPORTS],

    ['label' => 'الموارد', 'options' => ['class' => 'header']],

    // ─── 7. الموارد البشرية (HR) ───
    [
        'label' => 'الموارد البشرية',
        'icon'  => 'id-card',
        'items' => [
            ['label' => 'لوحة تحكم HR',      'icon' => 'tachometer',        'url' => ['/hr/hr-dashboard/index']],
            ['label' => 'سجل الموظفين',      'icon' => 'users',             'url' => ['/hr/hr-employee/index']],
            ['label' => 'العلاوات السنوية',  'icon' => 'line-chart',        'url' => ['/hr/hr-payroll/increments']],
            ['label' => 'الحضور والانصراف',   'icon' => 'clock-o',           'url' => ['/hr/hr-attendance/index']],
            ['label' => 'مسيرات الرواتب',    'icon' => 'money',             'url' => ['/hr/hr-payroll/index']],
            ['label' => 'السلف والقروض',     'icon' => 'credit-card',       'url' => ['/hr/hr-loan/index']],
            ['label' => 'تقييمات الأداء',     'icon' => 'star-half-o',       'url' => ['/hr/hr-evaluation/index']],
            ['label' => 'المهام الميدانية',   'icon' => 'map-marker',        'url' => ['/hr/hr-field/index']],
            ['label' => 'إدارة الإجازات',    'icon' => 'calendar-check-o',  'url' => ['/hr/hr-leave/index']],
            ['label' => 'تقارير HR',         'icon' => 'bar-chart',         'url' => ['/hr/hr-report/index']],
        ],
    ],

    // ─── 8. إدارة المخزون ───
    ['label' => 'إدارة المخزون', 'icon' => 'cubes', 'url' => ['/inventoryItems/inventory-items/index'], 'privilege' => [Permissions::INVENTORY_ITEMS, Permissions::INVENTORY_INVOICES, Permissions::INVENTORY_SUPPLIERS, Permissions::INVENTORY_STOCK_LOCATIONS, Permissions::INVENTORY_ITEMS_QUANTITY, Permissions::INVENTORY_IEMS_QUERY]],

    // ─── 9. المستثمرين ───
    ['label' => 'المستثمرين', 'icon' => 'building', 'url' => ['/companies/companies/index'], 'privilege' => Permissions::COMPAINES],

    // ─── 10. قسم الديوان ───
    ['label' => 'قسم الديوان', 'icon' => 'archive', 'url' => ['/diwan/diwan/index'], 'privilege' => [Permissions::DIWAN, Permissions::DIWAN_REPORTS]],

    // ─── 11. التقييم ───
    ['label' => 'التقييم', 'icon' => 'star-half-o', 'url' => ['/determination'], 'privilege' => Permissions::DETERMINATION],

    ['label' => 'الإدارة والإعدادات', 'options' => ['class' => 'header']],

    // ─── 12. إدارة الصلاحيات ───
    ['label' => 'إدارة الصلاحيات', 'icon' => 'shield', 'url' => ['/permissions-management'], 'privilege' => [Permissions::PERMISSION, Permissions::ROLE, Permissions::ASSIGNMENT]],

    // ─── 13. إعدادات النظام (تبويب واحد → صفحة موحّدة) ───
    ['label' => 'إعدادات النظام', 'icon' => 'cogs', 'url' => ['/site/system-settings']],

    // ─── تسجيل الدخول (للزوار) ───
    ['label' => 'تسجيل الدخول', 'url' => ['site/login'], 'visible' => Yii::$app->user->isGuest],
];

return Permissions::checkMainMenuItems($mainMenuItems);
