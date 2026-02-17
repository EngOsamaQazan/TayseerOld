<?php

namespace common\helper;

use Yii;
//Yii::$app->user->can('مدير')
class Permissions
{

    const CUSTOMERS = 'العملاء';
    const ROUTE = 'الجذر';
    const ASSIGNMENT = 'اسناد الصلاحيات  للموظفين';
    const ROLE = 'القواعد';
    const PERMISSION = 'الصلاحيات';
    const LOAN_SCHEDULING = 'التسويات الماليه';
    const EXPENSES = 'المصاريف';
    const FINANCIAL_TRANSACTION = 'الحركات المالية';
    const EXPENSE_CATEGORIES = 'فئات المصايف';
    const EMPLOYEE = 'الموظفين';
    const INCOME = 'الدخل';
    const FOLLOW_UP_REPORT = 'تقرير المتابعة';
    const CONTRACTS = 'العقود';
    const INVENTORY_ITEMS = 'عناصر المخزون';
    const INVENTORY_STOCK_LOCATIONS = 'مواقع المخزون';
    const INVENTORY_SUPPLIERS = 'موردي المخزون';
    const COMPANIES = 'الشركات';
    const INVENTORY_ITEMS_QUANTITY = 'كمية عناصر المخزون';
    const TRANSFER_TO_LEGAL_DEPARTMENT = 'التحويل إلى الدائره القانونية';
    const LAWYERS = 'المحامون';
    const COURT = 'المحاكم';
    const JUDICIARY_TYPE = 'انواع القضايا';
    const JUDICIARY = 'القضاء';
    const JUDICIARY_ACTION = 'الإجراءات القضائية';
    const JUDICIARY_CUSTOMERS_ACTION = 'إجراءات العملاء القضائية';
    const Notification = 'الاشعارات';
    const HOLIDAYS = 'العطل';
    const LEAVE_POLICY = 'سياسات الاجازات';
    const LEAVE_TYPES = 'أنواع الإجازات';
    const WORKDAYS = 'أيام العمل';
    const  LEAVE_REQUEST = 'طلب إجازة';
    const  ARCHEVE = 'أرشيف';
    const  DOCUMENT_HOLDER = 'حامل الوثيقة';
    const  MANAGER = 'الاداره';
    const  DIWAN = 'الديوان';
    const  DIWAN_REPORTS = 'تقارير الديوان';
    const  JOBS = 'الوظائف';
    const  DETERMINATION = 'تحديد';
    const  COLLECTION = 'الحسميات';
    const COMPAINES = 'المستثمرين';
    const REPORTS = 'التقارير';
    const STATUS = 'الحالات';
    const COUSINS = 'الاقارب';
    const CITIZEN = 'الجنسيه';
    const BANCKS = 'البنوك';
    const HEAR_ABOUT_US = 'كيف سمعت عنا';
    const CITY = 'المدن';
    const PAYMENT_TYPE = 'طرق الدفع';
    const FEELINGS = 'الانفعالات';
    const CONTACT_TYPE = 'طريقة الاتصال';
    const CONNECTION_RESPONSE = 'نتيجة الاتصال';
    const CLIENT_RESPONSE = 'رد العميل';
    const MESSAGES = 'الرسائل';
    const  DOCYUMENT_TYPE = 'انواع الوثائق';
    const Document_STATUS = 'حالات الوثائق';
    const INVENTORY_INVOICES = 'فواتير المخزون';
    const INVENTORY_IEMS_QUERY = 'استعلام عناصر المخزون';
    const FINANCIAL_TRANSACTION_TO_EXPORT_DATA = 'الحركات المالية لتصدير ونقل البيانات';
    const COLLECTION_MANAGER = 'مدير التحصيل';
    const JUDICIARY_INFORM_ADDRESS = 'الموطن المختار';

    /** لوحة التحكم الرئيسية — ملخص أعمال الشركة (تظهر في قسم الإدارة) */
    const DASHBOARD = 'لوحة التحكم';

    /** أدوات المستخدم (فحص حساب، إصلاح، تعيين كلمة مرور) — إدارة الصلاحيات */
    const USER_TOOLS = 'أدوات المستخدم';

    /** اشعارات الموظفين — للموارد البشرية */
    const EMPLOYEE_NOTIFICATIONS = 'اشعارات الموظفين';

