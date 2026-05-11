# Requirements: Diagnostics & Billing Fixes

## Overview

Four independent bug areas identified through code analysis. All fixes must maintain
consistency between the two investigation order entry points (visit workflow and
diagnostics module), preserve existing data, and follow the project's existing
patterns (Blade views, Eloquent, no new packages).

---

## 1. Investigation Orders — Status Not Updating After Results

### Problem
After saving a lab result, `InvestigationOrderItem.status` and `InvestigationOrder.status`
remain `ordered`/`testing` on the investigation orders index page. The result is saved
but the status propagation is incomplete in the `storeBatch` path.

### Root Cause (confirmed)
`storeBatch` in `LabResultController` only updates the **first** pending pathology item
per order, not each item that corresponds to a submitted result entry. The
`InvestigationOrderItem.result` relationship uses `investigation_order_id` as the FK
(pointing to the parent order), not the item's own id — so `whereDoesntHave('result')`
on items always returns true even after a result exists.

### Requirements
- R1.1 Each submitted result entry in `storeBatch` must update the correct item's status to `reported`.
- R1.2 The `InvestigationOrderItem.result` relationship must correctly identify whether a result exists for that specific item (not just the parent order).
- R1.3 After all items on an order are `reported`, the parent `InvestigationOrder.status` must be set to `reported` and `completed_at` populated.
- R1.4 The investigation orders index page must reflect the updated status immediately after redirect.
- R1.5 Status updates must behave identically whether the order was created from the visit workflow or the diagnostics module.

---

## 2. Lab Results — Parameter Values Not Persisting / No Print Link

### Problem
- Result parameter values appear not to save (success message shows but values are missing on reload).
- No print/report link is visible after saving results.

### Root Cause (confirmed)
- `LabResultItem` stores `lab_result_id` as FK, but `LabResultController::store` calls
  `$result->resultItems()->create(...)` which uses the `LabResult` → `LabResultItem`
  relationship. The `resultItems` relationship on `InvestigationOrder` incorrectly uses
  `lab_result_id` as the FK pointing to `investigation_order_id` — this is a wrong
  relationship definition causing items to be orphaned.
- The report view (`lab/results/report.blade.php`) references `$labResult->labOrder`
  which is a valid alias, but the route `lab-results.report` is not linked from the
  results index completed-results table actions.

### Requirements
- R2.1 `LabResultItem` records must be correctly associated with their parent `LabResult` via `lab_result_id`.
- R2.2 The completed results table on the results index page must include a "Print Report" link for each result.
- R2.3 The report view must load without errors using the correct relationship chain.
- R2.4 The report must be accessible from both the results index and the investigation orders show page.

---

## 3. Doctor Login — "User Not Found" After Central Login Change

### Problem
Doctors created via the Doctors module cannot log in through the central login page.
The error "No account found with this email address" is shown.

### Root Cause (confirmed)
`CentralLoginController` looks up the user in the `tenant_users` (landlord DB) table via
`TenantUser::findTenantByEmail()`. `TenantUser::register()` is called in:
- `UserController::store()` ✓
- `TenantProvisioningService` (admin only) ✓
- `SeedTenantData` job (admin only) ✓

But **not** in:
- `DoctorController::store()` ✗
- `EmployeeController::store()` ✗ (if it creates users)

Any user created outside `UserController` is invisible to the central login.

### Requirements
- R3.1 When a Doctor record is created with a new user account, the email must be registered in `tenant_users`.
- R3.2 When a Doctor's email is updated, the `tenant_users` entry must be updated accordingly.
- R3.3 The same fix must apply to any other controller that creates user accounts (Employee, etc.).
- R3.4 A backfill command (`BackfillTenantUsers`) already exists and must remain functional for existing records.
- R3.5 No change to the central login flow itself — only the registration side needs fixing.

---

## 4. Billing — Percentage Discount & Cash Register Report

### Problem
- Billing only supports a fixed-amount discount. Percentage-based discount is not available.
- The daily cash register report does not show total discount given.

### Requirements

#### 4a. Percentage Discount
- R4.1 The bill creation form must offer two discount modes: **Fixed Amount** and **Percentage**.
- R4.2 When percentage mode is selected, entering a percentage (0–100) must auto-calculate and display the discount amount.
- R4.3 The calculated `discount_amount` (always stored as a monetary value) must be saved to the existing `bills.discount_amount` column — no schema change required.
- R4.4 The discount type/percentage used must be stored for display purposes. Add a `discount_type` (`fixed`|`percentage`) and `discount_percentage` (decimal 5,2) column to `bills` via a new migration.
- R4.5 The bill show/print view must display the discount type and value clearly.

#### 4b. Cash Register Report
- R4.6 The daily cash register summary must include a **Total Discount** card showing the sum of `discount_amount` for bills on that date.
- R4.7 The bills table in the report must show the discount column per bill.
- R4.8 The `ReportController::dailyCashRegister()` must include `total_discount` in the `$summary` array.

---

## 5. Import Toast — Persistence Across Navigation

### Problem
The import loading toast disappears when the user navigates away from the investigations
index page because the polling script is inline in that page and the cache key is stored
only in the server-side session (lost on redirect).

### Requirements
- R5.1 The import cache key must be persisted in `localStorage` so polling survives page navigation.
- R5.2 On any page load within the admin panel, if a pending import key exists in `localStorage`, the loading toast must resume polling.
- R5.3 When the import completes and the user is on the investigations index page, the page must reload to show new rows, then display the success toast.
- R5.4 When the import completes and the user is on a different page, only the success toast must be shown (no redirect).
- R5.5 The cache key must be cleared from `localStorage` once the result has been consumed.
