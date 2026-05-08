# Implementation Plan: Doctor Share UI

## Overview

Build the complete Doctor Share UI module on top of the existing `DoctorShareService` backend. The implementation proceeds in six incremental phases: database + model, permissions + routing, controller skeleton, views (rules → items → settlements → reports), sidebar integration, and a final wiring checkpoint. Each phase produces working, testable code before the next begins.

All views extend `admin.layout`, use Tailwind with `medical-blue`/`medical-light`/`medical-green` tokens, and follow the card/table/badge/flash patterns from `resources/views/admin/bills/`.

---

## Tasks

- [x] 1. Create `doctor_share_settlements` migration and `DoctorShareSettlement` model
  - [x] 1.1 Write the migration `database/migrations/tenant/2026_05_05_000003_create_doctor_share_settlements_table.php`
    - Columns: `id`, `doctor_id` (nullable FK → doctors, nullOnDelete), `date_from` (date), `date_to` (date), `item_count` (unsignedInteger), `total_settled_amount` (decimal 10,2), `created_by` (FK → users), `notes` (text nullable), `timestamps()`
    - Index on `doctor_id` for per-doctor settlement queries
    - Index on `created_at` for date-range listing
    - _Requirements: 6.1, 6.4_

  - [x] 1.2 Create `app/Models/DoctorShareSettlement.php`
    - `use UsesTenantConnection`
    - `$fillable`: all columns listed in 1.1
    - `$casts`: `total_settled_amount` → `decimal:2`, `date_from`/`date_to` → `date`
    - `doctor()` belongsTo Doctor
    - `createdBy()` belongsTo User (`created_by`)
    - `shareItems()` hasMany DoctorShareItem (`settlement_id`)
    - _Requirements: 6.4_

  - [x] 1.3 Add `settlement_id` FK constraint to `doctor_share_items` via a new migration `2026_05_05_000004_add_settlement_fk_to_doctor_share_items.php`
    - Add foreign key: `settlement_id` → `doctor_share_settlements.id` nullOnDelete
    - Add `collected_at_settlement` decimal(10,2) nullable column (stores `SUM(allocations.amount)` captured at settlement time)
    - _Requirements: 6.4_

- [x] 2. Add `manage doctor shares` permission and update `RolePermissionSeeder`
  - [x] 2.1 Add `'manage doctor shares'` to the `$permissions` array in `database/seeders/RolePermissionSeeder.php`
    - Place it in the Billing Management block alongside existing billing permissions
    - Assign to `Super Admin` (already gets `Permission::all()`) — no change needed
    - Add `'manage doctor shares'` to the Hospital Administrator `givePermissionTo()` call
    - _Requirements: 9.1, 9.2_

- [x] 3. Register routes in `routes/web.php`
  - [x] 3.1 Add a named route group `doctor-share.*` inside the existing tenant-authenticated middleware group
    - Apply `permission:manage doctor shares` middleware to the entire group
    - Rules resource routes: `index`, `create`, `store`, `edit`, `update`, `destroy` + a `PATCH doctor-share/rules/{rule}/toggle` → `DoctorShareController@toggleRule` named `doctor-share.rules.toggle`
    - Items: `GET doctor-share/items` → `DoctorShareController@itemsIndex` named `doctor-share.items.index`
    - Settlements: `GET doctor-share/settlements` → `settlementsIndex`, `GET doctor-share/settlements/preview` → `settlementsPreview`, `POST doctor-share/settlements` → `settlementsStore`, `GET doctor-share/settlements/{settlement}` → `settlementsShow`
    - Reports: `GET doctor-share/reports` → `reportsIndex`, `GET doctor-share/reports/print` → `reportsPrint`
    - _Requirements: 1.1, 2.1, 3.1, 4.1, 5.1, 6.1, 7.1, 9.1_

