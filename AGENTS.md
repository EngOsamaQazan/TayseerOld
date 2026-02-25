# AGENTS.md

## Project Overview

Tayseer ERP v2 - نظام تيسير لإدارة شركات التقسيط والإقراض (SaaS Multi-Tenant)

**Migrating from:** Yii2/PHP legacy system → NestJS + Next.js + PostgreSQL
**Legacy system docs:** See `docs/SYSTEM_OVERVIEW.md` for complete reverse engineering documentation

## Architecture

```
SaaS Multi-Tenant (Single DB, shared schema, tenant_id column)
├── Backend: NestJS + TypeScript + TypeORM + PostgreSQL
├── Frontend: Next.js + React (NOT YET BUILT)
├── Auth: JWT + Passport (tenantId embedded in token)
├── API: REST (GraphQL planned)
└── Docs: Swagger at /api/docs
```

## Current State (What's Built)

### Completed Modules:
1. **Tenants** - Multi-tenant management (UUID-based tenant_id)
2. **Auth** - Register company + admin, login, JWT with tenantId
3. **Users** - CRUD with RBAC roles, tenant-isolated
4. **Companies** - Investors with bank accounts, tenant-isolated
5. **Customers** - With addresses and phones, tenant-isolated

### NOT Yet Built (Priority Order):
1. **Contracts** - العقود (core business - links customers, companies, inventory)
2. **Income** - الدفعات والإيرادات
3. **Financial Transactions** - الحركات المالية (bank statement import)
4. **Expenses** - المصاريف
5. **Follow-Up** - المتابعة (246K records in legacy)
6. **Judiciary** - القضايا القانونية
7. **Court, Lawyers** - المحاكم والمحامون
8. **Collection** - التحصيل
9. **HR Module** - الموارد البشرية (attendance, payroll, field tracking)
10. **Inventory** - المخزون
11. **Reports** - التقارير
12. **Notifications/SMS** - الإشعارات والرسائل
13. **Dashboard** - لوحة التحكم
14. **Next.js Frontend** - الواجهة الأمامية

## Key Design Decisions

- **Multi-Tenant:** Every entity has `tenantId: uuid` column. All queries MUST filter by tenantId.
- **Soft Delete:** All entities use `isDeleted: boolean` instead of hard delete.
- **Base Entity Pattern:** Use `BaseTenantEntity` from `src/common/entities/base-tenant.entity.ts`
- **Validation:** class-validator + class-transformer with global ValidationPipe
- **API Prefix:** All routes under `/api/v1/`
- **Swagger:** Auto-generated at `/api/docs`

## Legacy System Reference

Full reverse engineering documentation in `docs/`:
- `docs/SYSTEM_OVERVIEW.md` - Complete system architecture, 80+ modules, workflows, DB schema (154 tables)
- `docs/db_columns_full.txt` - All 1,800 columns across all tables
- `docs/db_indexes.txt` - All 440 database indexes

### Legacy DB Stats (for reference):
- Customers: 9,329 | Contracts: 7,337 | Follow-ups: 246,052
- Income: 40,332 | Financial Transactions: 16,459 | Judiciary: 5,776

## Cursor Cloud specific instructions

### Running the API
```bash
cd api
pnpm install
# Ensure PostgreSQL is running: sudo pg_ctlcluster 16 main start
# Create DB if needed: sudo -u postgres psql -c "CREATE USER tayseer WITH PASSWORD 'Tayseer@2026' CREATEDB; CREATE DATABASE tayseer_db OWNER tayseer;"
pnpm run start:dev
```

### Environment Variables
Copy `.env` file in `api/` directory. Required vars: DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD, DB_NAME, JWT_SECRET

### Testing API
```bash
# Register a new company (creates tenant + admin)
curl -X POST http://localhost:3000/api/v1/auth/register -H "Content-Type: application/json" -d '{"companyName":"Test Co","companySlug":"test","username":"admin","email":"admin@test.co","password":"admin123"}'

# Login
curl -X POST http://localhost:3000/api/v1/auth/login -H "Content-Type: application/json" -d '{"login":"admin@test.co","password":"admin123"}'
```

### Module Creation Pattern
When building new modules, follow this pattern:
1. Create entity in `src/modules/{name}/entities/` - extend BaseTenantEntity or add tenantId manually
2. Create DTOs in `src/modules/{name}/dto/` - use class-validator decorators
3. Create service in `src/modules/{name}/` - ALL queries must include tenantId filter
4. Create controller in `src/modules/{name}/` - extract tenantId from `req.user.tenantId`
5. Create module file and register in `app.module.ts`
6. Add Swagger decorators (@ApiTags, @ApiOperation, @ApiBearerAuth)
