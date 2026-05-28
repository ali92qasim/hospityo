# Bug Report: Sidebar Permission Gaps — Full Audit

## The Two Problems

### Problem 1: Permission String Mismatch (Pharmacy & Diagnostics)

The sidebar checks `view services` / `create services` for both the Pharmacy section and the Diagnostics section. These are billing-domain permissions. A user who has `view services` (a billing permission) will see Pharmacy AND Diagnostics in the sidebar — even if they have no pharmacy or lab role. Conversely, a user who has `view investigations` or `view pharmacy` (the logically correct permissions) will see nothing.

### Problem 2: Sections With No Permission Check At All (Accounting, Reports, Patients)

Several sidebar sections are visible to **every authenticated user** regardless of their role or permissions:

| Section | Current guard | Should be |
|---------|--------------|-----------|
| **Patients** | `hasModule('patients')` only — no permission check | `view patients` |
| **Accounting** | `hasModule('billing')` only — the `@can('view bills')` wraps only the heading button, NOT the submenu `<div>` | `view bills` on the entire block |
| **Reports** | `hasModule('reports')` only — no permission check | `view bills` or a dedicated `view reports` permission |
| **HR** | `@hasrole('Super Admin|Hospital Administrator')` — role-based, not permission-based | Acceptable, but inconsistent |

This is why a Lab Technician or Pharmacist sees Accounting and Reports — those sections have no permission gate at all.

---

## Full Sidebar Audit

| Section | Current check | Problem | Fix |
|---------|--------------|---------|-----|
| Dashboard | None (always visible) | ✅ Correct | — |
| Patients | `hasModule('patients')` only | ❌ No permission check — visible to all | Add `@can('view patients')` |
| Doctors | `hasModule` + `can('view doctors')` | ✅ Correct | — |
| Departments | `@can('view departments')` | ✅ Correct | — |
| Visits | `hasModule` + `can('view visits')` | ✅ Correct | — |
| Appointments | `hasModule` + `can('view appointments')` | ✅ Correct | — |
| IPD | `@canany(['view departments', 'create departments'])` | ✅ Correct | — |
| **Pharmacy** | `@canany(['view services', 'create services'])` | ❌ Wrong permission strings | Change to `view services` (pharmacy is billing-adjacent) OR add `view pharmacy` |
| **Diagnostics** | `@canany(['view services', 'create services'])` | ❌ Wrong permission strings | Change to `view investigations` |
| Billing | `@canany(['view bills', 'view services'])` | ✅ Correct | — |
| Doctor Share | `@can('manage doctor shares')` | ✅ Correct | — |
| **Accounting** | `@can('view bills')` on heading only — submenu `<div>` has NO closing `@endif` before it | ❌ Submenu always renders | Wrap entire accounting block in `@can('view bills')` |
| **Reports** | `hasModule('reports')` only | ❌ No permission check | Add `@canany(['view bills', 'view reports'])` |
| HR | `@hasrole('Super Admin|Hospital Administrator')` | ⚠️ Role-based not permission-based | Acceptable for now |
| RBAC | `@canany(['view roles', 'view permissions', ...])` | ✅ Correct | — |
| Settings | (need to check) | — | — |

---

## The Accounting Bug (Specific)

Looking at the sidebar code:

```blade
{{-- Accounting --}}
@if(!$currentTenant || $currentTenant->hasModule('billing'))
@can('view bills')
<li class="pt-4">
    <button ...>Accounting</button>
</li>
@endcan
<div id="accounting-submenu" ...>   ← THIS DIV IS OUTSIDE THE @can BLOCK
    @can('view bills')
    <li>...</li>
    ...
    @endcan
</div>
@endif
```

The `@can('view bills')` only wraps the heading `<li>` button. The `<div id="accounting-submenu">` is outside the `@can` block — it always renders. The links inside have `@can('view bills')` so they don't show, but the empty `<div>` still exists in the DOM. More importantly, the heading button IS gated, but the submenu div is not — so if someone navigates directly to an accounting URL, the submenu opens for everyone.

---

## What Needs to Change

### 1. Sidebar — Diagnostics section