    /* ═══════════════════════════════════════════════════════════════
     *  صلاحيات الإجراءات — الحركات المالية (Action-Level)
     * ═══════════════════════════════════════════════════════════════ */
    const FIN_EDIT     = 'الحركات المالية: تعديل';
    const FIN_DELETE   = 'الحركات المالية: حذف';
    const FIN_IMPORT   = 'الحركات المالية: استيراد';
    const FIN_TRANSFER = 'الحركات المالية: ترحيل';

    /* ═══ صلاحيات الإجراءات — الدفعات ═══ */
    const INC_EDIT   = 'الدخل: تعديل';
    const INC_DELETE  = 'الدخل: حذف';
    const INC_REVERT = 'الدخل: ارجاع';

    /* ═══ صلاحيات الإجراءات — المصاريف ═══ */
    const EXP_EDIT   = 'المصاريف: تعديل';
    const EXP_DELETE  = 'المصاريف: حذف';
    const EXP_REVERT = 'المصاريف: ارجاع';

    /** صلاحيات الموارد البشرية (أي واحدة تكفي للوصول لشاشات HR) */
    public static function getHrPermissions()
    {
        return [
            self::EMPLOYEE,
            self::JOBS,
            self::HOLIDAYS,
            self::LEAVE_POLICY,
            self::LEAVE_TYPES,
            self::WORKDAYS,
            self::LEAVE_REQUEST,
            self::EMPLOYEE_NOTIFICATIONS,
        ];
    }

    /** صلاحيات إعدادات النظام (أي واحدة تكفي للوصول لصفحة الإعدادات) */
    public static function getSettingsPermissions()
    {
        return [
            self::STATUS,
            self::Document_STATUS,
            self::COUSINS,
            self::CITIZEN,
            self::BANCKS,
            self::HEAR_ABOUT_US,
            self::CITY,
            self::PAYMENT_TYPE,
            self::FEELINGS,
            self::CONTACT_TYPE,
            self::CLIENT_RESPONSE,
            self::DOCYUMENT_TYPE,
            self::MESSAGES,
        ];
    }

    /**
     * فحص AND — المستخدم يملك **كل** الصلاحيات المُمرّرة
     */
    public static function hasPermissionOn($permission)
    {
        $permission = is_array($permission) ? $permission : [$permission];
        $hasPermission = true;
        foreach ($permission as $key => $permissionName) {
            if (!Yii::$app->user->can($permissionName)) {
                $hasPermission = false;
                break;
            }
        }
        return $hasPermission;
    }

