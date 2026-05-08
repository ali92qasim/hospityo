# Requirements Document

## Introduction

The Doctor Share UI module provides the complete frontend for managing doctor revenue sharing within the Hospital Management System (HMS). The backend service (`DoctorShareService`) is already fully implemented and automatically calculates, voids, and records payment allocations. This feature adds the missing user interface so administrators can configure share rules, monitor earned share items per doctor, run settlement batches, and view share history — all within the existing Tailwind CSS design system using `medical-blue`, `medical-light`, and `medical-green` color classes and the same card/table/form patterns as the billing and doctor management views.

The module covers four functional areas:
1. **Share Rule Management** — create, edit, deactivate, and delete the three-level rule hierarchy (global default → doctor default → doctor+service/investigation specific).
2. **Share Item Ledger** — view pending, settled, and voided share items per doctor or across all doctors, with drill-down to allocation events.
3. **Settlement Batches** — run a settlement batch for one or all doctors, marking eligible pending items as settled and recording the batch for audit.
4. **Share Reports** — summary and detail reports filterable by doctor, date range, and status.

---

## Glossary

- **Share_Rule**: A `DoctorShareRule` record that defines how much a doctor earns (percentage or fixed amount) for a given bill type, optionally scoped to a specific service or investigation.
- **Share_Item**: A `DoctorShareItem` record representing the earned share liability for one bill line item. Status lifecycle: `pending` → `settled` or `voided`.
- **Share_Allocation**: A `DoctorShareAllocation` record — an immutable event in the collection ledger. One row per payment that allocates collected cash toward a share item.
- **Settlement_Batch**: A logical grouping of share items that are marked `settled` together in one operation. Identified by a `settlement_id` on the share items.
- **Collected_Amount**: The sum of all `Share_Allocation.amount` values for a given `Share_Item`. Derived at query time; never stored as a mutable column.
- **Rule_Level**: The specificity tier of a Share_Rule. Level 1 = doctor + service/investigation (most specific). Level 2 = doctor only (doctor default). Level 3 = no doctor, no service, no investigation (global default).
- **Share_Controller**: The new `DoctorShareController` that handles all HTTP requests for the Doctor Share UI.
- **Rules_View**: The Blade view listing all Share_Rules with filter and CRUD actions.
- **Items_View**: The Blade view listing Share_Items with status filter, doctor filter, and date range filter.
- **Settlement_View**: The Blade view for initiating and reviewing Settlement_Batches.
- **Report_View**: The Blade view for share summary and detail reports.
- **Admin_User**: An authenticated user with the `manage doctor shares` permission.

---

## Requirements

### Requirement 1: Share Rule List

**User Story:** As an Admin_User, I want to view all doctor share rules in a filterable table, so that I can understand the current rule configuration at a glance.

#### Acceptance Criteria

1. THE Share_Controller SHALL render the Rules_View at route `doctor-share/rules` with all Share_Rules paginated at 20 per page.
2. WHEN the Admin_User filters by doctor, THE Rules_View SHALL display only Share_Rules where `doctor_id` matches the selected doctor.
3. WHEN the Admin_User filters by status (active / inactive), THE Rules_View SHALL display only Share_Rules where `is_active` matches the selected value.
4. THE Rules_View SHALL display each Share_Rule's rule level (Global Default, Doctor Default, or Doctor + Service/Investigation), share type, share value, applies-to scope, and active status.
5. THE Rules_View SHALL display a badge indicating Rule_Level: "Global" for Level 3, "Doctor Default" for Level 2, and "Specific" for Level 1.
6. IF no Share_Rules match the current filter, THEN THE Rules_View SHALL display an empty-state message with a prompt to create the first rule.

---

### Requirement 2: Create Share Rule

**User Story:** As an Admin_User, I want to create a new doctor share rule, so that the system can automatically calculate the correct share amount when bills are generated.

#### Acceptance Criteria

1. THE Share_Controller SHALL render a create form at route `doctor-share/rules/create` with doctor, service, investigation, share type, share value, applies-to, and notes fields.
2. WHEN the Admin_User submits a valid create form, THE Share_Controller SHALL persist the Share_Rule and redirect to the Rules_View with a success flash message.
3. IF the Admin_User submits a create form where the combination of `doctor_id`, `service_id`, `investigation_id`, and `applies_to` already exists, THEN THE Share_Controller SHALL return a validation error stating the rule combination already exists.
4. IF the Admin_User submits a create form with `share_type` = `percentage` and `share_value` outside the range 0.01–100.00, THEN THE Share_Controller SHALL return a validation error.
5. IF the Admin_User submits a create form with `share_type` = `fixed` and `share_value` less than or equal to 0, THEN THE Share_Controller SHALL return a validation error.
6. WHEN the Admin_User selects a doctor but leaves service and investigation blank, THE Rules_View SHALL label the resulting rule as "Doctor Default".
7. WHEN the Admin_User leaves doctor, service, and investigation all blank, THE Rules_View SHALL label the resulting rule as "Global Default".
8. THE Share_Controller SHALL set `created_by` to the authenticated user's ID on every new Share_Rule.