Change from `view services` to `view investigations`:

```blade
{{-- BEFORE --}}
@canany(['view services', 'create services'])
...
@can('view services')
<div id="diagnostics-submenu" ...>

{{-- AFTER --}}
@canany(['view investigations', 'create investigations'])
...
@can('view investigations')
<div id="diagnostics-submenu" ...>
```

### 2. Sidebar — Pharmacy section

Pharmacy is tightly coupled to `view services` in billing. The cleanest fix without breaking existing users is to check BOTH — show pharmacy if user has `view services` OR `view pharmacy`:

```blade
{{-- BEFORE --}}
@canany(['view services', 'create services'])
...
@can('view services')
<div id="pharmacy-submenu" ...>

{{-- AFTER --}}
@canany(['view services', 'create services', 'view pharmacy', 'manage pharmacy'])
...
@if(auth()->user()->can('view services') || auth()->user()->can('view pharmacy'))
<div id="pharmacy-submenu" ...>
@endif
```

### 3. Sidebar — Patients section

Add permission check:

```blade
{{-- BEFORE --}}
@if(!$currentTenant || $currentTenant->hasModule('patients'))
<li>
    <a href="{{ route('patients.index') }}">Patients</a>
</li>
@endif

{{-- AFTER --}}
@if((!$currentTenant || $currentTenant->hasModule('patients')) && auth()->user()->can('view patients'))
<li>
    <a href="{{ route('patients.index') }}">Patients</a>
</li>
@endif
```

### 4. Sidebar — Accounting section

Wrap the entire accounting block (heading + submenu) in a single `@can('view bills')`:

```blade
{{-- BEFORE --}}
@if(!$currentTenant || $currentTenant->hasModule('billing'))
@can('view bills')
<li class="pt-4"><button>Accounting</button></li>
@endcan
<div id="accounting-submenu" ...>   ← always renders
    @can('view bills')
    ...links...
    @endcan
</div>
@endif

{{-- AFTER --}}
@if((!$currentTenant || $currentTenant->hasModule('billing')) && auth()->user()->can('view bills'))
<li class="pt-4"><button>Accounting</button></li>
<div id="accounting-submenu" ...>
    ...links (no inner @can needed, outer check covers it)...
</div>
@endif
```

### 5. Sidebar — Reports section

Add permission check:

```blade
{{-- BEFORE --}}
@if(!$currentTenant || $currentTenant->hasModule('reports'))
<li class="pt-4"><button>Reports</button></li>
<div id="reports-submenu" ...>...</div>
@endif

{{-- AFTER --}}
@if((!$currentTenant || $currentTenant->hasModule('reports')) && auth()->user()->can('view bills'))
<li class="pt-4"><button>Reports</button></li>
<div id="reports-submenu" ...>...</div>
@endif
```

---

## Routes That Still Need Updating

The investigation routes were already fixed. These still need updating:

| Routes | Current middleware | Fix |
|--------|-------------------|-----|
| `medicine-categories`, `medicine-brands`, `medicines`, `prescription-instructions`, `units` | `permission:view services\|create services\|...` | `permission:view services\|view pharmacy\|manage pharmacy` |
| `inventory.*` | `permission:view services` / `permission:create services` | `permission:view services\|view pharmacy\|manage pharmacy` |
| `suppliers`, `purchases` | `permission:view services\|create services\|...` | `permission:view services\|view pharmacy\|manage pharmacy` |

---

## Seeder — Already Updated

The `RolePermissionSeeder` already contains `view investigations`, `create investigations`, `view pharmacy`, `manage pharmacy`. After applying the sidebar and route fixes, run:

```bash
php artisan tenants:sync-permissions
```

---

## Summary of All Files to Change

| File | Changes |
|------|---------|
| `resources/views/partials/sidebar.blade.php` | Fix Diagnostics (use `view investigations`), fix Pharmacy (add `view pharmacy`), fix Patients (add permission check), fix Accounting (wrap entire block), fix Reports (add permission check) |
| `routes/web.php` | Fix pharmacy, inventory, supplier, purchase route middleware |
| `database/seeders/RolePermissionSeeder.php` | Already done |