- [x] 4. Implement `DoctorShareController` — rules methods
  - [x] 4.1 Create `app/Http/Controllers/DoctorShareController.php` with `rulesIndex()` method
    - Eager-load `doctor`, `service`, `investigation`
    - Accept `doctor_id` and `status` query filters
    - Paginate at 20 per page
    - Pass `$doctors` list for the filter dropdown
    - Return `admin.doctor-share.rules.index`
    - _Requirements: 1.1, 1.2, 1.3_

  - [x] 4.2 Add `rulesCreate()` and `rulesStore()` methods
    - `rulesCreate()`: pass `$doctors`, `$services`, `$investigations` to `admin.doctor-share.rules.create`
    - `rulesStore()`: validate (doctor_id nullable exists, service_id nullable exists, investigation_id nullable exists, share_type in [percentage,fixed], share_value numeric, applies_to in [opd,ipd,investigation,emergency,all])
    - Percentage range 0.01–100.00; fixed > 0 (Req 2.4, 2.5)
    - Unique combination check for `[doctor_id, service_id, investigation_id, applies_to]` (Req 2.3)
    - Set `created_by` = `auth()->id()`
    - Write audit log entry (Req 9.3)
    - Redirect to `doctor-share.rules.index` with success flash
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.8, 9.3_

  - [x] 4.3 Add `rulesEdit()` and `rulesUpdate()` methods
    - `rulesEdit()`: load rule with relationships; pass `$doctors`, `$services`, `$investigations`; return `admin.doctor-share.rules.edit`
    - `rulesUpdate()`: same validation as store; unique check excludes current rule ID (Req 3.5)
    - Write audit log entry (Req 9.3)
    - Redirect with success flash
    - _Requirements: 3.1, 3.2, 3.4, 3.5, 9.3_

  - [x] 4.4 Add `rulesDestroy()` and `toggleRule()` methods
    - `rulesDestroy()`: block deletion if `shareItems()->exists()` — return back with error (Req 4.3); otherwise delete and redirect with success flash; write audit log (Req 9.3)
    - `toggleRule()`: flip `is_active`; write audit log; return JSON `{active: bool}` for AJAX or redirect for non-JS
    - _Requirements: 4.1, 4.2, 4.3, 9.3_

- [x] 5. Implement `DoctorShareController` — items, settlements, reports methods
  - [x] 5.1 Add `itemsIndex()` method
    - Query `DoctorShareItem` with `doctor`, `bill`, `billItem` eager-loaded
    - Accept `doctor_id`, `status`, `date_from`, `date_to` filters
    - Compute `collected_amount` as `SUM(allocations.amount)` via a subquery or `withSum`
    - Paginate at 25 per page; pass summary totals (total share, total collected, total pending) for the current filter set
    - Return `admin.doctor-share.items.index`
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_

  - [x] 5.2 Add `settlementsIndex()`, `settlementsPreview()`, `settlementsStore()`, `settlementsShow()` methods
    - `settlementsIndex()`: list `DoctorShareSettlement` records with `doctor`, `createdBy`; paginate 20; return `admin.doctor-share.settlements.index`
    - `settlementsPreview()`: accept `doctor_id` (nullable) and `date_from`/`date_to`; query eligible items (status=pending, settlement_id IS NULL, has ≥1 allocation, created_at in range); pass preview data to `admin.doctor-share.settlements.preview`; if no eligible items pass empty flag (Req 6.8)
    - `settlementsStore()`: validate inputs; wrap in `DB::connection('tenant')->transaction()`: insert `DoctorShareSettlement`, bulk-update eligible items to settled with `settlement_id` and `collected_at_settlement = SUM(allocations.amount)`; on failure roll back and return error (Req 6.5); on success redirect with flash showing count + total (Req 6.6); write audit log (Req 9.4)
    - `settlementsShow()`: load settlement with `shareItems.doctor`, `shareItems.bill`; return `admin.doctor-share.settlements.show`
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8, 9.4_

  - [x] 5.3 Add `reportsIndex()` and `reportsPrint()` methods
    - `reportsIndex()`: accept `doctor_id`, `date_from`, `date_to`, `bill_type` filters; build summary grouped by doctor (total earned, collected, pending, settled); build detail list of matching share items; pass both to `admin.doctor-share.reports.index`
    - `reportsPrint()`: accept same filters via query string; load same data; return `admin.doctor-share.reports.print` (no layout)
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5, 7.6_

