# RBAC Wave 3 Remainder — Design

**Status:** Approved  
**Date:** 2026-10-28  
**Branch:** `feat/rbac-wave3-remainder`  
**Modules:** Appointments, Services, Billing, Laboratory, Imaging, Visits

## Goal

Finish Wave 3 RBAC for the six remaining unscoped modules using the Wave 1/2 template (per-route Spatie, UI gates, route-scoping tests, README/backfills), with module-specific adjustments. Deliver as one umbrella design + one plan, sequential SDD on a single branch, then linear ff to `main` (no merge commit).

## Locked decisions

- Packaging: umbrella + sequential SDD (not a parallel mega-PR).
- No soft delete and no hard delete for these six modules.
- Drop `delete *` from `PermissionRegistry`, `RolePermissionSeeder`, and role grants; remove destroy routes and Delete UI.
- Cancel / void / workflow / status actions stay as today.
- Payments: keep `create payments` / `edit payments` / `delete payments` (payment line removal is not bill destroy).
- Visits: replace `hasRole('Doctor')` with Spatie `view assigned visits`.
- Execution order: Appointments → Services → Billing → Laboratory → Imaging → Visits → closing gate.

## Shared module playbook

1. Replace CRUD any-of resource middleware with per-route `permission:view|create|edit …` (no `delete`).
2. `->except(['destroy'])` and/or remove explicit DELETE routes; remove or dead-code `destroy` methods.
3. Strip matching `delete …` strings from registry and seeder role lists.
4. Gate Blade / DataTables: no Delete controls; AD-1 (`RedirectsAfterWrite`) on remaining writes where the module already uses it or Wave pattern requires it.
5. Add `*RouteScopingTest` per module: permission isolation, destroy absent/404, UI has no delete affordance.
6. README deploy notes: `tenants:sync-permissions` plus any new-permission backfills (`--dry-run` then apply).

## Permission outcomes by module

### Appointments

- Keep: `view appointments`, `create appointments`, `edit appointments`
- Drop: `delete appointments`
- Calendar `calendar.events` stays `view appointments`

### Services

- Keep: `view services`, `create services`, `edit services`
- Drop: `delete services`
- Import routes stay `create services`
- Module gate remains billing (unchanged CheckModule map)

### Billing (bills)

- Keep: `view bills`, `create bills`, `edit bills`
- Drop: `delete bills`
- Payments unchanged: `create payments`, `edit payments`, `delete payments`
- Taxes stay `view bills|create bills` (no delete surface)
- Cancel/void/accounting reverse paths used by edit/payment flows are out of delete-removal scope

### Laboratory

- Keep view/create/edit for: investigations, investigation orders, lab orders, lab results
- Drop: `delete investigations`, `delete investigation orders`, `delete lab orders`, `delete lab results`
- Aliases (`lab-tests`, `/lab/*`) use the same permissions; no destroy routes
- Dual naming (investigations vs lab) preserved; only middleware and delete surface change

### Imaging

- Keep: `view radiology results`, `create radiology results`, `edit radiology results`
- Drop: `delete radiology results`
- **Add** under imaging registry group:
  - `view imaging studies`, `create imaging studies`, `edit imaging studies`
  - `view imaging orders`, `create imaging orders`, `edit imaging orders`
- All `/imaging/studies*` and `/imaging/orders*` middleware switch from investigation / lab-order permissions to the new imaging permissions
- Drop DESTROY for studies and orders
- Backfill: map prior investigation / lab-order grants that previously unlocked imaging routes to the imaging equivalents (view↔view, create↔create, edit↔edit); Super Admin via `tenants:sync-permissions`
- Lab module no longer gates Imaging UI

### Visits

- Keep: `view visits`, `create visits`, `edit visits`
- Drop: `delete visits` (resource already excludes destroy; remove permission entirely)
- **Add:** `view assigned visits`
  - Grant to seeded **Doctor** role only (not HA/SA)
  - Backfill: `rbac:backfill-view-assigned-visits` (Doctor only, `--dry-run`, report custom roles without granting)

## Visits Spatie behavior (`view assigned visits`)

