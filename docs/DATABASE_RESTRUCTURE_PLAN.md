# خطة إعادة هيكلة قاعدة البيانات
# Database Restructure Plan: 154 tables → ~35 tables

## السبب

تحليل قاعدة البيانات القديمة (namaa_jadal) أظهر:
- **62 جدول فارغ تماماً** (40% من الجداول بدون أي بيانات)
- **15 جدول Lookup بنفس البنية بالضبط** (id, name, created_at, updated_at, created_by, is_deleted)
- **5 جداول backup/temp** لا يجب أن تكون في الإنتاج
- **18 Foreign Key فقط** معرفة من أصل مئات العلاقات

## التغييرات المطلوبة

### 1. جدول `lookups` الموحد

**يستبدل 15 جدول:**
- os_bancks, os_citizen, os_city, os_connection_response, os_contact_type
- os_cousins, os_document_status, os_document_type, os_feelings
- os_hear_about_us, os_payment_type, os_status
- os_jobs_type, os_judiciary_type, os_rejester_follow_up_type

**التصميم:**
```sql
CREATE TABLE lookups (
    id SERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    type VARCHAR(50) NOT NULL,      -- 'city', 'bank', 'status', 'citizen', etc.
    name VARCHAR(255) NOT NULL,
    name_en VARCHAR(255),           -- اسم إنجليزي اختياري
    parent_id INT REFERENCES lookups(id),  -- للتصنيفات الهرمية
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    metadata JSONB,                 -- أي بيانات إضافية خاصة بالنوع
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(tenant_id, type, name)
);

CREATE INDEX idx_lookups_tenant_type ON lookups(tenant_id, type);
```

**أنواع الـ Lookup (type values):**
| type | البديل عن | سجلات في القديم |
|------|-----------|----------------|
| city | os_city | 16 |
| bank | os_bancks | 27 |
| status | os_status | 3 |
| citizen | os_citizen | 12 |
| feeling | os_feelings | 3 |
| contact_type | os_contact_type | 5 |
| connection_response | os_connection_response | 7 |
| document_type | os_document_type | 3 |
| document_status | os_document_status | 4 |
| hear_about_us | os_hear_about_us | 6 |
| payment_type | os_payment_type | 4 |
| job_type | os_jobs_type | 5 |
| judiciary_type | os_judiciary_type | 3 |
| follow_up_type | os_rejester_follow_up_type | 0 |
| cousin_type | os_cousins | 29 |

### 2. جدول `categories` الموحد

**يستبدل:**
- os_expense_categories (25 سجل)
- os_income_category (11 سجل)

**التصميم:**
```sql
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    type VARCHAR(20) NOT NULL,      -- 'income', 'expense'
    name VARCHAR(255) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(tenant_id, type, name)
);
```

### 3. توحيد الحركات المالية

**الوضع القديم:** 3 جداول منفصلة
- os_financial_transaction (16,459 سجل) ← كشوفات بنكية مستوردة
- os_income (40,332 سجل) ← دفعات العملاء
- os_expenses (16,074 سجل) ← المصاريف

**الوضع الجديد:** جدول واحد `financial_transactions`
```sql
CREATE TABLE financial_transactions (
    id SERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    type VARCHAR(20) NOT NULL,        -- 'income', 'expense', 'transfer', 'bank_import'
    status VARCHAR(20) DEFAULT 'confirmed',  -- 'pending', 'confirmed', 'reversed'
    amount DECIMAL(15,2) NOT NULL,
    date DATE NOT NULL,
    description TEXT,
    
    -- الربط
    contract_id INT REFERENCES contracts(id),
    company_id INT REFERENCES companies(id),
    category_id INT REFERENCES categories(id),
    
    -- بيانات الدفع
    payment_method VARCHAR(50),       -- بدل payment_type FK
    receipt_number VARCHAR(50),
    document_number VARCHAR(50),
    bank_reference VARCHAR(100),
    
    -- للاستيراد من كشف بنكي
    bank_description TEXT,
    import_batch_id VARCHAR(50),      -- لتجميع الاستيراد
    
    -- التتبع
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    is_deleted BOOLEAN DEFAULT false
);

CREATE INDEX idx_ft_tenant_type ON financial_transactions(tenant_id, type);
CREATE INDEX idx_ft_contract ON financial_transactions(tenant_id, contract_id);
CREATE INDEX idx_ft_date ON financial_transactions(tenant_id, date);
```

