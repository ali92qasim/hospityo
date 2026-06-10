# Hospityo — Cloud-Based Hospital Management System

A multi-tenant SaaS hospital management platform built with Laravel, designed for clinics and hospital networks in Pakistan.

---

## Overview

Hospityo enables healthcare facilities to manage their entire operation from a single platform — patient records, billing, pharmacy, laboratory, HR, and accounting. Each tenant (hospital/clinic) gets a dedicated subdomain with isolated data.

---

## Architecture

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 11 |
| Multi-tenancy | Spatie Laravel Multitenancy (per-tenant DB) |
| RBAC | Spatie Laravel Permission |
| Frontend | Blade + Tailwind CSS + Vite |
| Database | MySQL (production) / SQLite (local dev) |
| Queue | Database driver (landlord DB) |
| Cache | Database driver |
| Session | File (production-ready for single server) |

### Database Connections

- **Landlord** — central DB: `tenants`, `plans`, `subscriptions`, `super_admins`, `site_settings`, `jobs`
- **Tenant** — per-tenant DB: `patients`, `doctors`, `bills`, `visits`, `medicines`, etc.

---

## Modules

### Core Clinical

| Module | Description |
|--------|-------------|
| **Patient Management** | Demographics, history, search by phone |
| **Doctor Management** | Profiles, assignments, department linking |
| **Visit/OPD Workflow** | Registration → Vitals → Doctor → Tests → Prescription → Billing |
| **IPD Management** | Wards, beds, admissions, discharges |
| **Appointments** | Calendar scheduling, doctor availability |

### Diagnostics & Laboratory

| Module | Description |
|--------|-------------|
| **Investigations** | Test definitions with parameters and reference ranges |
| **Investigation Orders** | Order tests, collect samples, track status |
| **Lab Results** | Enter results, verify, generate reports |
| **Radiology** | Imaging results and reports |
| **CSV Import** | Bulk upload investigations from CSV/Excel |

### Pharmacy & Inventory

| Module | Description |
|--------|-------------|
| **Medicines** | Catalog with SKU, strength, dosage forms |
| **Inventory (FIFO)** | Stock-in with batch/expiry, FIFO dispensing |
| **Prescriptions** | Doctor prescribes, pharmacist dispenses |
| **Near-Expiry Alerts** | Dashboard banner + email alerts for 6-month threshold |
| **Suppliers & Purchases** | PO management with receiving workflow |

### Billing & Accounting

| Module | Description |
|--------|-------------|
| **Bills** | OPD/IPD/Investigation/Pharmacy billing with line items |
| **Payments** | Cash, card, insurance, bank transfer tracking |
| **Tax Configuration** | Configurable tax rules per bill type |
| **Chart of Accounts** | Full double-entry chart with parent/child hierarchy |
| **Journal Entries** | Manual and auto-generated (bills, payments, purchases) |
| **Deposit & Transfer** | Quick forms for cash movements between accounts |
| **General Ledger** | Per-account transaction view with date range |
| **Profit & Loss** | Revenue vs expenses by period |
| **Balance Sheet** | Assets, liabilities, equity at a point in time |
| **Daily Cash Register** | Full cash flow: opening balance → inflows → outflows → closing |

### Doctor Share

| Module | Description |
|--------|-------------|
| **Share Rules** | Percentage or fixed share per doctor, per bill type |
| **Share Items** | Auto-calculated when bills are created |
| **Allocations** | Payment collection tracking per share item |
| **Settlements** | Batch payouts to doctors |

### HR & Payroll

| Module | Description |
|--------|-------------|
| **Employees** | Profiles, documents, department assignment |
| **Attendance** | Daily marking, monthly reports |
| **Leave Management** | Requests, approvals, balances |
| **Payroll** | Salary components, payslips, batch generation |
| **Shifts & Roster** | Shift definitions, auto-scheduling, swap requests |

### Reports

| Report | Description |
|--------|-------------|
| Daily Cash Register | Cash flow with inflows/outflows |
| Patient Visits | Visit statistics by doctor, status, period |
| Revenue | Revenue breakdown by bill type |
| Outstanding Bills | Unpaid/partial bills |
| Lab Test Report | Investigation order statistics |
| Medicine Sales | Dispensing report |
| Inventory Status | Current stock levels |
| Expiry Report | Near-expiry medicines |
| Doctor Performance | Patient count, revenue per doctor |
| Appointment Statistics | Scheduling metrics |
| IPD Report | Admissions, discharges, bed occupancy |
| Department Performance | Per-department statistics |
| Patient Demographics | Age, gender, location breakdown |

