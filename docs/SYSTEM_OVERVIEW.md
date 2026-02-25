# Tayseer ERP - Reverse Engineering Documentation
# نظام تيسير - توثيق الهندسة العكسية

## نظرة عامة

**تيسير** (Tayseer) هو نظام ERP متكامل لإدارة شركات التقسيط والإقراض. مبني على **Yii2 Advanced Template** بلغة PHP.

### البنية العامة

```
┌─────────────────────────────────────────────────────┐
│                    Tayseer ERP                       │
├──────────┬──────────┬──────────┬────────────────────┤
│ Backend  │ Frontend │ Console  │ API               │
│ (Admin)  │ (Public) │ (CLI)    │ (REST)            │
│ 80+ مود  │ غير فعال │ Migrations│ v1 Module        │
├──────────┴──────────┴──────────┴────────────────────┤
│                    Common                            │
│         Models, Components, Helpers, i18n            │
├─────────────────────────────────────────────────────┤
│              MariaDB (namaa_jadal)                   │
│           154 جدول | 40,000+ سجل دفعات             │
└─────────────────────────────────────────────────────┘
```

### التقنيات المستخدمة

| التقنية | الإصدار | الاستخدام |
|---------|---------|-----------|
| PHP | ^8.3 | اللغة الأساسية |
| Yii2 | ~2.0.54 | الإطار الأساسي |
| MariaDB | 10.11+ | قاعدة البيانات |
| AdminLTE | 2.x | قالب الواجهة |
| Bootstrap | 3.x | CSS Framework |
| jQuery | 2.1.1 | JavaScript |
| Codeception | ^5.0 | الاختبارات |
| mPDF | ^8.2 | توليد PDF |
| PHPSpreadsheet | ^5.0 | Excel Import/Export |
| Google Cloud Vision | API | تصنيف المستندات بالذكاء الاصطناعي |

---

## الموديولات الأساسية

### 1. العمليات التجارية (Core Business)

| الموديول | الوصف | الجداول الرئيسية | عدد السجلات |
|----------|-------|-----------------|-------------|
| **customers** | إدارة العملاء | os_customers, os_address, os_phone_numbers | 9,329 |
| **contracts** | إدارة العقود | os_contracts, os_contracts_customers | 7,337 |
| **income** | الدفعات والإيرادات | os_income | 40,332 |
| **financialTransaction** | الحركات المالية | os_financial_transaction | 16,459 |
| **expenses** | المصاريف | os_expenses | 16,074 |
| **collection** | التحصيل والحسميات | os_collection, os_divisions_collection | 6 |
| **followUp** | المتابعة | os_follow_up | 246,052 |
| **loanScheduling** | التسويات المالية | os_loan_scheduling | 16 |

### 2. القسم القانوني (Legal)

| الموديول | الوصف | الجداول الرئيسية | عدد السجلات |
|----------|-------|-----------------|-------------|
| **judiciary** | القضايا | os_judiciary | 5,776 |
| **court** | المحاكم | os_court | 16 |
| **lawyers** | المحامون | os_lawyers | 5 |
| **judiciaryType** | أنواع القضايا | os_judiciary_type | 3 |
| **judiciaryActions** | الإجراءات القضائية | os_judiciary_actions | 75 |
| **judiciaryCustomersActions** | إجراءات العملاء | os_judiciary_customers_actions | 36,769 |

### 3. الموارد البشرية (HR)

| الموديول | الوصف |
|----------|-------|
| **hr** | موديول شامل: الحضور، الرواتب، التتبع الميداني، الإجازات، التقييمات |
| **employee** | إدارة الموظفين |
| **attendance** | الحضور والانصراف |
| **department** | الأقسام |
| **designation** | المسميات الوظيفية |
| **jobs** | أماكن العمل/جهات التوظيف |

### 4. المخزون (Inventory)

| الموديول | الوصف |
|----------|-------|
| **inventoryItems** | عناصر المخزون مع الباركود والأرقام التسلسلية |
| **inventoryInvoices** | فواتير المشتريات (متعددة المراحل) |
| **inventorySuppliers** | الموردون |
| **inventoryStockLocations** | مواقع التخزين |

### 5. الاستثمار والمالية (Investment & Finance)