**ملاحظة:** الـ workflow القديم (استيراد → تصنيف → ترحيل) يصبح:
- الاستيراد: type='bank_import', status='pending'
- بعد التصنيف: يتحول إلى type='income' أو type='expense', status='confirmed'

### 4. تبسيط HR (من 15+ جدول إلى 5)

**الوضع القديم:** 15+ جدول HR منفصل
```
os_hr_employee_extended, os_hr_attendance, os_hr_attendance_log,
os_hr_payroll_run, os_hr_payslip, os_hr_payslip_line,
os_hr_salary_component, os_hr_loan, os_hr_evaluation,
os_hr_evaluation_score, os_hr_kpi_template, os_hr_kpi_item,
os_hr_emergency_contact, os_hr_employee_document, os_hr_employee_salary,
os_hr_disciplinary, os_hr_annual_increment, os_hr_grade,
os_hr_field_session, os_hr_field_task, os_hr_field_event,
os_hr_field_config, os_hr_field_consent, os_hr_geofence_event,
os_hr_location_point, os_hr_tracking_point, os_hr_work_zone,
os_hr_audit_log
```

**الوضع الجديد:** 5 جداول
```sql
-- 1. بيانات الموظف (مع JSONB للبيانات المتغيرة)
CREATE TABLE employees (
    id SERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    user_id INT NOT NULL REFERENCES users(id),
    employee_code VARCHAR(20),
    basic_salary DECIMAL(15,2),
    grade VARCHAR(50),
    shift JSONB,                    -- بدل جدول hr_work_shift
    emergency_contacts JSONB,       -- بدل جدول hr_emergency_contact
    documents JSONB,                -- بدل جدول hr_employee_document
    salary_components JSONB,        -- بدل جدول hr_salary_component
    is_field_staff BOOLEAN DEFAULT false,
    tracking_mode VARCHAR(20),
    work_zone JSONB,                -- بدل جدول hr_work_zone
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    is_deleted BOOLEAN DEFAULT false
);

-- 2. الحضور (مبسط)
CREATE TABLE attendance (
    id SERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    check_in TIMESTAMP,
    check_out TIMESTAMP,
    status VARCHAR(20),             -- present, absent, late, half_day
    late_minutes INT DEFAULT 0,
    overtime_minutes INT DEFAULT 0,
    location JSONB,                 -- {lat, lng, method, zone_id}
    notes TEXT,
    UNIQUE(tenant_id, user_id, date)
);

-- 3. الرواتب
CREATE TABLE payroll_runs (
    id SERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    period VARCHAR(7) NOT NULL,     -- '2026-02'
    status VARCHAR(20) DEFAULT 'draft',
    payslips JSONB,                 -- مصفوفة الرواتب لكل موظف
    totals JSONB,                   -- إجماليات
    approved_by INT,
    created_at TIMESTAMP DEFAULT NOW()
);

-- 4. الإجازات
CREATE TABLE leave_requests (
    id SERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    user_id INT NOT NULL,
    leave_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    reason TEXT,
    approved_by INT,
    created_at TIMESTAMP DEFAULT NOW()
);

-- 5. التتبع الميداني
CREATE TABLE field_sessions (
    id SERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    user_id INT NOT NULL,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP,
    status VARCHAR(20),
    location_points JSONB,          -- بدل جدول hr_location_point
    events JSONB,                   -- بدل جدول hr_field_event
    total_distance_km DECIMAL(8,2),
    device_info JSONB
);
```