---

### Requirement 3: Edit Share Rule

**User Story:** As an Admin_User, I want to edit an existing share rule, so that I can correct the share percentage or fixed amount without deleting and recreating the rule.

#### Acceptance Criteria

1. THE Share_Controller SHALL render an edit form at route `doctor-share/rules/{rule}/edit` pre-populated with the existing Share_Rule values.
2. WHEN the Admin_User submits a valid edit form, THE Share_Controller SHALL update the Share_Rule and redirect to the Rules_View with a success flash message.
3. IF the Admin_User edits a Share_Rule that has associated pending Share_Items, THEN THE Rules_View SHALL display an informational notice that existing pending items will retain their original rule snapshot and will not be recalculated.
4. THE Share_Controller SHALL apply the same validation rules as Requirement 2 acceptance criteria 4 and 5 on edit submissions.
5. IF the Admin_User submits an edit form where the updated combination of `doctor_id`, `service_id`, `investigation_id`, and `applies_to` conflicts with a different existing Share_Rule, THEN THE Share_Controller SHALL return a validation error.

---

### Requirement 4: Deactivate and Delete Share Rule

**User Story:** As an Admin_User, I want to deactivate or delete a share rule, so that I can stop it from applying to new bills without losing historical data.

#### Acceptance Criteria

1. WHEN the Admin_User toggles a Share_Rule to inactive, THE Share_Controller SHALL set `is_active` = false and return a success response without deleting the record.
2. WHEN the Admin_User requests deletion of a Share_Rule that has no associated Share_Items, THE Share_Controller SHALL delete the record and redirect to the Rules_View with a success flash message.
3. IF the Admin_User requests deletion of a Share_Rule that has one or more associated Share_Items, THEN THE Share_Controller SHALL reject the deletion and display an error message stating the rule has associated share history and must be deactivated instead.
4. THE Rules_View SHALL display a deactivated Share_Rule with a visually distinct inactive badge so the Admin_User can distinguish it from active rules.

---

### Requirement 5: Share Item Ledger

**User Story:** As an Admin_User, I want to view all share items with their status and collected amounts, so that I can monitor what is owed to each doctor and track collection progress.

#### Acceptance Criteria

1. THE Share_Controller SHALL render the Items_View at route `doctor-share/items` with all Share_Items paginated at 25 per page, ordered by `created_at` descending.
2. WHEN the Admin_User filters by doctor, THE Items_View SHALL display only Share_Items where `doctor_id` matches the selected doctor.
3. WHEN the Admin_User filters by status (`pending`, `settled`, `voided`), THE Items_View SHALL display only Share_Items matching the selected status.
4. WHEN the Admin_User filters by date range, THE Items_View SHALL display only Share_Items where `created_at` falls within the selected range.
5. THE Items_View SHALL display for each Share_Item: doctor name, bill number, bill date, base amount, share amount, collected amount (derived from `SUM(allocations.amount)`), and status badge.
6. THE Items_View SHALL display a summary row at the top showing total share amount, total collected amount, and total pending amount for the current filtered result set.
7. WHEN the Admin_User clicks a Share_Item row, THE Items_View SHALL expand an inline detail panel showing all Share_Allocations for that item with payment date, payment amount, and allocated share amount.
8. IF a Share_Item has `status` = `voided`, THEN THE Items_View SHALL display the void reason and voided-at timestamp in the detail panel.

---

### Requirement 6: Settlement Batch Execution

**User Story:** As an Admin_User, I want to run a settlement batch for one or all doctors, so that I can mark earned shares as settled and record the disbursement for accounting purposes.

> **Decided:** Settlement batches are stored in a dedicated `doctor_share_settlements` table. A Share_Item is eligible for settlement as soon as any allocation exists for it (i.e., at least one partial payment has been received). Items can be settled even when `collected_amount` < `share_amount` — partial collection is acceptable. The settlement captures the total collected amount at the time of settlement (`SUM(allocations.amount)`). This allows progressive clearing of share items as payments arrive, without waiting for the bill to be fully paid.

#### Acceptance Criteria