| الموديول | الوصف |
|----------|-------|
| **companies** | المستثمرون/الشركات |
| **shareholders** | المساهمون |
| **capitalTransactions** | حركات رأس المال |
| **profitDistribution** | توزيع الأرباح |
| **companyBanks** | الحسابات البنكية |

### 6. الإعدادات والمراجع (Settings & Lookups)

| الموديول | الوصف |
|----------|-------|
| **city, bancks, status, citizen, feelings, contactType, connectionResponse** | جداول مرجعية |
| **documentType, documentStatus, documentHolder** | إدارة الوثائق |
| **paymentType, expenseCategories, incomeCategory** | تصنيفات مالية |
| **hearAboutUs, cousins** | بيانات تسويقية واجتماعية |

---

## Workflows الأساسية

### 1. دورة حياة العميل

```
إنشاء عميل → تقييم المخاطر (AI) → إنشاء عقد → متابعة ← → دفعات
                                         ↓                      ↓
                                    تحويل للقانوني        تسوية/إنهاء
                                         ↓
                                    إنشاء قضية ← → إجراءات قضائية
                                         ↓
                                    تحصيل (أقساط)
```

### 2. دورة حياة العقد

```
                     ┌──────────────────────────────────────┐
                     │           Contract Statuses           │
                     ├──────────────────────────────────────┤
                     │                                      │
  إنشاء ──→ active ──→ legal_department ──→ judiciary      │
                │              │                   │        │
                │              ↓                   ↓        │
                ├──→ settlement ←──────────────────┘        │
                │                                           │
                ├──→ finished                               │
                │                                           │
                └──→ canceled                               │
                                                            │
                     │  refused (مرفوض)                     │
                     └──────────────────────────────────────┘
```

### 3. دورة الحركات المالية

```
استيراد كشف بنكي (Excel)
         ↓
    تحليل تلقائي (BankStatementAnalyzer)
         ↓
    مراجعة وتأكيد
         ↓
    إنشاء حركات مالية (FinancialTransaction)
         ↓
    ┌────────────────┬────────────────┐
    ↓                ↓                ↓
  Type=1 (دائن)   Type=2 (مدين)    غير مصنف
    ↓                ↓                ↓
  ترحيل → Income  ترحيل → Expenses  تصنيف يدوي
```

### 4. دورة القضايا

```
عقد بحالة legal_department
         ↓
    إنشاء قضية (Judiciary)
         ↓
    تحديث حالة العقد → judiciary
         ↓
    إنشاء إجراءات للأطراف (JudiciaryCustomersActions)
         ↓
    ┌─────────────────────────────────┐
    │     Persistence Tracking        │
    │  أخضر: جيد | برتقالي: قريب    │
    │  أحمر: متأخر/يحتاج تجديد     │
    └─────────────────────────────────┘
         ↓
    تحصيل (Collection) ← → أقساط شهرية (DivisionsCollection)
```

---

## هيكلية قاعدة البيانات

### الجداول الأساسية وعلاقاتها

```
os_companies (المستثمرون)
  ├── os_company_banks (حسابات بنكية)
  ├── os_contracts (العقود) ──→ os_contracts_customers ──→ os_customers
  │     ├── os_income (الدفعات)
  │     ├── os_follow_up (المتابعة) [246K سجل]
  │     ├── os_judiciary (القضايا)
  │     │     ├── os_judiciary_customers_actions (إجراءات) [37K سجل]
  │     │     ├── os_judiciary_cost (تكاليف)
  │     │     └── os_deduction_document (وثائق الحجز)
  │     ├── os_collection (التحصيل)
  │     │     └── os_divisions_collection (أقساط التحصيل)
  │     ├── os_contract_inventory_item (عناصر المخزون)
  │     ├── os_contract_document_file (ملفات العقد) [10K سجل]
  │     └── os_loan_scheduling (التسويات)
  └── os_financial_transaction (الحركات المالية)

os_customers (العملاء) [9,329]
  ├── os_address (العناوين) [15,829]
  ├── os_phone_numbers (أرقام الهواتف) [35,325]
  ├── os_customers_document (وثائق العملاء) [6,178]
  ├── os_real_estate (العقارات) [14,197]
  └── os_jobs (جهات العمل) [1,314]

os_user (المستخدمون) [62]
  ├── os_auth_assignment (صلاحيات المستخدم) [637]
  ├── os_hr_employee_extended (بيانات الموظف الموسعة)
  ├── os_hr_attendance (الحضور)
  └── os_hr_field_session (جلسات العمل الميداني)
```

