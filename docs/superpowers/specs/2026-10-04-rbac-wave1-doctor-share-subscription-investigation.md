# RBAC Wave 1: Doctor Share + `manage subscription` investigation

**Date:** 2026-10-04
**Status:** Findings only. **No code changes.** Design comes after the decisions in §4.
**Code read:** `main` @ `55bbcb6` (via `git show`; the working tree is on `fix/bug-fixes` and untouched).
**Parent:** `2026-10-03-rbac-permission-escalation-investigation.md`.
**Rules for this wave:**
- D2: create does not imply view.
- D5: coarse permissions stay valid for viewing only.
- D6: one permission per action via `middlewareFor`, with before/after tests per route.
- D4: a new `manage subscription` permission, backfilled to Super Admin and Hospital Administrator only.
- Spatie permissions only.

**Method:**
- Route and controller reading on `main`.
- Read-only scan of all 51 roles in 7 local tenant databases, plus the landlord `payment_gateways` / `subscriptions` / `plans` tables. No writes.
- Nothing was executed against payment flows.

---

## 1. Doctor Share

### 1.1 Routes (10): current gate, real behaviour, and candidate permission

| Route | Method | What it actually does | Current middleware (any-of) | Candidate single permission |
|---|---|---|---|---|
| `doctor-share.rates.index` | GET | Rates matrix page (read) | `view share rules\|create share rules\|edit share rules\|manage doctor shares` | `view share rules` |
| `doctor-share.rates.sync` | PUT | **Deletes** the selected doctors' rate rows and **recreates** them (`DoctorShareController:61-131`), i.e. create, edit and delete in one save | `create share rules\|edit share rules\|manage doctor shares` | `edit share rules` (**W1-2**) |
| `doctor-share.rules.index` | GET | Redirect to the rates page (legacy link) | same as rates.index | `view share rules` |
| `doctor-share.items.index` | GET | Share items list (read) | `view\|create\|edit\|delete share items\|manage doctor shares` | `view share items` |
| `doctor-share.settlements.index` | GET | Settlements list | `view\|create\|edit\|approve settlements\|manage doctor shares` | `view settlements` |
| `doctor-share.settlements.show` | GET | One settlement | same | `view settlements` |
| `doctor-share.settlements.preview` | GET | Computes eligible items: the first step of creating a settlement | `create\|approve settlements\|manage doctor shares` | `create settlements` |
| `doctor-share.settlements.store` | POST | Creates the settlement **and immediately marks every item `settled`** (`:273-345`); there is no separate approval step | `create\|approve settlements\|manage doctor shares` | `create settlements` or `approve settlements` (**W1-3**) |
| `doctor-share.reports.index` | GET | Share report | `view share reports\|manage doctor shares` | `view share reports` |
| `doctor-share.reports.print` | GET | Printable report | same | `view share reports` |

**Controller and FormRequest checks:** none, apart from a module-entitlement guard (`DoctorShareController:538`, `abort_unless($module !== null, 403)`). The route middleware is the only authorization.

**Defined permissions that no route uses:**
- `create share items`, `edit share items`, `delete share items` (items are created and voided by billing services, not by users)
- `edit settlements`
- `delete share rules`
- `approve settlements` (used only as an any-of alternative)

These aren't bugs. They're noted so the design doesn't build them into the route mapping.

### 1.2 Real-role impact: **zero, confirmed**

| Role | Tenants | Users | Doctor Share permissions held |
|---|---|---|---|
| Super Admin | 7 | 8 | **all 14** |
| Hospital Administrator | 7 | 1 | **all 14** |
| every other role (49) | 7 | n/a | **none** |

No real role reaches any Doctor Share route through an over-broad permission. Tightening every route to a single permission removes nothing from anyone. **No backfill is needed.**

### 1.3 UI coupling (must change with the routes)

- **Sidebar** (`SidebarService.php:234-247`): gated `view share items | manage doctor shares`, `view settlements | manage …`, `view share reports | manage …`. This matches the candidate view permissions if `manage doctor shares` stays accepted (**W1-1**).
- **Settings entry** for the rates page (`SettingsSectionRegistry.php:55-62`, via `SettingsAccess::canAccessSection`): **any** of `manage doctor shares` or `view/create/edit/delete share rules` shows it. That's consistent with "view share rules" for the page.
- **Ungated action controls** (would show users a button that then 403s):
  - `rates/index.blade.php:30`: the Save form (`rates.sync`) has no `@can`.
  - `settlements/index.blade.php:8`: "New Settlement" (`settlements.preview`) has no `@can`.
  - `settlements/preview.blade.php:108`: the Create form (`settlements.store`) has no `@can`.

### 1.4 Existing tests

`tests/Feature/Permissions/DoctorSharePermissionsTest.php` has 2 tests: reports blocked with only `view share rules`, and the rates page allowed with `view share rules`. Both stay valid under the candidate mapping. Neither covers a write route.

---

## 2. SaaS billing / subscription (D4)

### 2.1 Routes

