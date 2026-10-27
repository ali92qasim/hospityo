# RBAC Wave 2: Wards / Beds investigation

**Date:** 2026-10-12  
**Status:** Findings only. **No code changes.** Design comes after your confirmation of §6 (especially the **Nurse** decision).  
**Code read:** tip `06dd82c` + docs on `feat/rbac-wave2-departments-hr-wards` (`../saasy-rbac-wave2`).  
**Precedent:** Departments investigation/design; HR investigation/design.  
**Wave position:** Wave 2 module 3 of 3 (Departments ✓ → HR ✓ design → **Wards/Beds**).

**Rules (carry forward):**
- D2: create does not imply view.
- D6: one permission per action.
- AC-1: seeder-intended abilities may be backfilled; any-of-only abilities are closed — **except Nurse ward/bed CRUD is your decision (not auto-closed).**
- AD-1: `RedirectsAfterWrite` after successful writes.
- Spatie permissions only. No role-name checks.

**Method:**
- `routes/web.php:593–594` resource middleware; `WardController` / `BedController`; index/create/edit blades; `SidebarService`; `PermissionRegistry` / `ModuleRegistry` / `RolePermissionSeeder`.
- Read-only SQL across 7 tenant DBs (tenant connection). No writes.

**In scope:** `wards.*` and `beds.*` resource routes.  
**Out of scope:** IPD visit admission bed assignment (`visits.*` / AdmitPatientRequest); reports IPD occupancy; HR; Departments.

---

## 1. Inventory

| Resource | Routes | Controller | Current middleware (any-of) |
|---|---|---|---|
| `wards` | 6 resource (no show) | `WardController` | `view\|create\|edit\|delete wards` |
| `beds` | 6 resource (no show) | `BedController` | `view\|create\|edit\|delete beds` |
| **Total** | **12** resource (+1 status = **13** in scope) | | |

**Note:** Controllers implement index/create/store/edit/update/destroy only — **no `show` action**. Dead `wards.show` / `beds.show` are **removed** (no app/Blade callers; `Route::has` false).

**Authorization layers:** Controllers have no `authorize` / `can`. FormRequests only `auth()->check()`. Resource middleware is the only real gate.

**Escalation today:** any one of the four permissions for that resource opens **every** CRUD action (same Departments pattern).

**Module / sidebar (likely untouched):**
- `ModuleRegistry` IPD module routes `wards.`, `beds.`.
- Sidebar: `view wards` / `view beds` + `ipd` module.

**Registry:** four ward + four bed permissions already listed. No new names required for D6 scoping.

---

## 2. Candidate mapping (shape for design)

| Route | Candidate |
|---|---|
| `wards.index` | `view wards` |
| `wards.create` / `wards.store` | `create wards` |
| `wards.edit` / `wards.update` | `edit wards` |
| `wards.destroy` | `delete wards` |
| `beds.index` | `view beds` |
| `beds.create` / `beds.store` | `create beds` |
| `beds.edit` / `beds.update` | `edit beds` |
| `beds.destroy` | `delete beds` |

No coarse `view ipd`-style read-superset on these routes.

**Writes** all redirect to `wards.index` / `beds.index` → AD-1 via `RedirectsAfterWrite`.

**UI:** index blades have **no `@can`** — Add / Edit / Delete render for anyone who can open the list.

---

## 3. Seeder intent

| Role | Wards | Beds | Seeder lines |
|---|---|---|---|
| Super Admin | all four | all four | `Permission::all()` |
| Hospital Administrator | `view`, `create`, `edit` — **not** `delete` | same | **322–323** |
| Nurse | **`view wards` only** | **`view beds` only** | **409** |
| every other seeded role | none | none | — |

Global `PERMISSIONS` list still defines `delete wards` / `delete beds` (lines 69–76) for Super Admin.

---

## 4. Live data (7 tenants, read-only)

