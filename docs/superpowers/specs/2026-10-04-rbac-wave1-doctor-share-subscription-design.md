# RBAC Wave 1: Doctor Share + `manage subscription` design

**Date:** 2026-10-04
**Status:** Draft. **Awaiting confirmation** of §5 before the implementation plan is written.
**Investigation:** `2026-10-04-rbac-wave1-doctor-share-subscription-investigation.md`
**Confirmed inputs:**
- D2: create does not imply view.
- D5: coarse permissions are valid for viewing only.
- D6: one permission per action, with before/after tests per route.
- D4: new `manage subscription` permission, backfilled to Super Admin and Hospital Administrator only; no role-name checks.
- W1-1: `manage doctor shares` is a superset.
- W1-2: `rates.sync` requires `edit share rules`.
- W1-3: `settlements.store` requires `approve settlements`.
- W1-4: billing and subscription pages require `manage subscription`.
- Spatie permissions only.

**Out of scope:** payment-integrity S1–S4 (Track A, own branch). Track A and this wave touch different lines of `BillingController` / `SubscriptionController` and different routes. They'll be rebased in whichever order they ship.

---

## 1. Doctor Share route mapping

Each route gets exactly **one action permission**, plus the `manage doctor shares` superset (W1-1). That's `permission:<action perm>|manage doctor shares`. It's an any-of across *the action and its superset*, never across CRUD verbs.

| Route | Before | After |
|---|---|---|
| `doctor-share.rates.index` | `view\|create\|edit share rules\|manage doctor shares` | `view share rules\|manage doctor shares` |
| `doctor-share.rules.index` | same | `view share rules\|manage doctor shares` |
| `doctor-share.rates.sync` | `create\|edit share rules\|manage doctor shares` | **`edit share rules\|manage doctor shares`** (W1-2) |
| `doctor-share.items.index` | `view\|create\|edit\|delete share items\|manage doctor shares` | `view share items\|manage doctor shares` |
| `doctor-share.settlements.index` | `view\|create\|edit\|approve settlements\|manage doctor shares` | `view settlements\|manage doctor shares` |
| `doctor-share.settlements.show` | same | `view settlements\|manage doctor shares` |
| `doctor-share.settlements.preview` | `create\|approve settlements\|manage doctor shares` | **`approve settlements\|manage doctor shares`** (**DS-1**) |
| `doctor-share.settlements.store` | same | **`approve settlements\|manage doctor shares`** (W1-3) |
| `doctor-share.reports.index` / `.print` | `view share reports\|manage doctor shares` | unchanged (already single-action) |

**Side effects:**
- `create settlements` no longer opens any route. It stays in `PermissionRegistry`, and no role is affected: only Super Admin and HA hold it, and both also hold `approve settlements`.
- **Real-role impact: none.** Super Admin (8 users) and Hospital Administrator (1) hold all 14 Doctor Share permissions in all 7 tenants, and no other role holds any. **No backfill is needed** for Doctor Share.

## 2. Doctor Share UI

| Location | Change |
|---|---|
| `admin/doctor-share/rates/index.blade.php:30` (matrix + Save) | Wrap the form submit in `@canany(['edit share rules','manage doctor shares'])`. View-only users see the matrix with inputs `disabled` and no Save button. |
| `admin/doctor-share/settlements/index.blade.php:8` ("New Settlement") | `@canany(['approve settlements','manage doctor shares'])` |
| `admin/doctor-share/settlements/preview.blade.php:108` (Create Settlement form) | same `@canany` (defense in depth; the route also blocks it) |
| Sidebar (`SidebarService.php:234-247`) | **no change**. Already `view X \|\| manage doctor shares` for each item. |
| **Settings → Doctor Share entry** (`SettingsAccess::canAccessSection`, non-bundled branch) | **Change:** for `GET`, only permissions whose names start with `view `, `manage ` or `access ` count. Today *any* section permission counts, so a holder of only `create share rules` / `delete share rules` would see a link that now 403s (D2). The other settings sections hold only `access settings.<key>`, so they're unaffected. Write methods keep today's any-of, and the routes enforce the rest. |

## 3. `manage subscription`