- **Index / data:** If the user `can('view assigned visits')`, scope the query to that user’s `Doctor` profile (`doctor_id` match or IPD care-team membership). Same query shape as today’s `hasRole('Doctor')` block. Missing Doctor profile → empty result set.
- Users **without** `view assigned visits` who have `view visits` keep the unscoped admin list (HA / SA).
- **`checkPatient`:** Require `view assigned visits` + Doctor profile + assignment / care-team access; no `hasRole`. Else 403.
- Do **not** grant `view assigned visits` to Hospital Administrator or Super Admin via seeder defaults (SA still receives it only if `Permission::all()` sync is used — prefer registry presence without intentional HA grant; if SA sync-all includes it, index scoping must still prefer: only apply assigned scope when the user has `view assigned visits` **and** we intend doctor UX — SA with all permissions would get scoped lists. **Mitigation:** apply assigned-visit scoping only when the user has `view assigned visits` **and** lacks broad admin listing intent.

### Scoping rule (normative)

Apply doctor-assigned scoping when:

```text
auth()->user()->can('view assigned visits')
&& ! auth()->user()->can('view visits') is WRONG — Doctor has both view visits and view assigned visits today.
```

Doctor role already has `view visits`. Scoping trigger must be:

```text
auth()->user()->can('view assigned visits')
```

only — same as today’s `hasRole('Doctor')` which fired for Doctor role holders even though they also have `view visits`. HA/SA must **not** receive `view assigned visits`, so they stay unscoped. Super Admin via `syncPermissions(Permission::all())` **would** receive `view assigned visits` and would become scoped — unacceptable for admin.

**Normative fix for SA:** Apply assigned scoping only when:

```php
auth()->user()->can('view assigned visits')
    && ! auth()->user()->hasRole('Super Admin');
```

That retains one `hasRole` for Super Admin carve-out, **or** prefer:

```php
auth()->user()->can('view assigned visits')
    && Doctor::where('user_id', auth()->id())->exists();
```

**Chosen rule:** Scope when `can('view assigned visits')` **and** a `Doctor` profile exists for `auth()->id()`. Super Admin without a doctor profile stays unscoped; a Super Admin who is also a linked doctor gets scoped (acceptable edge case). No `hasRole` in VisitController access paths.

## Imaging cutover summary

1. Registry: imaging studies + imaging orders (view/create/edit) + radiology results (no delete).
2. Routes: `/imaging/studies*`, `/imaging/orders*` → imaging perms; no DELETE.
3. Backfill command(s) for studies and orders (or one combined imaging cutover backfill).
4. Seeder: grant imaging study/order perms to roles that today hold the corresponding investigation / lab-order perms (typically HA and similar).

## Out of scope

- SoftDeletes / trash / restore for these modules
- Redesign of cancel / void / discharge / triage / payment flows
- Patients and Doctors (already on `main`)
- Wave 1/2 modules (Pharmacy, OT, Accounting, Departments, HR, Wards)
- `fix/bug-fixes` WIP and existing stashes
- Rewriting `visit_type` / CheckModule remaps beyond permission middleware changes

## Testing expectations

- Per-module Feature `*RouteScopingTest` (TDD: red then green)
- Backfill command Feature tests for new permissions
- `PermissionRegistryTest` updated for added/removed names
- Closing gate: all new tests green; environmental warnings OK; no new real failures in touched suites

## Deploy

Same deploy action as the code:

1. `php artisan tenants:migrate` (only if a migration appears; none expected for delete-removal alone)
2. New backfills with `--dry-run` then apply (imaging cutover; view assigned visits)
3. `php artisan tenants:sync-permissions`

Document in README under a Wave 3 Remainder section.

## Success criteria

- No destroy route or Delete UI for appointments, services, bills, lab catalog/orders/results, imaging studies/orders/results, visits
- No `delete …` permission names for those resources in registry/seeder (payments excepted)
- Imaging gated by imaging permissions only
- Visits Doctor scoping / checkPatient use Spatie + Doctor profile, not `hasRole('Doctor')`
- Linear history on `main` via ff after approval
