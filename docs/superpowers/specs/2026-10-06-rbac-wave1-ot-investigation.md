# RBAC Wave 1 (continued): Operation Theatre investigation

**Date:** 2026-10-06
**Status:** Findings only. **No code changes.**
**Code read:** `main` @ `6d891b3`.
**Parent:** `2026-10-03-rbac-permission-escalation-investigation.md`
**Rules:**
- D2: create does not imply view.
- D5: coarse permissions are view-only, except deliberate `manage …` supersets.
- D6: one permission per action, with before/after tests per route.
- AC-1: admins are backfilled only for seeder-intended abilities. Applied automatically.
- AD-1 (proposed): the post-write redirect falls back when the user can't view the list.

**Method:**
- Read `routes/web.php:943-1025`, `OTController`, `OperativeMonitoringController`, `resources/views/admin/ot/**`, `SidebarService.php:99-119`, `PermissionRegistry` and `RolePermissionSeeder`.
- Read-only SQL role scan of the 7 tenants.

---

## 1. Scope

| Group | Routes | Gate today | Verdict |
|---|---|---|---|
| **Surgeries** (calendar, conflicts, theatres, surgeries, lifecycle, monitoring) | **27** | `view\|create\|edit\|delete surgeries` on the whole group | **BUG (P1 + P3).** This wave's scope. |
| PAC | 7 | `manage pac` | OK. A single coarse superset (D5), not an escalation. |
| Surgical checklist | 4 | `manage surgical checklists` | OK (same) |
| OT consumables | 10 | `manage ot consumables` | OK (same) |
| Sterilization | 8 | `manage sterilization` | OK (same) |

**Authorization layers:** `OTController` and `OperativeMonitoringController` have **no** `authorize`/`can`/`abort_*` calls and no FormRequests. The route middleware is the only gate.

**Permissions that exist:** only `view`, `create`, `edit` and `delete surgeries`. There are **no theatre permissions** (**OT-1**), and `delete surgeries` gates nothing today: there's no destroy route.

## 2. Surgeries group: candidate mapping

| Route | Method | What it does | Candidate |
|---|---|---|---|
| `ot.calendar`, `ot.calendar.events` | GET | Schedule view and its JSON feed (patient names) | `view surgeries` |
| `ot.check-conflicts` | GET | Overlap check (JSON, **includes patient**) used only by the create and edit forms | `create surgeries\|edit surgeries` (§2.1) |
| `ot.theatres` | GET | Theatre list | `view surgeries` |
| `ot.theatres.create` / `.store` | GET/POST | New theatre (room configuration) | **OT-1** |
| `ot.theatres.edit` / `.update` | GET/PUT | Edit theatre, incl. status | **OT-1** |
| `ot.surgeries.index`, `.show` | GET | List / detail | `view surgeries` |
| `ot.surgeries.create` / `.store` | GET/POST | Schedule a surgery | `create surgeries` |
| `ot.surgeries.edit` / `.update` | GET/PUT | Edit a surgery | `edit surgeries` |
| `ot.surgeries.start` | POST | scheduled → in_progress (PAC + checklist gates) | `edit surgeries` |
| `ot.surgeries.complete` | POST | in_progress → completed, writes post-op diagnosis/notes, frees the OT | `edit surgeries` |
| `ot.surgeries.postpone` | POST | scheduled → postponed, optional new date | `edit surgeries` |
| `ot.surgeries.cancel` | POST | → cancelled (terminal, cannot be undone in the UI) | **OT-2** |
| `ot.monitoring.anaesthesia` / `.store-anaesthesia` | GET/POST | Intra-op anaesthesia record | `edit surgeries` |
| `ot.monitoring.vitals` / `.store-vitals` | GET/POST | Intra-op vitals entry | `edit surgeries` |
| `ot.monitoring.vitals-data` | GET | Vitals JSON (read) | `view surgeries` |
| `ot.monitoring.post-op` / `.store-post-op` | GET/POST | Post-op record | `edit surgeries` |

