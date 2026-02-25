# AGENTS.md

## Project Overview

Tayseer ERP v2 - نظام تيسير لإدارة شركات التقسيط والإقراض (SaaS Multi-Tenant)

**Migrating from:** Yii2/PHP legacy system (154 tables) → NestJS + Next.js + PostgreSQL (~35 tables)
**Legacy system docs:** See `docs/SYSTEM_OVERVIEW.md` for complete reverse engineering
**DB restructure plan:** See `docs/DATABASE_RESTRUCTURE_PLAN.md` for migration rationale

## Architecture

```
SaaS Multi-Tenant (Single DB, shared schema, tenant_id column on EVERY table)
├── Backend:  NestJS + TypeScript + TypeORM + PostgreSQL
├── Frontend: Next.js + React (NOT YET BUILT)
├── Auth:     JWT + Passport (tenantId embedded in token)
├── API:      REST at /api/v1/ (GraphQL planned)
├── Docs:     Swagger at /api/docs
└── DB:       PostgreSQL (tayseer_db)
```

## Current State

### ✅ Completed Modules (11 modules, ~45 endpoints):

| # | Module | Table(s) | Endpoints | Description |
|---|--------|----------|-----------|-------------|
| 1 | **Tenants** | `tenants` | 4 | إدارة المستأجرين (SaaS) - UUID-based |
| 2 | **Auth** | - | 3 | تسجيل شركة + دخول + JWT with tenantId |
| 3 | **Users** | `users` | 5 | المستخدمون - RBAC roles, tenant-isolated |
| 4 | **Lookups** | `lookups` | 7 | 🆕 القوائم المرجعية الموحدة (بديل 15 جدول) |
| 5 | **Categories** | `categories` | 5 | 🆕 فئات الدخل والمصاريف الموحدة |
| 6 | **Companies** | `companies`, `company_banks` | 5 | المستثمرون مع حسابات بنكية |
| 7 | **Customers** | `customers`, `customer_addresses`, `customer_phones` | 7 | العملاء مع عناوين وهواتف |
| 8 | **Financial Transactions** | `financial_transactions` | 8 | 🆕 الحركات المالية الموحدة (بديل 3 جداول) |
| 9 | **Audit** | `audit_logs` | 3 | 🆕 سجل العمليات المركزي |
| 10 | **Contracts** | `contracts`, `contract_items`, `contract_installments`, `contract_parties` | ~6 | العقود (⚠️ بُني في جلسة سابقة على Tayseer-v2 مباشرة، قد يحتاج تحديث ليتوافق مع lookups و financial_transactions الجديد) |
| 11 | **Income** | - | ~5 | الدفعات (⚠️ بُني في جلسة سابقة، يجب حذفه ودمجه في financial_transactions) |

### ⚠️ تنبيهات مهمة للجلسة القادمة:

1. **موديول Income يجب حذفه** - الدفعات مدمجة الآن في `financial_transactions` بـ `type='income'`. لا حاجة لموديول منفصل.
2. **موديول Contracts يحتاج تحديث** - لأنه بُني قبل إعادة الهيكلة:
   - يجب أن يستخدم `lookups` للحقول المرجعية بدل القيم المباشرة
   - يجب أن يربط الدفعات مع `financial_transactions` بدل `income`
3. **موديول Financial Transactions بُني مرتين** - مرة في جلسة سابقة ومرة في إعادة الهيكلة. النسخة الجديدة (الموحدة مع income/expense/transfer/bank_import) هي الصحيحة.

### ❌ لم يُبنى بعد (بالترتيب المطلوب):

| الأولوية | Module | الوصف | الجداول المقترحة |
|----------|--------|-------|-----------------|
| 1 | **Follow-Up** | المتابعة | `follow_ups` |
| 2 | **Judiciary** | القضايا | `judiciary_cases`, `judiciary_actions` |
| 3 | **Courts** | المحاكم | `courts` |
| 4 | **Lawyers** | المحامون | `lawyers` |
| 5 | **Collection** | التحصيل | `collections`, `collection_installments` |
| 6 | **HR** | الموارد البشرية | `employees`, `attendance`, `payroll_runs`, `leave_requests`, `field_sessions` |
| 7 | **Inventory** | المخزون | `inventory_items`, `inventory_movements`, `suppliers`, `purchase_orders` |
| 8 | **Notifications** | الإشعارات | `notifications` |
| 9 | **SMS** | الرسائل | `sms_messages` |
| 10 | **Reports** | التقارير | لا جداول (queries على الجداول الموجودة) |
| 11 | **Dashboard** | لوحة التحكم | لا جداول (aggregation queries) |
| 12 | **System Settings** | إعدادات النظام | `system_settings` |
| 13 | **Jobs** | أماكن العمل | `jobs` |
| 14 | **Next.js Frontend** | الواجهة الأمامية | - |

## Database Design (Restructured)

