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
| **Doctor Management** | Profiles, assignments, department linking, self-service profile updates |
| **Visit/OPD Workflow** | Registration → Vitals → Doctor → Tests → Prescription → Billing |
| **IPD Management** | Wards, beds, admissions, discharges |
| **Appointments** | Calendar scheduling, doctor availability |
| **Operation Theatre** | Theatre management, surgery scheduling with conflict detection, calendar view, status transitions (start, complete, postpone, cancel), surgical team assignment |

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
| **Employees** | Simplified profiles (name + joining date), user linking for auto-fill, documents |
| **Attendance** | Daily marking, monthly reports |
| **Leave Management** | Requests, approvals, balances, leave types CRUD |
| **Payroll** | Salary components, payslips, batch generation |
| **Shifts & Roster** | Shift definitions, auto-scheduling, swap requests |

### Reports

| Report | Description |
|--------|-------------|
| Daily Cash Register | Full cash flow with date range filter (opening → inflows → outflows → closing) |
| Patient Visits | Visit statistics by doctor, status, period |
| Revenue | Revenue breakdown by bill type |
| Outstanding Bills | Unpaid/partial bills |
| Investigation Report | Investigation order statistics (renamed from Lab Test Report) |
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
| **Subscriptions** | Paddle/PayFast integration, payment history tracking |
| **Custom Package Pricing** | "Contact Sales" for enterprise plans |

---

## Operation Theatre Module

### Features

- **Theatre Management** — CRUD for operation theatres (type, floor, equipment, status)
- **Surgery Scheduling** — Full form with patient, lead surgeon, procedure details, date/time
- **Conflict Detection** — Real-time AJAX check + server-side block for double-booking (emergency surgeries bypass)
- **OT Calendar** — FullCalendar integration with month/week/day views, theatre filter, status color-coding
- **Surgical Team** — Optional team assignment (assistant surgeon, anesthetist, nurse, technician)
- **Status Transitions** — Scheduled → Start → Complete / Cancel / Postpone
- **Postpone Action** — Record reason + optional new tentative date
- **Pre-Anaesthesia Checkup (PAC)** — Dedicated assessment form routed to the anaesthesia team; logs patient conditions (ASA grade, Mallampati class, vitals, medications, allergies) and provides medical clearance gate before surgery can start

### Surgery Statuses

| Status | Description |
|--------|-------------|
| `scheduled` | Booked for future date |
| `in_progress` | Surgery has started, OT marked as occupied |
| `completed` | Finished with post-op notes recorded |
| `postponed` | Delayed with reason, can be rescheduled |
| `cancelled` | Cancelled with mandatory reason |

### PAC (Pre-Anaesthesia Checkup) Workflow

1. Surgery is scheduled → PAC section shows "Not yet requested"
2. User clicks "Request PAC" → fills patient condition form (ASA grade, airway, cardiovascular, allergies, vitals, etc.)
3. Anaesthetist reviews the PAC → can **Clear**, **Not Clear**, or mark as **Needs Further Evaluation**
4. If cleared → surgery can be started normally
5. If not cleared / pending → "Start Surgery" is blocked with an error message
6. Emergency surgeries bypass the PAC clearance gate

### Surgical Safety Checklist (WHO 3-Phase)

Implements the WHO Surgical Safety Checklist with real-time AJAX toggling:

**Phase 1 — Sign In** (Before induction of anaesthesia):
- Patient identity confirmed
- Procedure & site marked
- Informed consent form signed
- Anaesthesia safety check complete
- Pulse oximeter functioning
- Known allergies reviewed
- Aspiration risk assessed
- Blood loss risk assessed

**Phase 2 — Time Out** (Before skin incision):
- Team introduced by name and role
- Patient/procedure/site confirmed
- Antibiotic prophylaxis given
- Critical events discussed (surgeon, anaesthesia, nursing)
- Essential imaging displayed

**Phase 3 — Sign Out** (Before patient leaves OR):
- Procedure name recorded
- Instrument/sponge/needle counts correct
- Specimen labelled
- Equipment problems documented
- Recovery plan communicated

**Enforcement:**
- Sign In phase must be complete before "Start Surgery" is allowed (elective only)
- Each phase must be confirmed sequentially (Sign In → Time Out → Sign Out)
- Phase confirmation is locked once marked done (checkboxes become read-only)
- Emergency surgeries bypass the checklist gate
- Permission: `manage surgical checklists` (assigned to Hospital Administrator, Nurse)

### OT Inventory & Consumable Tracking

Dedicated inventory system for surgical supplies, separate from the pharmacy module:

**Catalog:**
- Consumable categories: Instrument, Implant, Disposable, Suture, Drape, Other
- Reusable flag (instruments are logged but not stock-deducted)
- Serial tracking flag (implants require serial number for patient traceability)
- Reorder level per item — triggers alerts when stock falls below threshold

**Stock Management (FIFO):**
- Stock-in with batch number, expiry date, serial number (implants), supplier, PO reference
- FIFO consumption — oldest batch is consumed first when recording surgery usage
- Remaining quantity tracked per stock-in batch