### SaaS / Admin Panel

| Feature | Description |
|---------|-------------|
| **Super Admin** | Tenant management, plan management, site settings |
| **Plans** | Custom pricing flag, module-based feature gating |
| **Tenant Registration** | 3-step wizard, auto-provisioning with queue |
| **Landing Page** | DB-driven pricing cards, "Most Popular" badge |
| **Subscriptions** | Paddle/PayFast integration |
| **Custom Package Pricing** | "Contact Sales" for enterprise plans |

---

## RBAC System

Uses Spatie Permission with per-tenant isolation:

- Permissions assigned to Roles (not directly to users)
- Roles assigned to Users
- Route middleware: `->middleware('permission:view patients|create patients')`
- Sidebar driven by `SidebarService` — checks user permissions via `$user->can()`
- Seeded via `RolePermissionSeeder` — single source of truth

Default roles: Super Admin, Hospital Administrator, Doctor, Nurse, Receptionist, Lab Technician, Pharmacist

---

## Key Technical Decisions

- **Settings stored in DB** (not cache) — `Setting` model with `get()`/`set()` methods
- **FIFO inventory** — `remaining_quantity` on stock_in transactions, batch consumption on dispense
- **Doctor Share** uses bcmath (scale 6) — no float arithmetic in financial calculations
- **Bill number** uses `MAX(sequence)` not `COUNT()` — prevents duplicates on deletion
- **Signed URLs** for public lab report sharing via WhatsApp (72-hour expiry)
- **Duplicate journal entry prevention** — checks `reference_type + reference_id` before creating
- **Service import** accepts both CSV and Excel (PhpSpreadsheet) — handles user accidentally saving as .xlsx

---

## Local Development Setup

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+ or SQLite
- Laravel Herd (recommended for Windows)

### Installation

```bash
git clone <repo-url>
cd saasy
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### Database Setup

For SQLite (local dev):
```env
DB_LANDLORD_DRIVER=sqlite
DB_TENANT_DRIVER=sqlite
```

For MySQL:
```env
DB_LANDLORD_DRIVER=mysql
DB_HOST=127.0.0.1
DB_DATABASE=saasy
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations:
```bash
php artisan migrate --path=database/migrations/landlord
```

### Build Assets

```bash
npm run dev   # development with hot reload
npm run build # production build
```

---

## Deployment

### Server Requirements

- PHP 8.2+ with extensions: BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML, GD
- MySQL 8.0+
- Composer
- Node.js (for asset building)
- Supervisor (for queue worker)
- Cron (for scheduler)

### Queue Worker

```bash
php artisan queue:work --queue=default --sleep=3 --tries=3
```

### Scheduler

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### After Deploying Permission Changes

```bash
php artisan tenants:sync-permissions
php artisan cache:clear
```

---

## Important Commands

| Command | Purpose |
|---------|---------|
| `php artisan tenants:sync-permissions` | Re-seed permissions on all tenants |
| `php artisan pharmacy:alert-near-expiry` | Send near-expiry medicine alerts |
| `php artisan cache:clear` | Clear all cached data |
| `php artisan config:clear` | Clear config cache |

---

## Project Structure

```
app/
├── Console/Commands/     # Artisan commands
├── Exceptions/           # Custom exception handlers
├── Helpers/              # Global helper functions (setting(), format_currency(), etc.)
├── Http/
│   ├── Controllers/      # All controllers (tenant + super-admin)
│   ├── Middleware/        # CheckModule, EnsureTenantActive, etc.
│   └── Requests/         # Form request validation
├── Jobs/Tenant/          # Queue jobs (imports, provisioning)
├── Models/               # Eloquent models
├── Notifications/        # Email notifications
├── Services/             # Business logic (AccountingService, DoctorShareService, etc.)
└── Traits/               # Reusable traits (Auditable)

database/
├── migrations/landlord/  # Central DB migrations
├── migrations/tenant/    # Per-tenant DB migrations
└── seeders/              # Role/permission seeders, reference data

resources/views/
├── admin/                # Tenant admin panel views
├── super-admin/          # Super admin panel views
├── partials/             # Sidebar, header
├── landing.blade.php     # Public landing page
└── tenant/               # Registration wizard

docs/                     # Feature documentation and bug reports
```

---

## License

Proprietary. All rights reserved.