| Role | Tenants with role holding these perms | Users (sum) | Permissions held (live = seeder) |
|---|---|---|---|
| Super Admin | 7 | **8** (sgh has 2; others 1) | all eight ward+bed |
| Hospital Administrator | 7 (role present) | **1** (demo-hospital only) | view/create/edit wards + beds; **no** deletes |
| Nurse | 7 (role present) | **1** (hassan-health-care-centre only) | **view wards, view beds only** |
| every other role | 0 | 0 | none |

No direct user-level grants of any ward/bed permission in any tenant.

**What the any-of bug means in practice today:**

| Actor | Seeder intent | What middleware allows today |
|---|---|---|
| HA (1 user) | view/create/edit; no delete | **full CRUD including destroy** |
| Nurse (1 user) | **view only** | **full CRUD including create/edit/destroy** |
| Super Admin (8 users) | all | all (unchanged after scoping) |

---

## 5. Escalations vs AC-1 (recommend only where allowed)

### 5a. Hospital Administrator destroys — Departments precedent

| # | Routes | Through today | Seeder | Recommendation |
|---|---|---|---|---|
| W1 | `wards.destroy` | CRUD any-of (`view`/`create`/`edit wards`) | withholds `delete wards` (322) | **Close** (AC-1), same as DEP-2 |
| W2 | `beds.destroy` | CRUD any-of | withholds `delete beds` (323) | **Close** (AC-1) |

No backfill if closed. Live matches seeder.

### 5b. Nurse ward/bed CRUD — **YOUR decision (do not auto-close)**

Nurse holds only `view wards` / `view beds` in both seeder and live DB, yet today can hit every ward and bed write route because of resource any-of.

Local blast radius if closed: **1 Nurse user** (hassan-health-care-centre). Role exists with 0 users in the other 6 tenants.

| Option | Outcome after scoping | Seeder / backfill | Notes |
|---|---|---|---|
| **A — Close (match seeder)** | Nurse keeps list/show only; loses create/edit/destroy for wards and beds | **No** seeder change; **no** backfill | AC-1 default for HA deletes; applies the same rule to Nurse writes that exist only via the bug |
| **B — Expand Nurse to operational CRUD (no delete)** | Grant Nurse `create`+`edit` for wards and/or beds (pick which) | **Seeder change + backfill** for Nurse | Product says nurses manage ward/bed setup day-to-day; deletes stay SA/HA policy |
| **C — Expand Nurse including deletes** | Grant full four on wards and/or beds | **Seeder change + backfill** | Rare; usually admins own deletes |
| **D — Asymmetric** | e.g. beds create/edit for Nurse, wards view-only (or inverse) | Seeder + backfill for the grants you choose | If nurses adjust beds but not ward metadata |

**Recommend (not decide):** **A** if ward/bed master data is an admin job (matches current seeder text); **B** (beds create/edit, wards view-only or both create/edit, still no delete) only if production nurses rely on the accidental CRUD. Live data shows **one** Nurse user — ask that tenant’s workflow before expanding.

---

## 6. Decisions for confirmation

| # | Question | Recommendation |
|---|---|---|
| **WB-1** | Scope all 14 `wards.*` / `beds.*` routes to one permission per action as in §2? | **Yes.** |
| **WB-2** | Close HA `wards.destroy` / `beds.destroy` with no backfill (AC-1, Departments precedent)? | **Yes — automatic.** |
| **WB-3** | **Nurse** ward/bed CRUD: Option A / B / C / D from §5b? | **Your call — not auto-closed.** |
| **WB-4** | Apply AD-1 via `RedirectsAfterWrite` on store/update/destroy? | **Yes — automatic.** |
| **WB-5** | Gate index action controls with `@can`? | **Yes.** |
| **WB-6** | Leave IPD module / sidebar `view wards`/`view beds` checks unchanged? | **Yes.** |

---

## 7. Proposed next step (after confirm)

Write the Wards/Beds **design** from confirmed WB-1…WB-6 (especially Nurse option), then produce the **combined Wave 2 implementation plan** (Departments + HR + Wards/Beds). No code until that plan is approved.