### 3.1 Permission and roles
- **Registry:** add `'manage subscription'` to `PermissionRegistry` under a new `subscription` group in the `settings` module (it's tenant account configuration).
- **Seeder:** `RolePermissionSeeder`:
  - **Hospital Administrator** list gets `manage subscription`.
  - **Super Admin** already syncs `Permission::all()`, so it picks it up automatically.
- **Backfill command:** `rbac:backfill-manage-subscription`, following `BackfillManagePacPermission` (every tenant, or the current one; per-tenant permission cache isolation; idempotent).
  - Creates the permission if missing.
  - Grants it to the roles named `Super Admin` and `Hospital Administrator` in each tenant.
  - Role names are used **only to pick which roles get the backfill, once**. They're never used as an access check.
- **Expected result:** 14 role grants (2 roles × 7 tenants), covering 9 users.

### 3.2 Routes
`permission:manage subscription` on:
- `billing.index`
- `billing.subscribe`
- `billing.payfast.success`
- `billing.payfast.cancel`
- `subscription.index`
- `subscription.activate`

The webhooks are not affected; they're server-to-server, authenticated by signatures in Track A.

### 3.3 Non-Spatie checks found and converted
- **`partials/sidebar.blade.php:66`:** the Subscription link is gated by `auth()->user()->hasAnyRole(['Super Admin','Hospital Administrator'])`, a hard-coded role-name check. **Convert** to `@can('manage subscription')`.

### 3.4 Other entry points that must follow
| Location | Change |
|---|---|
| `admin/layout.blade.php:47` trial banner "Upgrade Now" | `@can('manage subscription')`; others see the banner text without the link |
| `admin/billing/index.blade.php:31` link to subscription | inside a page that now requires the permission, so no change |
| `errors/trial-expired.blade.php:27` "Subscribe Now" | `@can('manage subscription')`; others see "Please ask your administrator to renew the subscription." |
| **`EnsureTenantActive.php:44-48`** (trial expired and no active subscription ⇒ redirect **everyone** to `subscription.index`) | **Change:** users who `can('manage subscription')` are redirected as today. Everyone else gets the existing (currently orphaned) `errors.trial-expired` view with HTTP 402, instead of a redirect into a page they can't open. Login and logout stay reachable. (**DS-2**) |

## 4. Tests (before/after for each route, same style as C1–C4)

`tests/Feature/Permissions/DoctorShareRouteScopingTest.php`:
- **Each of the 10 routes:**
  - a user holding **only the wrong-verb permission** that passes today gets **403** (fails before the fix);
  - a user holding the **correct single permission** gets **200/302** (positive control);
  - a `manage doctor shares` holder passes every route (superset).
- **`rates.sync`:**
  - a `create share rules`-only user gets 403 and the `doctor_share_rates` rows are unchanged;
  - an `edit share rules` user saves.
- **`settlements.store`:**
  - a `create settlements`-only user gets 403, no settlement row is created and items stay `pending`;
  - an `approve settlements` user settles.
- **UI:**
  - the rates page shows no Save for a view-only user and shows it for an editor;
  - "New Settlement" is hidden without `approve settlements`.
- **Settings entry:** a `create share rules`-only user doesn't get the Doctor Share settings entry; a `view share rules` user does.

`tests/Feature/Billing/ManageSubscriptionPermissionTest.php`:
- All 6 subscription routes:
  - authenticated **without** `manage subscription` gets 403 (fails before: 200/302);
  - **with** it, the current behaviour is unchanged.
- **Sidebar:** the Subscription link shows for `manage subscription`, and a role **named** "Hospital Administrator" without the permission does **not** see it.
- **Trial expired:**
  - a non-admin gets the 402 trial-expired page, with no Subscribe link and no redirect loop;
  - an admin is redirected to `subscription.index`.
- **Backfill command:**
  - grants `manage subscription` to exactly Super Admin and HA;
  - is idempotent on a second run;
  - leaves every other role without it.

**Real-render check:** not needed. There are no layout or print changes.

## 5. Decisions to confirm

| # | Question | Recommendation |
|---|---|---|
| **DS-1** | `settlements.preview` is only the first step towards `store`, and `store` now requires `approve settlements`. Should preview require the same, so whoever opens it can finish it? | **Yes, `approve settlements`.** Otherwise a create-only user can open a page whose only action 403s. |
| **DS-2** | Non-admin staff on an expired trial: show the 402 "trial expired, ask your administrator" page (proposed), or keep today's redirect, which would become a 403? | **402 trial-expired page.** |
| **DS-3** | Release order: Track A (payment integrity) first, then this wave rebased on it? Both edit `BillingController` / `SubscriptionController`. | **Yes, Track A merges first.** |