**Per-Surgery Usage:**
- Record which consumables were used during each surgery
- Links to the specific FIFO batch for cost tracking
- Serial number capture for implants (patient-linked traceability)
- Reusable items are logged but not deducted from stock

**Reorder Alerts:**
- Dedicated reorder alerts page showing all items below reorder level
- Low stock count badge visible on the consumables index
- Quick "Stock In" action from the alert page

**Permission:** `manage ot consumables` (assigned to Hospital Administrator, Nurse)

### Sterilization & Audit Logs

Tracks and verifies sterilization of operation theatres and instruments for infection control:

**Target Types:**
- Operation Theatre — full room sterilization
- Instrument Set — tray/set level (e.g., "General Surgery Tray #2")
- Individual Instrument — single items from the OT inventory catalog

**Sterilization Methods:**
- Steam Autoclave (121°C/134°C)
- Chemical (Glutaraldehyde)
- Dry Heat
- Ethylene Oxide (EtO)
- Hydrogen Peroxide Plasma

**Process Flow:**
1. Schedule or start sterilization immediately
2. Record cycle number, temperature, duration
3. Complete with chemical & biological indicator results (pass/fail/pending)
4. If any indicator fails → status auto-set to "failed" (re-sterilization required)
5. If passed → dual sign-off verification (second person verifies — cannot be the same person who performed)

**Key Features:**
- Auto-generated log numbers (`STER-20260619-0001`)
- Dual sign-off enforcement (performer ≠ verifier)
- Chemical + biological indicator tracking
- Filter by status, method, or target type
- Failure documentation with mandatory reason
- Full audit trail (created_by, performed_by, verified_by with timestamps)

**Permission:** `manage sterilization` (assigned to Hospital Administrator, Nurse)

### Intra-operative & Post-operative Monitoring

Comprehensive digital surgical log covering anaesthesia through recovery:

**Anaesthesia Record:**
- Anaesthetist assignment, anaesthesia type (general, regional, local, sedation, combined)
- Airway management (ETT, LMA, facemask, tracheostomy) with tube size
- Drugs: induction agent/dose, maintenance agent, muscle relaxant, reversal agent
- Regional technique details (spinal level, epidural, nerve block)
- IV fluids, estimated blood loss, urine output
- Timing: induction, intubation, extubation timestamps
- Intra-op medications and events/complications
- Recovery status, post-op instructions, pain management plan
- Supports create + update (same form edits existing record)

**Intra-Operative Vitals (Time-Series):**
- Per-entry recording: systolic/diastolic BP, heart rate, SpO2, EtCO2, respiratory rate, temperature, MAC value, FiO2
- Time-stamped entries form a trend record
- Canvas-based chart visualization (HR, SpO2, systolic plotted over time)
- JSON API endpoint for chart data (`GET /ot/surgeries/{id}/vitals-data`)

**Post-Operative Monitoring:**
- Phase tracking: PACU (Recovery Room) vs Ward
- AVPU consciousness scale (Alert, Verbal, Pain, Unresponsive)
- Vitals: BP, HR, SpO2, respiratory rate, temperature
- Pain score (NRS 0-10), nausea/vomiting severity
- Wound status, drain output, IV fluids given, medications given
- Chronological history table with all entries

**Access:**
- Monitoring links appear on surgery detail page when status is `in_progress` or `completed`
- All three modules (anaesthesia, vitals, post-op) accessible from a single navigation card
- Uses existing OT permission (`view surgeries`) — no additional permission needed since it's part of the surgery workflow

---

## RBAC System

Uses Spatie Permission with per-tenant isolation:

- Permissions assigned to Roles (not directly to users)
- Roles assigned to Users
- Route middleware: `->middleware('permission:view patients|create patients')`
- Sidebar driven by `SidebarService` — checks user permissions via `$user->can()`
- Seeded via `RolePermissionSeeder` — single source of truth
- System Settings restricted via `manage settings` permission
- Tenant-scoped permission cache (fixes stale cache after sync)

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
- **Tenant-scoped Spatie cache** — `SyncTenantPermissions` clears `spatie.permission.cache.tenant.{id}` (not default key)
- **OT conflict detection** — time-overlap query: `existing_start < new_end AND existing_end > new_start`; emergency surgeries bypass the hard block
- **Doctor profile self-service** — Doctors can update professional fields (specialization, qualification, schedule) from their profile page
- **Permission-gated Settings** — System settings accessible only to users with `manage settings` permission (default: Hospital Administrator, Receptionist)

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
| `php artisan tenants:sync-permissions` | Re-seed permissions on all tenants (clears tenant-scoped cache) |
| `php artisan pharmacy:alert-near-expiry` | Send near-expiry medicine alerts |
| `php artisan cache:clear` | Clear all cached data |
| `php artisan config:clear` | Clear config cache |
| `php artisan migrate --database=tenant` | Run tenant migrations |

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