- [x] 6. Build Share Rules views
  - [x] 6.1 Create `resources/views/admin/doctor-share/rules/index.blade.php`
    - `@extends('admin.layout')`, `@section('content')`
    - Page header with "Share Rules" title and "Create Rule" button (`bg-medical-blue`)
    - Session flash alert block (success/error) matching billing views pattern
    - Filter bar: doctor dropdown + status dropdown + submit button
    - `bg-white rounded-lg shadow-sm` card wrapping the table
    - `thead bg-gray-50` with columns: Rule Level, Doctor, Scope (Service/Investigation), Share Type, Value, Applies To, Status, Actions
    - Rule Level badge: "Global" (`bg-purple-100 text-purple-800`), "Doctor Default" (`bg-blue-100 text-blue-800`), "Specific" (`bg-green-100 text-green-800`)
    - Status badge: active (`bg-green-100 text-green-800`), inactive (`bg-gray-100 text-gray-800`) — pill pattern `text-xs px-2 py-1 rounded`
    - Actions: Edit link, toggle active/inactive button (AJAX-friendly), Delete form with confirm
    - Empty state message with "Create your first rule" prompt when no results (Req 1.6)
    - Pagination links
    - _Requirements: 1.1–1.6, 4.4, 10.1–10.6_

  - [x] 6.2 Create `resources/views/admin/doctor-share/rules/create.blade.php`
    - `@extends('admin.layout')`, `@section('content')`
    - `bg-white rounded-lg shadow-sm` form card
    - Fields: Doctor (select, nullable — "Global Default" option), Service (select, nullable), Investigation (select, nullable), Share Type (radio: percentage / fixed), Share Value (number input), Applies To (select: all/opd/ipd/investigation/emergency), Notes (textarea)
    - Inline validation error display using `$errors`
    - Submit button (`bg-medical-blue`) + Cancel link back to index
    - _Requirements: 2.1, 2.4, 2.5, 10.1–10.6_

  - [x] 6.3 Create `resources/views/admin/doctor-share/rules/edit.blade.php`
    - Same structure as create; pre-populate all fields with `old()` / model values
    - If rule has pending share items, show informational notice: "Existing pending items retain their original rule snapshot and will not be recalculated." (Req 3.3)
    - _Requirements: 3.1, 3.3, 10.1–10.6_

- [x] 7. Build Share Items view
  - [x] 7.1 Create `resources/views/admin/doctor-share/items/index.blade.php`
    - `@extends('admin.layout')`, `@section('content')`
    - Page header "Share Item Ledger"
    - Filter bar: doctor dropdown, status dropdown (pending/settled/voided), date-from, date-to inputs, submit
    - Summary stats row (3 cards): Total Share Amount, Total Collected, Total Pending — `bg-white rounded-lg shadow-sm p-4`
    - Main table card (`bg-white rounded-lg shadow-sm`), `thead bg-gray-50`
    - Columns: Doctor, Bill #, Bill Date, Base Amount, Share Amount, Collected, Status badge, Expand toggle
    - Status badges: pending (`bg-yellow-100 text-yellow-800`), settled (`bg-green-100 text-green-800`), voided (`bg-red-100 text-red-800`)
    - Expandable inline detail panel per row (Alpine.js `x-show` or `<details>`) listing allocations: Payment Date, Payment Amount, Allocated Share Amount; if voided show void reason + voided_at (Req 5.7, 5.8)
    - Pagination links
    - _Requirements: 5.1–5.8, 10.1–10.6_

