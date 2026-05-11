# Tasks: Diagnostics & Billing Fixes

## Fix 1 — Investigation Order Status Propagation

- [x] 1.1 Fix `InvestigationOrderItem::result()` relationship
  - Remove the incorrect `HasOne` relationship that resolves to the parent order's result
  - Add `hasResult(): bool` helper that returns `$this->status === 'reported'`

- [x] 1.2 Fix pending-item filter in `LabResultController::index()` and `createBatch()`
  - Replace `->whereDoesntHave('result')` with `->whereNotIn('status', ['reported', 'verified', 'cancelled'])`

- [x] 1.3 Add `item_id` hidden field to `create-batch.blade.php`
  - Add `<input type="hidden" name="orders[{{ $index }}][item_id]" value="{{ $item->id }}">` inside the per-item form block

- [x] 1.4 Fix `LabResultController::storeBatch()` to use item_id
  - Add `orders.*.item_id` to validation rules
  - Look up `InvestigationOrderItem::find($orderData['item_id'])` instead of guessing first pending item
  - After saving result, call `$item->update(['status' => 'reported', 'test_location' => ...])`
  - After each item update, check if all order items are reported and update parent order status

- [x] 1.5 Remove `InvestigationOrder::resultItems()` incorrect relationship
  - Remove the `resultItems()` HasMany on `InvestigationOrder` that uses wrong FK

## Fix 2 — Lab Result Print Link & Report Loading

- [x] 2.1 Fix `LabResultController::report()` eager load
  - Add `investigationOrder.items.investigation` to the `$labResult->load([...])` call

- [x] 2.2 Verify print link in results index completed table
  - Confirm `route('lab-results.report', $result)` renders in the actions column
  - If missing, add print icon link alongside the existing view/verify icons

- [x] 2.3 Add print/report link to investigation orders show page
  - In `admin/lab/orders/show.blade.php`, for each item that has a result, add a "View Report" button linking to `route('lab-results.report', $item->result)`

## Fix 3 — Doctor Login Registration

- [x] 3.1 Register TenantUser in `DoctorController::store()`
  - After `User::create(...)`, add:
    `\App\Models\TenantUser::register($user->email, \App\Models\Tenant::current()->id);`

- [x] 3.2 Handle email change in `DoctorController::update()`
  - Before updating the user, capture the old email
  - After update, if email changed: delete old `tenant_users` row, register new email

- [x] 3.3 Check `EmployeeController::store()` for same gap
  - If it creates a `User`, add the same `TenantUser::register()` call

## Fix 4 — Billing Percentage Discount & Cash Register

- [x] 4.1 Create migration for discount type columns
  - Add `discount_type` ENUM(`fixed`, `percentage`) DEFAULT `fixed`
  - Add `discount_percentage` DECIMAL(5,2) DEFAULT 0
  - File: `database/migrations/tenant/YYYY_MM_DD_add_discount_type_to_bills.php`

- [x] 4.2 Update `Bill` model
  - Add `discount_type` and `discount_percentage` to `$fillable`
  - Add casts: `discount_percentage => 'decimal:2'`

- [x] 4.3 Update bill create/edit form UI
  - Replace single discount input with Fixed/Percentage toggle (radio buttons)
  - Add JS to compute `discount_amount` from percentage × subtotal on input
  - Submit `discount_type`, `discount_percentage`, and `discount_amount`

- [x] 4.4 Update `BillController::store()` and `update()`
  - Accept and save `discount_type` and `discount_percentage`
  - Ensure `discount_amount` is always the computed monetary value

- [x] 4.5 Update `StoreBillRequest` validation
  - Add `discount_type` (in: fixed, percentage) and `discount_percentage` (numeric, 0–100)

- [x] 4.6 Update daily cash register report controller
  - Add `'total_discount' => $bills->sum('discount_amount')` to `$summary`

- [x] 4.7 Update daily cash register report view
  - Add "Total Discount" summary card
  - Add Discount column to the bills table

## Fix 5 — Import Toast Persistence Across Navigation

- [x] 5.1 Create `resources/js/import-poller.js`
  - On module load, read `localStorage.getItem('investigationImportKey')`
  - If key exists, start polling the status URL (also stored in localStorage)
  - On `done`: clear localStorage keys, show `Toast.success()` or `Toast.warning()`
  - If current URL matches the investigations index URL (also stored), reload page first

- [x] 5.2 Import `import-poller.js` in `app.js`
  - Add `import './import-poller';` after the toast import

- [x] 5.3 Update investigations index view
  - Replace the inline polling `<script>` block with a minimal script that writes the cache key, status URL, and index URL to `localStorage`
  - Remove the `window.Toast.loading(...)` call from the view (the poller handles it)
