# OPD / IPD / Emergency HTTP identity — reliable resolution

**Date:** 2026-09-20  
**Status:** confirmed 2026-09-20 (Q1–Q4 accepted; P1 Option A; P4 Option B). Implementation plan: `docs/superpowers/plans/2026-09-20-opd-ipd-emergency-http-identity.md`.  
**Method:** Superpowers brainstorming (design after Phase 0). Builds on `2026-09-20-opd-ipd-emergency-http-identity-investigation.md`. Does not re-open that inventory.

**Related**

- `docs/superpowers/specs/2026-09-20-opd-ipd-emergency-http-identity-investigation.md` — shared `visits.*` family; `moduleForRequest` sources; 187 `route('visits…')` blast radius; `test-orders.*` fallback to `visits`; admit/triage unguarded; IPD sidebar `ipd+visits` vs middleware `ipd`-only; CTI half-landed.
- `docs/superpowers/specs/2026-09-01-ungated-module-ui-surfaces.md` / `2026-09-07-slice-3-ungated-ui-visibility-design.md` — UI gating vs CheckModule.
- `docs/superpowers/specs/2026-09-01-parent-route-module-entitlement-design.md` — nested module aborts (lab/imaging/pharmacy); unchanged by this design.
- `docs/superpowers/specs/2026-08-10-visit-domain-separation-design.md` — CTI / handlers (out of scope here except “do not touch ceremonial bridge”).

---

# Locked principles (do not reopen)

| ID | Principle |
|---|---|
| L1 | **No route renaming.** All ~34 `visits.*` and 2 `test-orders.*` names stay exactly as registered today. |
| L2 | **Primary target:** reliable identity resolution for CheckModule and type-specific actions. Close two confirmed gaps: (a) `test-orders.*` falling through to module `visits` for IPD-owned orders; (b) `admit` / `triage` with no visit-type guard. |
| L3 | **Resolve IPD sidebar-vs-middleware asymmetry** (`ipd`+`visits` vs `ipd`-only) in this design. |
| L4 | **Broken routes** (`visits.destroy`, `visits.order-test`) and **duplicate** `visits.order-multiple-imaging-studies` registration are a **separate fix-first task**, outside this design’s risk envelope. Sequence them before or beside identity work in planning — not as identity scope. |
| L5 | **Do not touch** ceremonial CTI bridge: `dual_write_legacy_columns`, `read_from_child`, `VisitClassHistory`, or `syncLegacyToChild` stub behavior. Separate technical debt. |

---

# Findings (from investigation — not re-proven here)

1. Bound `{visit}` routes already feed `moduleForRequest` from **`$visit->visit_type`** (spine). List/create/data/quick-register use **query/body** with **default → module `visits` (OPD)**.
2. `test-orders.remove` / `test-orders.result` bind `{testOrder}` only. `TestOrder` has `visit_id` + `visit()` belongsTo. Today CheckModule usually never sees a Visit → **defaults to `visits`**.
3. `visits.admit` / `visits.triage` share names across types; methods have **no** `visit_type` guard (`admit` IPD-oriented, `triage` emergency-oriented).
4. Emergency sidebar: module **`emergency` only** (+ Spatie `view visits`). IPD “Admitted Patients”: modules **`ipd` AND `visits`**. Middleware for IPD visit URLs: **`ipd` only**.
5. No dedicated **IPD-list** `CheckModule` feature test (Emergency/OPD list covered; IPD mapping unit-only).
6. Doctor Share / bill calculate already use Visit/bill truth — **out of scope** to change.

---

# Proposal

## P0. Sequencing note (not identity scope)

**Fix-first (separate task, before or parallel to identity implementation planning):**

- Remove or implement `visits.destroy` / `visits.order-test` registrations.
- Deduplicate `visits.order-multiple-imaging-studies`.

Do not fold their risk into identity PR review criteria.

---

## P1. `test-orders.*` — resolve owning visit type before CheckModule

### Problem (finding)

`visitTypeFromRequest` never loads `TestOrder→visit`, so IPD (and Emergency) legacy test-order mutations are plan-gated as **OPD/`visits`**.

### Design fork (options)

