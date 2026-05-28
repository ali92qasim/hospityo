# Service Bulk Import Feature

## Overview

This feature adds a CSV/Excel bulk upload capability to the Services module, mirroring the existing investigation import system. A hospital administrator can download a template, fill it in, and upload it to create or update many services at once without touching individual forms.

---

## Current State

The `ServiceController` has no import functionality. The services index view has only an "Add Service" button. The `Service` model has these fields: `name`, `code`, `description`, `category`, `price`, `department_id`, `is_active`.

The investigation import system (the reference implementation) uses:
- A `ServiceImportService` class for CSV parsing logic
- A `ImportInvestigationsJob` for background processing
- A `Cache`-based polling mechanism for async status updates
- A hidden file input + JS confirmation dialog in the view
- An `importStatus` AJAX endpoint polled by `import-poller.js`

The queue driver is `database` (stored in the landlord DB). This means jobs run via `php artisan queue:work` — **no Redis or external broker needed**.

---

## The Excel Problem (Critical UX Requirement)

When a user opens a `.csv` file in Excel and presses `Ctrl+S`, Excel silently converts it to `.xlsx` format. The user sees no warning (or dismisses it without reading). They then upload a `.xlsx` file thinking it is still a CSV.

**Solution:** Accept both `.csv` and `.xlsx` file extensions. On the backend, detect the actual file format by reading the file's MIME type and magic bytes — not just the extension — and parse accordingly.

- `.csv` / `.txt` → parse with `fgetcsv()`
- `.xlsx` → parse with the `PhpSpreadsheet` library (`phpoffice/phpspreadsheet`), which is already a common Laravel dependency

The user-facing upload button label should say **"Import CSV / Excel"** rather than "Import CSV" to set correct expectations.

---

## Async Without Running `artisan queue:work` Manually

The investigation import uses `ShouldQueue` which requires a queue worker process. The user's concern is valid — if no worker is running, the job sits in the `jobs` table forever and nothing happens.

**Solution: Use Laravel's `deferred` queue driver for this specific job.**

The `deferred` driver (available in Laravel 11+) runs the job at the end of the current HTTP request, in the same PHP process, after the response has been sent to the browser. This means:

- No separate `queue:work` process needed
- The import runs automatically after the user uploads
- The browser gets the redirect response immediately (feels async to the user)
- The import completes in the background of the same request lifecycle

**Tradeoff:** If the import is very large (thousands of rows), it will slow down the server response slightly. For services (typically < 500 rows), this is acceptable. The 5-minute timeout on the job handles edge cases.

**Implementation:** Dispatch the job `onConnection('deferred')` instead of the default queue connection:

```php
ImportServicesJob::dispatch($path, $cacheKey, auth()->id())->onConnection('deferred');
```

The Cache-based polling mechanism still works — the job writes its result to Cache, and the browser polls the `importStatus` endpoint. The only difference is the job runs immediately rather than waiting for a worker.

---

## CSV Template Format

File: `public/templates/services-template.csv`

```
code,name,category,description,price,department_name,is_active
CONS001,General Consultation,consultation,Standard outpatient consultation,500,,1
DRESS001,Wound Dressing,procedure,Basic wound dressing and bandaging,300,Surgery,1
ECG001,Electrocardiogram,diagnostic,12-lead ECG recording and report,800,Cardiology,1
```

### Column Definitions

| Column | Required | Description |
|--------|----------|-------------|
| `code` | Yes | Unique service code. Used as the upsert key — existing services with the same code are updated. |
| `name` | Yes | Display name of the service. |
| `category` | No | One of: `consultation`, `procedure`, `diagnostic`, `surgical`, `nursing`, `other`. Defaults to `other` if blank or unrecognised. |
| `description` | No | Short description. |
| `price` | No | Numeric price. Defaults to `0` if blank. |
| `department_name` | No | Name of the department (matched by name, case-insensitive). Leave blank if not department-specific. |
| `is_active` | No | `1` = active, `0` = inactive. Defaults to `1`. |