1. THE Share_Controller SHALL render the Settlement_View at route `doctor-share/settlements` listing all past Settlement_Batches with batch date, doctor scope, item count, and total settled amount.
2. WHEN the Admin_User initiates a new settlement batch, THE Settlement_View SHALL display a preview form showing the doctor scope (one doctor or all), date range, and the list of eligible pending Share_Items with their share amounts and current collected amounts.
3. THE Share_Controller SHALL define a pending Share_Item as eligible for settlement WHEN its `status` = `pending` AND its `settlement_id` IS NULL AND at least one `DoctorShareAllocation` row exists for it AND its `created_at` falls within the selected date range.
4. WHEN the Admin_User confirms the settlement batch, THE Share_Controller SHALL: (a) insert a new row into `doctor_share_settlements`, (b) update all eligible Share_Items to `status` = `settled`, `settlement_id` = the new settlement record's ID, and `collected_at_settlement` = `SUM(allocations.amount)` at that moment — all within a single database transaction.
5. IF the settlement batch transaction fails, THEN THE Share_Controller SHALL roll back all changes and display an error message without partially settling any Share_Items.
6. WHEN a settlement batch completes successfully, THE Share_Controller SHALL redirect to the Settlement_View with a success flash message showing the count of settled items and the total settled amount.
7. THE Settlement_View SHALL display each past Settlement_Batch with a link to view the full list of Share_Items included in that batch.
8. IF no eligible pending Share_Items exist for the selected scope and date range, THEN THE Settlement_View SHALL display an informational message stating there are no items to settle.

---

### Requirement 7: Share Report

**User Story:** As an Admin_User, I want to view a share report filtered by doctor and date range, so that I can review earnings history and prepare for doctor payments.

> **Decided:** The print report opens on a separate page/route (not CSS `@media print`). The print view omits the sidebar and navigation chrome and is optimised for printing.

#### Acceptance Criteria

1. THE Share_Controller SHALL render the Report_View at route `doctor-share/reports` with a summary table grouped by doctor showing total earned, total collected, total pending, and total settled amounts.
2. WHEN the Admin_User filters by doctor, THE Report_View SHALL display only rows for the selected doctor.
3. WHEN the Admin_User filters by date range, THE Report_View SHALL include only Share_Items where `created_at` falls within the selected range.
4. THE Report_View SHALL display a detail section below the summary table listing individual Share_Items matching the current filter, with the same columns as the Items_View (Requirement 5, criterion 5).
5. THE Report_View SHALL display a "Print" button that navigates to a dedicated print route (`doctor-share/reports/print`) which renders a print-optimised Blade view without the sidebar and navigation chrome.
6. WHEN the Admin_User filters by bill type (`opd`, `ipd`, `investigation`, `emergency`), THE Report_View SHALL include only Share_Items whose associated bill has the matching `bill_type`.

---

### Requirement 8: Sidebar Navigation Integration

**User Story:** As an Admin_User, I want to access the Doctor Share module from the sidebar, so that I can navigate to it without knowing the direct URL.

#### Acceptance Criteria

1. THE Sidebar SHALL display a "Doctor Share" submenu group under the Billing section, visible only to users with the `manage doctor shares` permission.
2. THE Sidebar SHALL include four submenu links: "Share Rules", "Share Items", "Settlements", and "Share Reports", each pointing to the corresponding route.
3. WHEN the current route matches any `doctor-share.*` route, THE Sidebar SHALL expand the Doctor Share submenu and apply the `bg-medical-light text-medical-blue` active class to the matching link.
4. THE Sidebar SHALL use the `fas fa-hand-holding-usd` icon for the Doctor Share submenu group header.

---

### Requirement 9: Access Control

**User Story:** As a system administrator, I want doctor share management to be restricted to authorised users, so that financial configuration cannot be changed by unauthorised staff.

#### Acceptance Criteria

1. THE Share_Controller SHALL apply the `manage doctor shares` permission gate to all routes in the `doctor-share.*` route group.
2. IF an authenticated user without the `manage doctor shares` permission attempts to access any `doctor-share.*` route, THEN THE Share_Controller SHALL return a 403 Forbidden response.
3. THE Share_Controller SHALL log the doctor name, rule details, and acting user ID to the audit log on every Share_Rule create, update, deactivate, and delete action.
4. THE Share_Controller SHALL log the settlement batch details and acting user ID to the audit log on every settlement batch execution.

---

### Requirement 10: Design System Compliance

**User Story:** As a developer, I want all Doctor Share UI views to follow the existing design system, so that the module is visually consistent with the rest of the HMS.

#### Acceptance Criteria

1. THE Rules_View, Items_View, Settlement_View, and Report_View SHALL use `bg-medical-blue` for primary action buttons and `bg-medical-light text-medical-blue` for active navigation states.
2. THE Rules_View, Items_View, Settlement_View, and Report_View SHALL use the same `bg-white rounded-lg shadow-sm` card container pattern as the doctors and bills index views.
3. THE Rules_View, Items_View, Settlement_View, and Report_View SHALL use the same `thead bg-gray-50` table header pattern with `text-xs font-medium text-gray-500 uppercase tracking-wider` column headings.
4. THE Rules_View, Items_View, Settlement_View, and Report_View SHALL use `@extends('admin.layout')` and `@section('content')` to integrate with the existing admin layout.
5. THE Rules_View, Items_View, Settlement_View, and Report_View SHALL display status badges using the same pill pattern as the bills index: `bg-{color}-100 text-{color}-800 text-xs px-2 py-1 rounded`.
6. THE Rules_View, Items_View, Settlement_View, and Report_View SHALL display flash success and error messages using the same session-based alert pattern as the existing billing views.