| Option | Mechanism | Pros | Cons |
|---|---|---|---|
| **A — Resolve via bound `TestOrder` (recommended)** | In `visitTypeFromRequest` (or a tiny helper it calls): if route param `testOrder` is a `TestOrder` model (or id → load), use `$testOrder->visit?->visit_type` (eager/lazy `visit`), then fall back to existing query/body/default chain. | Matches how `{visit}` already works; **no client trust**; no route rename (L1); one place CheckModule already calls | Middleware path does a relation read; must handle missing visit (see open Q1) |
| **B — Require explicit `visit_type` on request** | Form/query must send `visit_type`; whitelist; CheckModule stays request-based | No relation load in middleware | **Forgeable**; every Blade/JS caller of remove/result must pass type; easy to get wrong vs DB; fights L2 “reliable” |
| **C — Dedicated middleware before CheckModule** | e.g. `ResolveTestOrderVisitType` sets a request attribute from `TestOrder→visit` | Clear separation | Extra middleware registration; duplicates logic Option A can keep inside `visitTypeFromRequest` |

### Recommendation

**Option A.** Extend the existing resolution chain only:

1. Bound `Visit` object (unchanged)  
2. **New:** bound `TestOrder` → `visit.visit_type` (whitelist after read)  
3. Query `visit_type`  
4. Input `visit_type`  
5. `null` → default module `visits`

Keep route names and URIs unchanged (L1). Prefer loading through the already-bound model when the container has it (same timing as Visit binding relative to CheckModule today).

### Tests (this gap)

- Feature: IPD visit owns a `TestOrder`; tenant has **`ipd`**, lacks **`visits`** → `test-orders.result` / `remove` **allowed** by CheckModule (module `ipd`).  
- Feature: same order; tenant has **`visits`**, lacks **`ipd`** → **403** with standard CheckModule copy.  
- Feature: OPD-owned order still maps to `visits`.  
- Unit: `moduleForRequest` with mocked route param `testOrder`.

---

## P2. `admit` / `triage` — explicit visit-type guards

### Problem (finding)

Shared routes; wrong-type Visit can hit IPD admit or Emergency triage without a clear rejection.

### Proposal

**Controller-level early guard** (same style as `IpdClinicalService::ensureIpdVisit`), not a new middleware and not route renaming:

| Route | Required spine type | On mismatch |
|---|---|---|
| `visits.admit` | `ipd` | Abort **403** (or **422** — see open Q2) with explicit message: action requires an IPD visit |
| `visits.triage` | `emergency` | Same pattern for Emergency |

**Why controller (or thin private `assertVisitType(Visit, string)` on the controller / a tiny domain helper), not Form Request alone:** admit/triage already use Form Requests for payload; type identity is about the **bound Visit**, not request body. A shared `assertVisitType` keeps care-team-style gates consistent.

**Why not middleware:** would need a route→expected-type map for only two actions; controller colocates with existing IPD gates.

**Does not** change route names (L1). Workflow UI should already only expose these controls for the right type; guard is the hard backstop.

### Tests

- Feature: OPD (and Emergency) visit POST `visits.admit` → rejected; no admission row.  
- Feature: OPD (and IPD) visit POST `visits.triage` → rejected; no triage row.  
- Feature: matching type still succeeds (existing happy paths remain green).

---

## P3. List / create / data / quick-register — trusting `visit_type` without rename

### Problem (finding)

No bound Visit; identity is request input. `require_typed_visit_routes` already redirects index → `?visit_type=opd` and empties data without type; create redirects to `opd`; quick-register validates `opd|emergency` only.

### Proposal (preserve names — L1)

| Entry | Trust rule |
|---|---|
| `visits.index` | Whitelist `opd\|ipd\|emergency` only. Missing + `require_typed_visit_routes` → **redirect** to `visits.index` with `visit_type=opd` (keep current behavior). Invalid string → treat as missing (same as today). CheckModule continues to use query via `visitTypeFromRequest`. |
| `visits.data` | Same whitelist; missing → empty DataTables payload (keep current). Never invent type from “guesses” beyond whitelist. |
| `visits.create` | Whitelist required; missing → redirect `visit_type=opd`; invalid → redirect/404 as today. View still `admin.visits.create.{type}`. |
| `visits.quick-register` | Keep validated `opd\|emergency` only (**no IPD** quick-register in this design — see open Q3). Body `visit_type` is the CheckModule source (no Visit yet) — acceptable for create-time only because the value is also persisted onto the new Visit in the same request. |

**Explicit non-goals:** new route names; removing query `visit_type`; making list URLs path-typed (`/opd/visits`).

**Hardening (in scope):** ensure invalid `visit_type` never passes the whitelist into `moduleForRequest` (already true for query/input; keep it). Do not add silent coercion of typos to OPD inside CheckModule beyond the existing `default => 'visits'` when type is **absent** — invalid present values should not become OPD module entitlement by accident (today invalid query is nulled in `index`/`create` before module sees… **Open Q4** if CheckModule runs with raw invalid query on other routes).

### Tests