**Note on `department_name`:** The template uses the department name (human-readable) rather than `department_id` (a database integer the user cannot know). The import service resolves the name to an ID. If the name doesn't match any department, the service is imported without a department assignment and a warning is added to the error list.

---

## Files to Create / Modify

### New Files

| File | Purpose |
|------|---------|
| `app/Services/ServiceImportService.php` | CSV/Excel parsing, upsert logic, error collection |
| `app/Jobs/Tenant/ImportServicesJob.php` | Deferred queue job wrapping the import service |
| `public/templates/services-template.csv` | Downloadable template |

### Modified Files

| File | Change |
|------|--------|
| `app/Http/Controllers/ServiceController.php` | Add `import()` and `importStatus()` methods |
| `resources/views/admin/services/index.blade.php` | Add template download button, import button, hidden form, polling script |
| `routes/web.php` | Add `POST /services/import` and `GET /services/import-status` routes |

---

## Implementation Details

### 1. `ServiceImportService`

Mirrors `InvestigationImportService` exactly, adapted for the `Service` model.

Key differences from the investigation service:
- Columns: `code`, `name`, `category`, `description`, `price`, `department_name`, `is_active`
- No parameter sub-rows (services are flat records)
- `department_name` → resolved to `department_id` via `Department::where('name', 'LIKE', $name)->first()`
- Accepts both CSV (via `fgetcsv`) and XLSX (via `PhpSpreadsheet`)
- File format detection: check MIME type first, fall back to extension

**File format detection logic:**
```php
$mime = mime_content_type($absolutePath);
$ext  = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

$isExcel = in_array($mime, [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel',
    'application/zip', // xlsx files are ZIP archives
]) || in_array($ext, ['xlsx', 'xls']);
```

If Excel is detected, load with `PhpSpreadsheet` and convert the first sheet to an array, then process identically to CSV rows.

**Error handling requirements:**
- File not found → return error immediately, do not throw
- File cannot be opened → return error immediately
- Empty file → return error immediately
- Missing required columns (`code`, `name`) → return error immediately with column list
- Per-row errors (invalid price, unknown category) → collect in `$errors[]`, continue processing remaining rows
- Database errors per row → catch `\Throwable`, add to `$errors[]`, continue
- Never let a single bad row abort the entire import

### 2. `ImportServicesJob`

Mirrors `ImportInvestigationsJob` exactly.

```php
ImportServicesJob::dispatch($path, $cacheKey, auth()->id())
    ->onConnection('deferred'); // runs after response, no worker needed
```

- `$tries = 1` — do not retry imports (idempotent upserts make retries safe, but a failed import should be re-uploaded by the user)
- `$timeout = 300` — 5 minutes max
- `failed()` method writes `['status' => 'failed', 'message' => ...]` to Cache
- `finally` block always deletes the uploaded file from storage

### 3. `ServiceController` additions

```php
public function import(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
    ]);

    $path     = $request->file('file')->store('imports/services', 'local');
    $cacheKey = 'service_import_' . auth()->id() . '_' . Str::random(8);

    Cache::put($cacheKey, ['status' => 'pending'], now()->addMinutes(30));

    ImportServicesJob::dispatch($path, $cacheKey, auth()->id())
        ->onConnection('deferred');

    return redirect()->route('services.index')
        ->with('import_pending', true)
        ->with('import_cache_key', $cacheKey);
}

public function importStatus(Request $request)
{
    $key = $request->query('key');

    if (!$key) {
        return response()->json(['status' => 'not_found']);
    }

    $result = Cache::get($key);

    if ($result === null) {
        return response()->json(['status' => 'not_found']);
    }

    if ($result['status'] === 'pending') {
        return response()->json(['status' => 'pending']);
    }

    Cache::forget($key);
    return response()->json($result);
}
```

### 4. Routes

Add inside the `auth` middleware group, alongside existing service routes:

```php
Route::post('/services/import', [ServiceController::class, 'import'])->name('services.import');
Route::get('/services/import-status', [ServiceController::class, 'importStatus'])->name('services.import-status');
```