**The escalation today:**
- `view surgeries` alone can schedule, edit, start, complete, cancel and postpone surgeries, write anaesthesia, vitals and post-op records, and create or edit theatres.
- The parent doc counted 5 ESC and 8 POST-VIEW routes. The full count is **every write in the group: 14 write routes plus their 7 form pages.**

### 2.1 `check-conflicts`
It's a read-only helper that exists only for the create and edit forms. Under D2 a create-only user still needs it for the create form to work.
- The candidate accepts `create surgeries|edit surgeries`.
- This is an any-of across **two write verbs on a read-only helper**. It mutates nothing, and it shows only surgeries in the same theatre/time slot.
- I'm noting it as a deliberate exception to "one permission per action", not raising it as a decision.

## 3. Real-role impact (all 7 tenants)

| Role | Users | OT permissions held | Loses after scoping |
|---|---|---|---|
| Super Admin | 8 | all 4 surgery + all 4 `manage …` | nothing |
| Hospital Administrator | 1 | all 4 surgery + all 4 `manage …` | nothing |
| Nurse | 1 | `manage surgical checklists`, `manage ot consumables`, `manage sterilization` (no surgery permissions) | nothing (has no access to the surgeries group today) |
| all others | 18 | none | nothing |

**Zero real impact, confirmed. AC-1: no backfill.** The seeder gives HA all four surgery permissions, so every mapping choice for OT-1/OT-2 keeps HA whole. If OT-1 adds a new permission, the backfill to HA is automatic under AC-1, because the seeder already gives HA all of OT.

## 4. UI coupling

| View | Control | Change |
|---|---|---|
| `surgeries/show.blade.php:33` | Start | `@can('edit surgeries')` |
| `surgeries/show.blade.php:43` | Edit | `@can('edit surgeries')` |
| `surgeries/show.blade.php:165 / 187 / 204` | Complete / Cancel / Postpone forms | `edit` / OT-2 / `edit` |
| `surgeries/show.blade.php:346-360` | Anaesthesia / Vitals / Post-op links | `@can('edit surgeries')` |
| `calendar.blade.php:40` (the surgeries index has no create button) | Schedule surgery | `@can('create surgeries')` |
| `theatres/index.blade.php:10, 51` | New / Edit theatre | per OT-1 |
| `checklist/show.blade.php:35`, `consumables/usage.blade.php:86`, `pac/show.blade.php:219` | Back-link to the surgery, currently `@canany` of all 4 surgery permissions | `@can('view surgeries')`. The target page now needs view (D2). |

**Sidebar** (`SidebarService.php:102-104`): Theatres and Surgeries are already gated by `view surgeries`, so **no change**.

**Redirects (AD-1):**
- Theatre store/update go to `ot.theatres`, and surgery store goes to `ot.surgeries.index`. These are list pages and get the fallback.
- Surgery update and the monitoring stores go to `surgeries.show` (needs view), so they get the fallback too.
- The lifecycle actions use `back()`, so no change.

## 5. Existing tests

- `tests/Feature/OT/*` has 12 tests: workflow and PAC backfill. None covers surgery-route authorization.

## 6. Decisions needed before design

| # | Question | Recommendation |
|---|---|---|
| **OT-1** | No theatre permissions exist. Gate theatre create/edit on a **new `manage theatres`** permission, or reuse `create`/`edit surgeries`? | **New `manage theatres`** (list stays `view surgeries`, which scheduling needs). Theatres are room configuration, not surgeries. This follows the D3 precedent of giving taxes their own permissions instead of borrowing a neighbour's. It needs a registry and seeder entry plus a backfill to Super Admin and HA, which is automatic under AC-1. The backfill has to run **in the same deploy action** as the code, like `manage subscription`. The other option, reusing `edit surgeries`, means no backfill, but anyone allowed to edit a surgery could add or reconfigure theatres. |
| **OT-2** | `cancel` is terminal, with no destroy route. Gate it on `delete surgeries` (otherwise unused) or `edit surgeries`? | **`delete surgeries`.** It's the only end-of-life action, so it maps to the "remove" verb, the same reasoning that kept `close fiscal years` separate. Super Admin and HA hold it, so there's no impact. `postpone` stays `edit`. |