### Key Design: من 154 جدول → ~35 جدول

| التحسين | التفصيل |
|---------|---------|
| **lookups table** | جدول واحد يستبدل 15 جدول lookup (city, bank, status, citizen, feeling, contact_type, etc.). استخدم `type` column للتمييز |
| **categories table** | جدول واحد بـ `type='income'\|'expense'` يستبدل expense_categories + income_category |
| **financial_transactions** | جدول واحد بـ `type='income'\|'expense'\|'transfer'\|'bank_import'` يستبدل 3 جداول (income + expenses + financial_transaction) |
| **audit_logs** | جدول مركزي لتتبع كل العمليات بدل تتبع متفرق |
| **JSONB** | استخدام JSONB للبيانات المرنة في HR بدل جداول منفصلة |

### Seed Data

عند إنشاء tenant جديد، يمكن استدعاء:
- `POST /api/v1/lookups/seed` → ينشئ 66 قيمة افتراضية (مدن، بنوك، جنسيات، إلخ)
- `POST /api/v1/categories/seed` → ينشئ 12 فئة افتراضية (5 دخل + 7 مصاريف)

### Entity Pattern

كل Entity يجب أن يحتوي:
```typescript
@Column({ type: 'uuid' })
@Index()
tenantId: string;

@ManyToOne(() => Tenant)
@JoinColumn({ name: 'tenantId' })
tenant: Tenant;
```

كل Service يجب أن يفلتر بـ:
```typescript
where: { tenantId, isDeleted: false }
```

## Key Design Decisions

- **Multi-Tenant:** Single DB, shared schema. `tenantId: uuid` on every table. All queries MUST filter by tenantId.
- **Soft Delete:** `isDeleted: boolean` on every entity. Never hard delete.
- **Unified Lookups:** One `lookups` table with `type` column replaces 15+ separate tables.
- **Unified Financials:** One `financial_transactions` table with `type` enum replaces income + expenses + financial_transaction.
- **Validation:** class-validator + class-transformer with global ValidationPipe (whitelist + transform).
- **API Prefix:** All routes under `/api/v1/`.
- **Swagger:** Auto-generated at `/api/docs` with Arabic labels.
- **Audit:** Global `AuditModule` available in all modules via `@Global()`.

## Cursor Cloud specific instructions

### Running the API
```bash
cd api
pnpm install
sudo pg_ctlcluster 16 main start
sudo -u postgres psql -c "CREATE USER tayseer WITH PASSWORD 'Tayseer@2026' CREATEDB;" 2>/dev/null
sudo -u postgres psql -c "CREATE DATABASE tayseer_db OWNER tayseer;" 2>/dev/null
pnpm run start:dev
```

### Environment Variables
Create `api/.env`:
```
DB_HOST=localhost
DB_PORT=5432
DB_USERNAME=tayseer
DB_PASSWORD=Tayseer@2026
DB_NAME=tayseer_db
JWT_SECRET=tayseer-jwt-secret-key-change-in-production
JWT_EXPIRATION=24h
PORT=3000
NODE_ENV=development
```

### Testing API
```bash
# Register (creates tenant + admin)
curl -X POST http://localhost:3000/api/v1/auth/register -H "Content-Type: application/json" \
  -d '{"companyName":"Test","companySlug":"test","username":"admin","email":"admin@test.co","password":"admin123"}'

# Login
curl -X POST http://localhost:3000/api/v1/auth/login -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"admin123"}'

# Seed lookups (after login, use token)
curl -X POST http://localhost:3000/api/v1/lookups/seed -H "Authorization: Bearer TOKEN"
curl -X POST http://localhost:3000/api/v1/categories/seed -H "Authorization: Bearer TOKEN"
```

### Module Creation Pattern
1. Entity in `src/modules/{name}/entities/` → must have `tenantId: uuid` + `isDeleted: boolean`
2. DTO in `src/modules/{name}/dto/` → class-validator decorators + @ApiProperty
3. Service in `src/modules/{name}/` → ALL queries filter by tenantId
4. Controller in `src/modules/{name}/` → extract tenantId from `req.user.tenantId`
5. Module file → register in `app.module.ts`
6. Use `lookups` table for reference data instead of creating new tables
7. Use `financial_transactions` for any money movement instead of separate tables

## Reference Documents

| File | Content |
|------|---------|
| `docs/SYSTEM_OVERVIEW.md` | Full legacy system reverse engineering (80+ modules, 154 tables, workflows) |
| `docs/DATABASE_RESTRUCTURE_PLAN.md` | Detailed plan for 154→35 table restructure with SQL schemas |
| `docs/db_columns_full.txt` | All 1,800 columns from legacy DB |
| `docs/db_indexes.txt` | All 440 legacy DB indexes |
| `docs/HR_MODULE_SPECIFICATION.md` | Detailed HR module specification |
| `docs/invoice-wizard-and-approval-flow.md` | Invoice workflow documentation |