### إحصائيات قاعدة البيانات

| المؤشر | القيمة |
|--------|--------|
| إجمالي الجداول | 154 (148 جدول + 4 Views + 2 جداول نظام) |
| إجمالي الأعمدة | ~1,800 |
| إجمالي الفهارس | ~440 |
| أكبر جدول (سجلات) | os_follow_up: 246,052 |
| أكبر جدول (حجم) | os_judiciary_customers_actions_backup: 3.52 MB |
| Foreign Keys المعرفة | 18 |
| Views | 4 (follow_up_report, contracts_screen, inventory_dashboard, persistence_report) |

---

## نظام الصلاحيات (RBAC)

### الهيكلية

```
os_auth_item (904 عنصر صلاحية)
  ├── type=1: أدوار (Roles)
  └── type=2: صلاحيات (Permissions)

os_auth_item_child (1,118 علاقة أب/ابن)
  └── ربط الأدوار بالصلاحيات

os_auth_assignment (637 إسناد)
  └── ربط المستخدمين بالأدوار
```

### مستويات التحقق

```
الطلب الوارد
    ↓
RouteAccessBehavior
    ↓
1. هل المسار عام؟ (login, error, etc.) ──→ مسموح
    ↓
2. فحص مستوى المتحكم (Controller)
   Permissions::getRequiredPermissionsForRoute()
    ↓
3. فحص مستوى الإجراء (Action)
   Permissions::getActionPermission() ──→ view, create, update, delete
```

### الصلاحيات الرئيسية

- **صلاحيات الموديولات**: العملاء، العقود، القضاء، الدخل، المصاريف... (30+ صلاحية)
- **صلاحيات CRUD**: مشاهدة، إضافة، تعديل، حذف (لكل موديول)
- **صلاحيات خاصة**: استيراد، تصدير، ترحيل، إرجاع

---

## التكاملات الخارجية

| الخدمة | الاستخدام | الملفات |
|--------|-----------|---------|
| **Google Cloud Vision** | تصنيف المستندات تلقائياً (OCR + AI) | VisionService.php |
| **Google Maps** | تحديد مواقع العمل والموظفين | SiteController, Jobs |
| **SMS API** | إرسال رسائل نصية للعملاء | SMSHelper.php, SmsController |
| **WhatsApp Business** | إرسال رسائل واتساب | SiteController |
| **AMQP/RabbitMQ** | طابور الرسائل للعمليات غير المتزامنة | Queue component |

---

## API (REST)

### Endpoints (api/modules/v1/)

| المتحكم | الوظيفة |
|---------|---------|
| UserController | إدارة المستخدمين |
| SearchController | البحث في العقود والعملاء |
| PaymentsController | عمليات الدفع |
| CustomerImagesController | إدارة صور العملاء |

### المصادقة
- Token-based: `auth_key` parameter
- دعم اللغات: `language_id` parameter
- CORS headers مفعلة

---

## الملفات التفصيلية

| الملف | المحتوى |
|-------|---------|
| `docs/db_columns_full.txt` | جميع أعمدة جميع الجداول (1,800 سطر) |
| `docs/db_indexes.txt` | جميع الفهارس (440 سطر) |
| `docs/db_views.txt` | تعريفات الـ Views |
| `docs/db_routines.txt` | الـ Stored Procedures |

---

## ملاحظات للمرحلة القادمة (إعادة الكتابة)

### أولويات الموديولات حسب الاستخدام

1. **عالية جداً**: customers, contracts, income, financialTransaction, followUp, judiciary
2. **عالية**: expenses, collection, companies, court, lawyers
3. **متوسطة**: hr, inventoryItems, reports, jobs, sms, notification
4. **منخفضة**: diwan, realEstate, shareholders, capitalTransactions, lookup modules

### أنماط يجب تحسينها في النظام الجديد

1. **Service Layer**: نقل منطق الأعمال من Controllers إلى Services
2. **Repository Pattern**: فصل طبقة الوصول لقاعدة البيانات
3. **Event-Driven**: استخدام Events بدلاً من التحديث المباشر للكاش
4. **API-First**: بناء API أولاً ثم الواجهة
5. **Testing**: كتابة اختبارات لكل service
6. **TypeScript**: الاستفادة من Type Safety
7. **Real-time**: WebSocket للإشعارات والتحديثات الحية