    /**
     * فحص OR — المستخدم يملك **أي** صلاحية من المُمرّرة
     */
    public static function hasAnyPermission($permissions)
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        foreach ($permissions as $permissionName) {
            if (Yii::$app->user->can($permissionName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * خريطة المسارات والصلاحيات — للتحقق من الوصول المباشر عبر الرابط
     * المفتاح: بادئة المسار (مثلاً hr/) أو المسار الكامل (مثلاً customers/customers)
     * القيمة: مصفوفة صلاحيات (يكفي امتلاك أي واحدة)
     * إذا المسار غير موجود في الخريطة يُسمح بالوصول (للتوافق مع لوحة التحكم والروابط العامة).
     */
    public static function getRoutePermissionMap()
    {
        return [
            /* لوحة التحكم — الصفحة الرئيسية */
            'site' => [self::DASHBOARD],
            /* العملاء */
            'customers/customers' => [self::CUSTOMERS],
            'customers/smart-media' => [self::CUSTOMERS],
            /* العقود والمتابعة */
            'contracts/contracts' => [self::CONTRACTS],
            'followUp/follow-up' => [self::CONTRACTS],
            'followUpReport/follow-up-report' => [self::FOLLOW_UP_REPORT],
            /* المالية */
            'financialTransaction/financial-transaction' => [self::FINANCIAL_TRANSACTION, self::INCOME, self::EXPENSES, self::LOAN_SCHEDULING],
            'income/income' => [self::INCOME],
            'expenses/expenses' => [self::EXPENSES],
            'expenseCategories/expense-categories' => [self::EXPENSE_CATEGORIES],
            'loanScheduling/loan-scheduling' => [self::LOAN_SCHEDULING],
            'movment/movment' => [self::FINANCIAL_TRANSACTION],
            /* القضاء والقانون */
            'judiciary/judiciary' => [self::JUDICIARY],
            'judiciaryActions/judiciary-actions' => [self::JUDICIARY_ACTION],
            'judiciaryCustomersActions/judiciary-customers-actions' => [self::JUDICIARY_CUSTOMERS_ACTION],
            'judiciaryType/judiciary-type' => [self::JUDICIARY_TYPE],
            'court/court' => [self::COURT],
            'lawyers/lawyers' => [self::LAWYERS],
            'JudiciaryInformAddress/judiciary-inform-address' => [self::JUDICIARY_INFORM_ADDRESS],
            /* التحصيل */
            'collection/collection' => [self::COLLECTION],
            /* التقارير */
            'reports/reports' => [self::REPORTS],
            /* الموارد البشرية (متحكمات خارج موديول hr) */
            'employee/employee' => self::getHrPermissions(),
            'designation/designation' => self::getHrPermissions(),
            'holidays/holidays' => [self::HOLIDAYS],
            'leaveTypes/leave-types' => [self::LEAVE_TYPES],
            'leaveRequest/leave-request' => [self::LEAVE_REQUEST],
            'leavePolicy/leave-policy' => [self::LEAVE_POLICY],
            'workdays/workdays' => [self::WORKDAYS],
            'attendance/attendance' => self::getHrPermissions(),
            'jobs/jobs' => [self::JOBS],
            /* المخزون */
            'inventoryItems/inventory-items' => [self::INVENTORY_ITEMS, self::INVENTORY_INVOICES, self::INVENTORY_SUPPLIERS, self::INVENTORY_STOCK_LOCATIONS, self::INVENTORY_ITEMS_QUANTITY, self::INVENTORY_IEMS_QUERY],
            'inventoryInvoices/inventory-invoices' => [self::INVENTORY_INVOICES],
            'inventorySuppliers/inventory-suppliers' => [self::INVENTORY_SUPPLIERS],
            'inventoryStockLocations/inventory-stock-locations' => [self::INVENTORY_STOCK_LOCATIONS],
            'inventoryItemQuantities/inventory-item-quantities' => [self::INVENTORY_ITEMS_QUANTITY],
            'itemsInventoryInvoices/items-inventory-invoices' => [self::INVENTORY_INVOICES],
            /* المستثمرين */
            'companies/companies' => [self::COMPAINES],
            /* الديوان */
            'diwan/diwan' => [self::DIWAN, self::DIWAN_REPORTS],
            /* إدارة الصلاحيات وأدوات المستخدم */
            'permissions-management' => [self::PERMISSION, self::ROLE, self::ASSIGNMENT],
            'user-tools' => [self::USER_TOOLS],
            /* الإعدادات والمراجع */
            'status/status' => self::getSettingsPermissions(),
            'documentStatus/document-status' => self::getSettingsPermissions(),
            'cousins/cousins' => self::getSettingsPermissions(),
            'citizen/citizen' => self::getSettingsPermissions(),
            'bancks/bancks' => self::getSettingsPermissions(),
            'hearAboutUs/hear-about-us' => self::getSettingsPermissions(),
            'city/city' => self::getSettingsPermissions(),
            'paymentType/payment-type' => self::getSettingsPermissions(),
            'feelings/feelings' => self::getSettingsPermissions(),
            'contactType/contact-type' => self::getSettingsPermissions(),
            'connectionResponse/connection-response' => self::getSettingsPermissions(),
            'documentType/document-type' => self::getSettingsPermissions(),
            'notification/notification' => [self::Notification],
            'phoneNumbers/phone-numbers' => self::getSettingsPermissions(),
            'location/location' => self::getSettingsPermissions(),
            'address/address' => self::getSettingsPermissions(),
            /* أخرى */
            'documentHolder/document-holder' => [self::DOCUMENT_HOLDER],
            'department/department' => [self::MANAGER],
            'authAssignment/auth-assignment' => [self::ASSIGNMENT],
            'rejesterFollowUpType/rejester-follow-up-type' => [self::CONTRACTS],
            'contractInstallment/contract-installment' => [self::CONTRACTS],
            'contractDocumentFile/contract-document-file' => [self::CONTRACTS],
            'invoice/invoice' => [self::INCOME],
            'incomeCategory/income-category' => [self::INCOME],
            'items/items' => [self::INVENTORY_ITEMS],
            'divisionsCollection/divisions-collection' => [self::COLLECTION],
            /* مدير الصور / ImageManager */
            'imagemanager' => [self::MANAGER],
            'imagemanager/imagemanager' => [self::MANAGER],
        ];
    }

    /**
     * إرجاع مصفوفة مسطّحة بكل الصلاحيات المذكورة في خريطة المسارات (لا تكرار)
     * — للاستخدام في «إظهار لوحة التحكم فقط لمن لديه أي صلاحية»
     */
    public static function getAllMappedPermissions()
    {
        $out = [];
        foreach (self::getRoutePermissionMap() as $perms) {
            $arr = is_array($perms) ? $perms : [$perms];
            foreach ($arr as $p) {
                if (is_string($p)) $out[$p] = true;
            }
        }
        foreach (self::getHrPermissions() as $p) {
            $out[$p] = true;
        }
        return array_keys($out);
    }

    /**
     * إرجاع صلاحيات مطلوبة لمسار معيّن (معرّف المتحكم) أو null إذا لا قيد
     */
    /**
     * إرجاع صلاحيات مطلوبة لمسار معيّن.
     * يدعم المسارات المختصرة (مثل customers بدل customers/customers) لأن urlManager ينتج روابط قصيرة.
     */
    public static function getRequiredPermissionsForRoute($controllerUniqueId)
    {
        $map = self::getRoutePermissionMap();
        if (isset($map[$controllerUniqueId])) {
            return $map[$controllerUniqueId];
        }
        // مسار مختصر من segment واحد (مثلاً customers، contracts) — نطابق مع module/controller
        if (strpos($controllerUniqueId, '/') === false) {
            $expanded = $controllerUniqueId . '/' . $controllerUniqueId;
            if (isset($map[$expanded])) {
                return $map[$expanded];
            }
            // محاولة كيكس: financialTransaction → financialTransaction/financial-transaction
            foreach (array_keys($map) as $key) {
                if (strpos($key, $controllerUniqueId . '/') === 0) {
                    return $map[$key];
                }
            }
        }
        // نظام الحضور والانصراف — متاح لجميع الموظفين المسجّلين
        if ($controllerUniqueId === 'hr/hr-field') {
            return [];
        }
        if (strpos($controllerUniqueId, 'hr/') === 0) {
            return self::getHrPermissions();
        }
        return null;
    }

    /**
     * فلترة عناصر القائمة الجانبية
     * ─────────────────────────────
     * يدعم privilege كـ:
     *   - string → فحص صلاحية واحدة (AND كالسابق)
     *   - array  → فحص OR (يكفي امتلاك أي صلاحية)
     */
    public static function checkMainMenuItems($items)
    {
        foreach ($items as $key => $menuItem) {
            // ── تجاوز العناوين (headers) — لا تحتاج صلاحيات ──
            if (isset($menuItem['options']['class']) && strpos($menuItem['options']['class'], 'header') !== false) {
                continue;
            }

            // ── تجاوز العناصر بدون privilege ولكن لها url (متاحة للجميع مثل لوحة التحكم) ──
            if (!isset($menuItem['privilege']) && !isset($menuItem['items']) && isset($menuItem['url'])) {
                continue;
            }

            if (isset($menuItem['privilege'])) {
                $priv = $menuItem['privilege'];
                /* إذا privilege مصفوفة → فحص OR */
                if (is_array($priv)) {
                    if (!self::hasAnyPermission($priv)) {
                        unset($items[$key]);
                        continue;
                    }
                } else {
                    if (!Yii::$app->user->can($priv)) {
                        unset($items[$key]);
                        continue;
                    }
                }
            }

            if (isset($menuItem['items'])) {
                $items[$key]['items'] = self::checkMainMenuItems($menuItem['items']);
            }

            // حذف القوائم الفرعية الفارغة فقط
            if (isset($menuItem['items']) && count($items[$key]['items'] ?? []) == 0) {
                unset($items[$key]);
            }
        }
        return $items;
    }
}