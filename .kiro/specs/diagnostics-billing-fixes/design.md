# Design: Diagnostics & Billing Fixes

## Guiding Principles
- Minimal surface area — fix the root cause, touch only what is necessary.
- No new packages or migrations beyond what is strictly required.
- Consistent behaviour across both order entry points (visit workflow / diagnostics).

---

## Fix 1 — Investigation Order Status Propagation

### Schema Analysis
`lab_results.investigation_order_id` → `investigation_orders.id` (order-level FK).  
`lab_result_items.lab_result_id` → `lab_results.id` (correct).  
`investigation_order_items` has no direct FK to `lab_results`.

The `InvestigationOrderItem::result()` relationship is:
```php
// WRONG — resolves to the parent order's result, not this item's result
public function result(): HasOne {
    return $this->hasOne(LabResult::class, 'investigation_order_id', 'investigation_order_id');
}
```

There is no per-item result FK. The correct approach: a result belongs to an order;
an item is "done" when its parent order has a result AND the item's own status is
`reported`. The `whereDoesntHave('result')` filter on items must be replaced with a
status-based filter.

### Changes

**`InvestigationOrderItem` model**
- Remove the `result()` HasOne relationship (it is semantically wrong).
- Add a computed `hasResult()` helper that checks `$this->status === 'reported'`.

**`LabResultController::index()`**
- Replace `->whereDoesntHave('result')` with `->whereNotIn('status', ['reported', 'verified', 'cancelled'])`.

**`LabResultController::createBatch()`**
- Same filter change as above.

**`LabResultController::storeBatch()`**
- The form submits one entry per `InvestigationOrder` (header). Each entry corresponds
  to one item (the create-batch view iterates items). The fix: track which item each
  form entry belongs to by adding a hidden `item_id` field to the create-batch view,
  then look up the item directly instead of guessing "first pending pathology item".
- After saving the result, update `item.status = 'reported'`.
- After all items on the order are reported, update `order.status = 'reported'`.

**`resources/views/admin/lab/results/create-batch.blade.php`**
- Add `<input type="hidden" name="orders[{{ $index }}][item_id]" value="{{ $item->id }}">`.

**`LabResultController::storeBatch()` validation**
- Add `orders.*.item_id` as `required|integer`.

---

## Fix 2 — Lab Result Items Saving & Print Link

### Root Cause Detail
`LabResultController::store()` correctly calls `$result->resultItems()->create(...)`.
The `LabResult::resultItems()` relationship is:
```php
public function resultItems(): HasMany {
    return $this->hasMany(LabResultItem::class);
}
```
This is correct. The issue is that `InvestigationOrder::resultItems()` is defined as:
```php
public function resultItems(): HasMany {
    return $this->hasMany(LabResultItem::class, 'lab_result_id');
}
```
This is wrong (lab_result_id ≠ investigation_order_id) and causes confusion but does
not affect saving. The actual save path is correct.

The real issue with "values not appearing": the report view uses `$labResult->labOrder`
which resolves via the `labOrder()` alias on `LabResult`. The report view then accesses
`$labResult->labOrder->order_number` — this works. But the `resultItems` are loaded
via `$labResult->resultItems` which is correct.

**Likely actual issue**: the `create.blade.php` form posts to
`route('lab-orders.results.store', $labOrder)` where `$labOrder` is an
`InvestigationOrderItem`. The route is `POST lab-orders/{orderItem}/results` which
binds to `InvestigationOrderItem`. The `store()` method creates the result with
`investigation_order_id = $orderItem->investigation_order_id` (the parent order id).
This is correct. The result items are created with `lab_result_id = $result->id`.
This is correct.

**The missing print link**: The completed results table in `index.blade.php` has a
print icon that links to `route('lab-results.report', $result)` — this route exists.
The issue is the `report.blade.php` view references `$labResult->labOrder->order_number`
but `labOrder` is defined as `belongsTo(InvestigationOrder::class, 'investigation_order_id')`.
This works. However the view also calls `$labResult->labOrder->items` which is not
eager-loaded in `LabResultController::report()`.

### Changes

**`LabResultController::report()`**
- Add `investigationOrder.items.investigation` to the eager load chain.