### 5. جدول `audit_logs` مركزي

**يستبدل:** تتبع created_by/updated_by المتكرر + os_hr_audit_log + os_ocp_audit_log

```sql
CREATE TABLE audit_logs (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    user_id INT,
    action VARCHAR(20) NOT NULL,     -- 'create', 'update', 'delete', 'login', 'export'
    entity_type VARCHAR(50) NOT NULL, -- 'customer', 'contract', 'payment'
    entity_id INT,
    changes JSONB,                    -- {field: {old: x, new: y}}
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_audit_tenant ON audit_logs(tenant_id, entity_type, created_at DESC);
```

### 6. حذف الجداول غير المطلوبة

**لا حاجة لها في النظام الجديد:**
- os_migration (خاص بـ Yii2)
- os_menu (خاص بـ mdm/admin)
- os_auth_rule (RBAC قديم)
- os_profile, os_social_account, os_token (خاص بـ dektrium/user)
- os_gallery_image (خاص بـ gallery manager)
- os_ImageManager (سيُستبدل بنظام ملفات حديث)
- جميع جداول _backup و temp
- os_determination, os_movment, os_items, os_invoice (فارغة/غير مستخدمة)
- tbl_persistence_cache (سيحل محلها Redis أو query مباشر)
- session (سيُدار بـ JWT)

## الهيكل النهائي (~35 جدول)

```
🏢 النظام الأساسي (4)
   tenants, users, lookups, audit_logs

👥 العملاء (4)
   customers, customer_addresses, customer_phones, customer_documents

📋 العقود (4)
   contracts, contract_parties, contract_items, contract_installments

💰 المالية (4)
   financial_transactions, categories, company_banks, promissory_notes

🏛️ القانوني (4)
   judiciary_cases, judiciary_actions, courts, lawyers

📞 المتابعة والتحصيل (3)
   follow_ups, collections, collection_installments

🏢 الاستثمار (3)
   companies, shareholders, capital_transactions

👔 الموارد البشرية (5)
   employees, attendance, payroll_runs, leave_requests, field_sessions

📦 المخزون (4)
   inventory_items, inventory_movements, suppliers, purchase_orders

🔔 التواصل (2)
   notifications, sms_messages

📊 الوظائف (2)
   jobs, system_settings
```

**الإجمالي: 39 جدول** (مقابل 154 في القديم = تخفيض 75%)

## ترتيب التنفيذ (محدّث)

1. ✅ tenants, users (مبني ومختبر)
2. ✅ companies, company_banks (مبني ومختبر)
3. ✅ customers, customer_addresses, customer_phones (مبني ومختبر)
4. ✅ lookups (مبني ومختبر - 66 قيمة افتراضية، يستبدل 15 جدول)
5. ✅ categories (مبني ومختبر - 12 فئة افتراضية، يستبدل جدولين)
6. ✅ financial_transactions (مبني ومختبر - موحد: income/expense/transfer/bank_import)
7. ✅ audit_logs (مبني ومختبر - مركزي، @Global module)
8. 🔄 contracts (بُني في جلسة سابقة، يحتاج تحديث ليستخدم lookups + financial_transactions الجديد)
9. ❌ income module (بُني في جلسة سابقة، يجب حذفه - مدمج في financial_transactions)
10. ⬜ follow_ups
11. ⬜ judiciary_cases, judiciary_actions, courts, lawyers
12. ⬜ collections, collection_installments
13. ⬜ employees, attendance, payroll_runs, leave_requests, field_sessions
14. ⬜ inventory_items, inventory_movements, suppliers, purchase_orders
15. ⬜ notifications, sms_messages
16. ⬜ jobs, system_settings
17. ⬜ promissory_notes, customer_documents
18. ⬜ Next.js Frontend