| Route | Method | Auth today | Effect |
|---|---|---|---|
| `billing.index` | GET | login | Plans and billing page |
| `billing.subscribe` | POST | **login only** | Free plan: switches `tenant.plan_id` immediately. Paid plan: creates a `pending` Subscription (landlord DB) and redirects to PayFast |
| `billing.payfast.success` | GET | **login only** | `BillingService::handleSuccess($id)`: marks the subscription **active**, records a **success payment**, sets `tenant.plan_id` |
| `billing.payfast.cancel` | GET | login only | `handleFailure($id)` |
| `subscription.index` | GET | login | Paddle subscription page |
| `subscription.activate` | POST | **login only** | Marks a Paddle subscription active, sets the plan, clears `trial_ends_at` |
| `billing.payfast.webhook` | POST | **public, CSRF-exempt** | `handleWebhook` → `handleSuccess` |
| `paddle.webhook` | POST | **public, CSRF-exempt** | subscription created/updated/**canceled**/activated, transaction completed |

**D4 as decided** (`manage subscription` on the authenticated routes) closes "any staff member can change the plan". The code shows more beyond that.

### 2.2 Payment-integrity findings (outside D4's scope, found while investigating it)

**Evidence for all four: code only.** None was executed, because they write to the landlord database and payment records.

| # | Severity | Finding | Who can trigger |
|---|---|---|---|
| **S1** | **CRITICAL** | **PayFast webhook has no signature or ITN verification.** It takes `basket_id = SUB-{id}-…` and `status` from the request body; `00`/`success`/`COMPLETED` calls `handleSuccess($id)`. That activates **any** subscription id (`Subscription::find`, no tenant scope), extends it a month, records a fake success payment and changes that tenant's plan. It never checks whether PayFast is enabled. | **Anyone, unauthenticated** |
| **S2** | **CRITICAL** | **Paddle webhook has no signature verification.** Forged `subscription.canceled` / `.activated` / `.updated` events change subscription status for whatever gateway id is supplied. The canceled handler sets `status => 'cancelled'`. | **Anyone, unauthenticated** (only while Paddle is enabled; it returns 400 otherwise) |
| **S3** | HIGH | `GET billing/payfast/success?subscription_id=N` trusts the query string: no payment check, **no tenant scoping**. A user can start a paid checkout (creating a pending subscription), skip paying, hit the success URL and get the paid plan. They can also activate another tenant's pending subscription by id. | any logged-in user (admins only, after D4) |
| **S4** | HIGH | `subscription.activate` (Paddle) falls back to the tenant's **current plan** when the API call fails or the transaction is unknown. It still marks the subscription **active**, extends it a month and **clears the trial**. It never checks that the transaction status is paid or that the transaction belongs to this tenant. | any logged-in user (admins only, after D4) |

**Local exposure:** all 5 gateways (`payfast`, `jazzcash`, `easypaisa`, `stripe`, `paddle`) are **disabled**. The landlord DB has 3 subscriptions (all `active`) and 2 paid plans. Even with PayFast disabled, S1 can still re-activate or extend existing subscriptions, since `handleWebhook` doesn't check whether the gateway is enabled. **Production gateway configuration is unknown to me.**

**What D4 alone achieves:** it narrows S3 and S4 to admins, but admins could still skip payment. It does nothing for S1 or S2, which need no login.

### 2.3 `manage subscription` backfill

- **The permission doesn't exist** in any tenant today.
- **Backfill target:** the Super Admin role (7 tenants, 8 users) and the Hospital Administrator role (7 tenants, 1 user), plus `RolePermissionSeeder` so new tenants match.
- **After this,** no other role (49 roles) can reach these routes. None of them should today either, so there's no legitimate loss.

---

## 3. Corrections to earlier reports

- **The parent investigation counted Doctor Share's 1 escalation** (`rates.sync` via `create share rules`). That still stands, but the 7 "view-via-write" read routes and `settlements.preview`/`store` also need single-permission scoping. That makes **all 10** Doctor Share routes change, not 1.
- **The parent investigation called SaaS billing M1 (MEDIUM, any staff can change plan).** With S1–S4 found, this area is **CRITICAL** overall.

---

## 4. Decisions needed before design

| # | Question | Recommendation |
|---|---|---|
| **W1-1** | Is `manage doctor shares` a coarse **view-only** grant (as D5 treats `view hr`) or a full **manage** superset? `MigrateCoarsePermissions` maps it to view+create+edit, not view-only. | Treat it as a **superset**, accepted alongside each action's own permission. Its name and its migration map both mean "manage", and only admins hold it. |
| **W1-2** | `rates.sync` deletes and recreates rows. Require `edit share rules` only, or all of create, edit and delete share rules? | **`edit share rules`.** The page is a single matrix "save"; clearing a cell is an edit of the matrix. |
| **W1-3** | `settlements.store` immediately settles money (there's no draft or approval step). Require `create settlements` or `approve settlements`? | **`approve settlements`.** The action is final and financial. `create settlements` alone would let a preparer finalize payouts. Only admins hold either today, so there's no impact. |
| **W1-4** | Should the billing and subscription **pages** (`billing.index`, `subscription.index`) also require `manage subscription`, or stay visible to all staff? | **Require it.** The pages show plan and payment status and exist only to start plan changes. |
| **W1-5** | S1–S4 are payment-integrity and webhook-authentication bugs, not role scoping. Handle them as an **emergency track now** (like C1–C4: verify webhook signatures and ITN, scope and verify the success callbacks), separately from Wave 1? Or fold them into Wave 1? | **Emergency track, before Wave 1 ships.** S1/S2 need no login, and RBAC can't touch them. It needs PayFast and Paddle verification details (merchant key/passphrase, webhook secret), so it needs its own design. |
