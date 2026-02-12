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

    // ─── 7. إدارة الموظفين ───
    [
        'label' => 'إدارة الموظفين',
        'icon'  => 'id-card',
        'items' => [
            ['label' => 'الموظفين',          'icon' => 'user',              'url' => ['/employee/employee/index'],                         'privilege' => Permissions::EMPLOYEE],
            ['label' => 'طلبات الإجازات',    'icon' => 'calendar-minus-o',  'url' => ['/leaveRequest/leave-request/index'],                'privilege' => Permissions::LEAVE_REQUEST],
            ['label' => 'الإجازات المعلقة',  'icon' => 'calendar-times-o',  'url' => ['/leaveRequest/leave-request/suspended-vacations'],  'privilege' => Permissions::LEAVE_REQUEST],
            ['label' => 'العطل الرسمية',     'icon' => 'calendar',          'url' => ['/holidays/holidays/index'],                         'privilege' => Permissions::HOLIDAYS],
            ['label' => 'سياسة الإجازات',    'icon' => 'file-text-o',       'url' => ['/leavePolicy/leave-policy/index'],                  'privilege' => Permissions::LEAVE_POLICY],
            ['label' => 'أنواع الإجازات',    'icon' => 'list-ul',           'url' => ['/leaveTypes/leave-types/index'],                    'privilege' => Permissions::LEAVE_TYPES],
            ['label' => 'أيام العمل',        'icon' => 'calendar-check-o',  'url' => ['/workdays/workdays/index'],                         'privilege' => Permissions::WORKDAYS],
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

    // ─── 13. الإعدادات العامة (المتغيرات) ───
    [
        'label' => 'الإعدادات العامة',
        'icon'  => 'cogs',
        'items' => [
            // ── عام ──
            ['label' => 'الحالات',            'icon' => 'toggle-on',     'url' => ['/status/status/index'],                                      'privilege' => Permissions::STATUS],
            ['label' => 'حالات المستندات',    'icon' => 'file-o',        'url' => ['/documentStatus/document-status/index'],                     'privilege' => Permissions::Document_STATUS],
            ['label' => 'أنواع المستندات',    'icon' => 'files-o',       'url' => ['/documentType/document-type/index'],                         'privilege' => Permissions::DOCYUMENT_TYPE],
            ['label' => 'طرق الدفع',          'icon' => 'credit-card',   'url' => ['/paymentType/payment-type/index'],                           'privilege' => Permissions::PAYMENT_TYPE],
            ['label' => 'فئات المصروفات',     'icon' => 'tags',          'url' => ['/expenseCategories/expense-categories/index'],               'privilege' => Permissions::EXPENSE_CATEGORIES],
            ['label' => 'البنوك',             'icon' => 'university',    'url' => ['/bancks/bancks/index'],                                      'privilege' => Permissions::BANCKS],
            // ── جغرافي ──
            ['label' => 'المدن',              'icon' => 'map-marker',    'url' => ['/city/city/index'],                                          'privilege' => Permissions::CITY],
            ['label' => 'الجنسيات',           'icon' => 'flag',          'url' => ['/citizen/citizen/index'],                                    'privilege' => Permissions::CITIZEN],
            // ── عملاء ──
            ['label' => 'صلة القرابة',        'icon' => 'sitemap',       'url' => ['/cousins/cousins/index'],                                   'privilege' => Permissions::COUSINS],
            ['label' => 'مصدر التعرف علينا',  'icon' => 'bullhorn',      'url' => ['/hearAboutUs/hear-about-us/index'],                         'privilege' => Permissions::HEAR_ABOUT_US],
            ['label' => 'المشاعر',            'icon' => 'smile-o',       'url' => ['/feelings/feelings/index'],                                 'privilege' => Permissions::FEELINGS],
            ['label' => 'أنواع الاتصال',      'icon' => 'phone-square',  'url' => ['/contactType/contact-type/index'],                          'privilege' => Permissions::CONTACT_TYPE],
            ['label' => 'ردود الاتصال',       'icon' => 'reply',         'url' => ['/connectionResponse/connection-response/index'],             'privilege' => Permissions::CONNECTION_RESPONSE],
            // ── قضائي ──
            ['label' => 'الإجراءات القضائية', 'icon' => 'legal',         'url' => ['/judiciaryActions/judiciary-actions/index'],                 'privilege' => Permissions::JUDICIARY_ACTION],
            ['label' => 'أنواع القضايا',      'icon' => 'folder-open',   'url' => ['/judiciaryType/judiciary-type/index'],                      'privilege' => Permissions::JUDICIARY_TYPE],
            ['label' => 'المحامون',           'icon' => 'briefcase',     'url' => ['/lawyers/lawyers/index'],                                   'privilege' => Permissions::LAWYERS],
            ['label' => 'المحاكم',            'icon' => 'institution',   'url' => ['/court/court/index'],                                       'privilege' => Permissions::COURT],
            ['label' => 'عناوين التبليغ',     'icon' => 'map-signs',     'url' => ['/JudiciaryInformAddress/judiciary-inform-address/index'],    'privilege' => Permissions::JUDICIARY_INFORM_ADDRESS],
            // ── وظائف ──
            ['label' => 'جهات العمل',         'icon' => 'building-o',    'url' => ['/jobs/jobs/index'],                                         'privilege' => Permissions::JOBS],
            ['label' => 'المسميات الوظيفية',  'icon' => 'id-badge',      'url' => ['/designation/designation/index'],                           'privilege' => Permissions::EXPENSE_CATEGORIES],
            // ── رسائل ──
            ['label' => 'الرسائل النصية',     'icon' => 'envelope',      'url' => ['/sms/sms/index'],                                          'privilege' => Permissions::COURT],
        ],
    ],

    // ─── تسجيل الدخول (للزوار) ───
    ['label' => 'تسجيل الدخول', 'url' => ['site/login'], 'visible' => Yii::$app->user->isGuest],
];

return Permissions::checkMainMenuItems($mainMenuItems);