**Important:** These routes must be defined BEFORE `Route::resource('services', ...)` to prevent the resource route from capturing `/services/import` as a `show` route with `{service} = 'import'`.

### 5. Services index view changes

Add to the action bar (alongside the existing "Add Service" button):

```blade
{{-- Template download --}}
<a href="{{ asset('templates/services-template.csv') }}" download
   class="text-gray-500 hover:text-medical-blue px-3 py-2 border border-gray-300 rounded-lg text-sm">
    <i class="fas fa-download mr-1"></i>Template
</a>

{{-- Import button --}}
<button type="button" onclick="document.getElementById('service-import-file').click()"
        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm">
    <i class="fas fa-file-upload mr-2"></i>Import CSV / Excel
</button>

{{-- Hidden form --}}
<form id="service-import-form" action="{{ route('services.import') }}" method="POST"
      enctype="multipart/form-data" class="hidden">
    @csrf
    <input type="file" id="service-import-file" name="file"
           accept=".csv,.xlsx,.xls,.txt"
           onchange="if(this.files.length){
               if(confirm('Import ' + this.files[0].name + '?\n\nExisting services with the same code will be updated.\nNew services will be created.')) {
                   this.closest('form').submit();
               } else {
                   this.value='';
               }
           }">
</form>
```

Add the polling script (same pattern as investigations):

```blade
@if(session('import_pending'))
<script>
(function () {
    localStorage.setItem('serviceImportKey',       @json(session('import_cache_key')));
    localStorage.setItem('serviceImportStatusUrl', @json(route('services.import-status')));
    localStorage.setItem('serviceImportIndexUrl',  window.location.href);
    localStorage.setItem('serviceImportExpiry',    String(Date.now() + 25 * 60 * 1000));
})();
</script>
@endif
```

The existing `import-poller.js` (used for investigations) should be extended to also handle the service import keys, or a separate `service-import-poller.js` can be created following the same pattern.

### 6. PhpSpreadsheet dependency

Check if already installed:
```bash
composer show phpoffice/phpspreadsheet
```

If not installed:
```bash
composer require phpoffice/phpspreadsheet
```

---

## Validation Rules

| Rule | Detail |
|------|--------|
| File required | Reject if no file attached |
| File size | Max 10 MB |
| File type | Accept `.csv`, `.txt`, `.xlsx`, `.xls` only |
| `code` column | Required in every row. Blank code → skip row, add to errors |
| `name` column | Required in every row. Blank name → skip row, add to errors |
| `price` | Must be numeric ≥ 0. Non-numeric → default to 0, add warning |
| `category` | Must be one of the allowed values. Unknown → default to `other`, add warning |
| `is_active` | Must be `0` or `1`. Anything else → default to `1` |
| `department_name` | Optional. If provided and not found → import without department, add warning |
| Duplicate `code` in same file | Last row wins (upsert behaviour) |

---

## Error Handling Requirements

- Wrap the entire file processing in a try/catch — a corrupt file must not crash the job
- Per-row errors must be collected and returned, not thrown
- The job's `failed()` method must write to Cache so the browser poller can surface the failure
- The uploaded file must always be deleted from storage (use `finally`)
- Log all row-level errors with `Log::warning()` including row number and code
- Log job-level failures with `Log::error()`
- Never expose raw exception stack traces to the user — show a generic "Import failed" message with a support reference

---

## Testing Checklist

After implementation, manually test:

1. Download the template → open in Excel → fill in 3 rows → press `Ctrl+S` (Excel will ask to keep as CSV or convert; press "No" to convert to XLSX) → upload the `.xlsx` file → verify it imports correctly
2. Upload a valid `.csv` file → verify services are created
3. Upload the same `.csv` again → verify existing services are updated (not duplicated)
4. Upload a file with one invalid row (blank code) → verify the other rows import and the error is shown
5. Upload a file with an unknown department name → verify the service imports without a department and a warning appears
6. Upload a file larger than 10 MB → verify it is rejected with a validation error
7. Upload a `.pdf` file → verify it is rejected
8. Upload a file with no `code` column → verify it is rejected immediately with a clear message