- [x] 8. Build Settlement views
  - [x] 8.1 Create `resources/views/admin/doctor-share/settlements/index.blade.php`
    - `@extends('admin.layout')`, `@section('content')`
    - Page header "Settlement Batches" + "New Settlement" button
    - Session flash alert block
    - Table of past batches: Batch Date, Doctor Scope, Item Count, Total Settled Amount, Created By, Actions (View link)
    - Pagination links
    - _Requirements: 6.1, 6.7, 10.1–10.6_

  - [x] 8.2 Create `resources/views/admin/doctor-share/settlements/preview.blade.php`
    - `@extends('admin.layout')`, `@section('content')`
    - Filter form at top: Doctor (select, "All Doctors" option), Date From, Date To
    - If no eligible items: informational alert "No eligible pending items found for the selected scope and date range." (Req 6.8)
    - If items exist: preview table listing eligible Share_Items (Doctor, Bill #, Share Amount, Collected Amount), summary totals
    - Confirm Settlement form (`POST doctor-share/settlements`) with hidden inputs for scope + date range
    - Cancel link back to settlements index
    - _Requirements: 6.2, 6.3, 6.8, 10.1–10.6_

  - [x] 8.3 Create `resources/views/admin/doctor-share/settlements/show.blade.php`
    - `@extends('admin.layout')`, `@section('content')`
    - Settlement header card: batch date range, doctor scope, item count, total settled amount, created by
    - Table of included Share_Items: Doctor, Bill #, Share Amount, Collected at Settlement
    - Back link to settlements index
    - _Requirements: 6.7, 10.1–10.6_

- [x] 9. Build Report views
  - [x] 9.1 Create `resources/views/admin/doctor-share/reports/index.blade.php`
    - `@extends('admin.layout')`, `@section('content')`
    - Page header "Doctor Share Reports" + "Print Report" button (links to `doctor-share.reports.print` with current query string)
    - Filter bar: Doctor, Date From, Date To, Bill Type (all/opd/ipd/investigation/emergency)
    - Summary table grouped by doctor: Doctor Name, Total Earned, Total Collected, Total Pending, Total Settled — `thead bg-gray-50`
    - Detail section below: same columns as Items_View (Req 5.5) for all matching items
    - _Requirements: 7.1–7.6, 10.1–10.6_

  - [x] 9.2 Create `resources/views/admin/doctor-share/reports/print.blade.php`
    - Standalone HTML — no `@extends`, no sidebar, no nav chrome
    - Hospital name/logo from `setting()` helper at top
    - Report title, filter summary (doctor, date range, bill type)
    - Summary table + detail table matching the index view data
    - Print-trigger `<script>window.print()</script>` at bottom
    - Minimal Tailwind prose styles only (no interactive components)
    - _Requirements: 7.5, 10.1_

- [x] 10. Add Doctor Share submenu to sidebar
  - [x] 10.1 Edit `resources/views/partials/sidebar.blade.php`
    - Add a new submenu group after the Billing section (before Accounting) using the same collapsible pattern as existing submenus
    - Guard with `@can('manage doctor shares')`
    - Group header: `fas fa-hand-holding-usd` icon, label "Doctor Share", chevron toggle, `id="doctor-share-icon"`, `onclick="toggleSubmenu('doctor-share')"`
    - Active state: expand when `request()->routeIs('doctor-share.*')`
    - Four submenu links: "Share Rules" → `doctor-share.rules.index`, "Share Items" → `doctor-share.items.index`, "Settlements" → `doctor-share.settlements.index`, "Share Reports" → `doctor-share.reports.index`
    - Each link uses `pl-8 text-sm` indented style with appropriate Font Awesome icon; active class `bg-medical-light text-medical-blue` when route matches
    - _Requirements: 8.1, 8.2, 8.3, 8.4_

- [ ] 11. Final checkpoint — wire everything together and verify
  - Ensure all routes resolve without 404 (run `php artisan route:list | grep doctor-share`)
  - Ensure migration runs cleanly (`php artisan migrate --path=database/migrations/tenant`)
  - Ensure `RolePermissionSeeder` can be re-run without duplicate-key errors (use `firstOrCreate` if needed)
  - Ensure all Blade views compile without errors (`php artisan view:cache`)
  - Ensure all tests pass, ask the user if questions arise.

---

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP delivery
- Each task references specific requirements for traceability
- The settlement transaction (Task 5.2) must be atomic — partial settlement is a data-integrity failure
- The print view (Task 9.2) intentionally has no layout — it is a standalone document
- `collected_at_settlement` captures the SUM at the moment of settlement; it does not change if later allocations arrive
- All controller methods should use `abort(403)` or the `permission` middleware — never rely on view-level guards alone