- Feature: `visits.index?visit_type=ipd` with `ipd` entitled / `visits` not → **allowed** after P4 (and covered by P5).  
- Feature: `visits.index` without type → redirect includes `visit_type=opd`.  
- Feature: quick-register `emergency` without emergency module → **403**.  
- Feature: quick-register `ipd` → validation failure (unchanged contract unless Q3 changes it).

---

## P4. IPD sidebar vs middleware asymmetry

### Problem (finding)

Sidebar “Admitted Patients” requires **`ipd` + `visits`**. CheckModule on IPD visit URLs requires **`ipd` only**. Emergency sidebar requires **`emergency` only** (+ Spatie `view visits`).

### Design fork

| Option | Direction | Pros | Cons |
|---|---|---|---|
| **A — Tighten middleware** | IPD visit routes require **both** `ipd` and `visits` | Matches current sidebar; max restriction | Unique dual-module CheckModule path; Emergency stays single-module → **inconsistent product model**; tenants with `ipd` wards but no `visits` lose deep links they can hit today |
| **B — Relax sidebar (recommended)** | “Admitted Patients” requires **`ipd` only** (+ existing Spatie `view visits`), like Emergency’s **`emergency` only** (+ `view visits`) | **Aligns with Emergency**; matches middleware already; one plan slug = one clinical product line for visit lists | Tenants that have `ipd` without `visits` gain sidebar Admitted Patients (still need `view visits` Spatie) |

### Recommendation

**Option B — relax sidebar to `ipd`-only** for the Admitted Patients item (and keep Wards/Beds as `ipd`-only).

**Reasoning:** Emergency already defines the product pattern: **clinical type module owns the typed visit list**, Spatie `view visits` is the shared capability flag, and CheckModule remaps `visits.*` + type → that module. Forcing `visits` (OPD module) onto IPD list visibility couples IPD to OPD entitlement without middleware backing — the asymmetry the investigation called out.

**Unchanged:** OPD list still requires module `visits`. Emergency list still requires module `emergency`. Middleware mapping unchanged for IPD (`visit_type=ipd` → `ipd`).

### Tests

- Update `SidebarVisitNavigationTest`: Admitted Patients appears with `['ipd']` + `view visits`; **does not** require `visits` on the plan.  
- Regression: OPD/Emergency sidebar cases unchanged.  
- Pair with P5 CheckModule IPD list tests.

---

## P5. Test coverage plan (mandatory)

| Gap | Required coverage |
|---|---|
| IPD list CheckModule | Feature: `visits.index?visit_type=ipd` allowed with `ipd` only; **403** without `ipd` even if `visits` present; OPD list still needs `visits` |
| `test-orders.*` ownership | Feature cases under P1 |
| admit / triage guards | Feature cases under P2 |
| Sidebar IPD | Update under P4 |
| Unit | `moduleForRequest` with `testOrder` param; existing query unit tests remain |

Do **not** leave IPD list gating unit-only.

---

# Out of scope (explicit)

- Renaming or splitting `visits.*` / `test-orders.*` (L1).  
- Ceremonial CTI bridge (L5).  
- Doctor Share / bill_type calculate paths (already Visit/bill reliable).  
- Adding IPD to patients-index quick-register (unless Q3 decides otherwise).  
- Fixing destroy / order-test / duplicate imaging route (L4 — separate task).  
- Implementation plan / task breakdown (next pass after approval).

---

# Open questions

| ID | Question | Default if unanswered |
|---|---|---|
| **Q1** | If `TestOrder` has null/missing `visit`, should CheckModule **403**, or fall through to query/default `visits`? | **403** (fail closed) |
| **Q2** | Admit/triage mismatch: **403** (authorization/module family) vs **422** (validation)? | **403** with clear message (consistent with wrong-module aborts) |
| **Q3** | Should this design add IPD quick-register from patients index, or keep `opd\|emergency` only? | **Keep opd\|emergency only** |
| **Q4** | Should `visitTypeFromRequest` reject **invalid** non-null `visit_type` strings (not in whitelist) as “no type” for module purposes, vs passing through bound Visit’s raw DB value only? | Whitelist query/input; bound Visit/TestOrder→visit use DB value if in `{opd,ipd,emergency}`, else fail closed |

---

# Self-review

- Placeholders: none beyond numbered open questions.  
- Consistency: L1 preserved throughout; P4 Option B matches Emergency; P1 Option A mirrors bound Visit.  
- Scope: identity + sidebar asymmetry + tests; L4/L5 carved out.  
- Ambiguity: admit/triage expected types stated; test-orders resolution order stated; Q1–Q4 flagged for product confirm.

---

**End of design. Please review and confirm (including Q1–Q4 or accept defaults) before the implementation-plan pass.**