**`InvestigationOrder::resultItems()`**
- Remove this incorrectly-defined relationship to avoid confusion.

**`resources/views/admin/lab/results/index.blade.php`**
- Verify the print icon link is present in the completed results table (it is — confirm
  it renders correctly with the correct route).

**`resources/views/admin/lab/orders/show.blade.php`**
- Add a "View Report" / "Print Report" button for each item that has a result.

---

## Fix 3 — Doctor Login Registration

### Design
The fix is a one-line addition in every controller that creates a `User` record outside
of `UserController`. The `TenantUser::register()` method is idempotent (`firstOrCreate`),
so calling it multiple times is safe.

### Changes

**`DoctorController::store()`** — after `$user = User::create(...)`:
```php
\App\Models\TenantUser::register($user->email, \App\Models\Tenant::current()->id);
```

**`DoctorController::update()`** — if email changes:
```php
if ($user->wasChanged('email')) {
    // Remove old mapping, register new one
    \App\Models\TenantUser::where('email', $oldEmail)
        ->where('tenant_id', $tenant->id)->delete();
    \App\Models\TenantUser::register($user->email, $tenant->id);
}
```

**`EmployeeController::store()`** — same pattern if it creates a User.

**No changes** to `CentralLoginController`, `AuthenticatedSessionController`, or the
`tenant_users` schema.

---

## Fix 4 — Billing Percentage Discount & Cash Register

### Schema Change (minimal)
New migration adds two nullable columns to `bills`:
```
bills.discount_type       ENUM('fixed', 'percentage') DEFAULT 'fixed'
bills.discount_percentage DECIMAL(5,2) DEFAULT 0
```
`discount_amount` (existing) always stores the final monetary value — no change.

### Bill Form UI
Replace the single "Discount Amount" input with a toggle:
- Radio buttons: **Fixed (Rs.)** | **Percentage (%)**
- When "Percentage" is selected: show a percentage input + a read-only computed amount field.
- When "Fixed" is selected: show the amount input directly (existing behaviour).
- JS calculates `discount_amount = (subtotal * percentage) / 100` on input change.
- Both `discount_type`, `discount_percentage`, and `discount_amount` are submitted.

### `BillController::store()` / `update()`
- Accept `discount_type` and `discount_percentage` from request.
- Always store the computed `discount_amount` as the monetary value.

### `Bill::calculateTotals()`
- No change — it already uses `$this->discount_amount`.

### Daily Cash Register Report

**`ReportController::dailyCashRegister()`**
```php
$summary['total_discount'] = $bills->sum('discount_amount');
```

**`daily-cash-register.blade.php`**
- Add a fifth summary card: **Total Discount** (orange/yellow).
- Add a `Discount` column to the bills table.

---

## Fix 5 — Import Toast Persistence

### Design
Move the cache key from the server-side session to `localStorage` so it survives
navigation. The polling logic moves from the investigations index page into the global
`app.js` (via `toast.js` or a new `import-poller.js` module).

### Flow
1. Controller stores cache key in session (existing) AND returns it in the redirect response.
2. The investigations index page writes the key to `localStorage('investigationImportKey')` on load.
3. `app.js` checks `localStorage` on every page load. If a key exists, it starts polling.
4. On completion:
   - Clear `localStorage` key.
   - If `window.location.pathname` matches the investigations index, reload the page then show success toast.
   - Otherwise, show success toast immediately without reload.

### Changes

**`resources/js/import-poller.js`** (new file, imported in `app.js`)
- Reads `localStorage.getItem('investigationImportKey')`.
- Polls the status endpoint.
- On done: clears key, shows toast, conditionally reloads.

**`resources/views/admin/lab/tests/index.blade.php`**
- Replace the inline `<script>` block with a single line that writes the key to localStorage:
  ```html
  <script>
  if (@json(session('import_cache_key'))) {
      localStorage.setItem('investigationImportKey', @json(session('import_cache_key')));
      localStorage.setItem('investigationImportStatusUrl', @json(route('investigations.import-status')));
      localStorage.setItem('investigationImportIndexUrl', @json(route('investigations.index')));
  }
  </script>
  ```
- Remove the polling logic from the view entirely.

**`vite.config.js`**
- No change needed — `import-poller.js` is imported by `app.js`, not a separate entry.
