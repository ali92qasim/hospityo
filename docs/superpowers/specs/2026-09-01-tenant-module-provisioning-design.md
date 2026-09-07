# Tenant module permission provisioning

**Date:** 2026-09-01  
**Status:** specified from operator prompt; implement after Track 3 merge  
**Related:** `2026-09-01-demo-hospital-rbac-hotfix.md`, `2026-09-01-backup-sidebar-drift-investigation.md`

## Problem

SaaS plan JSON (`plans.modules`) and tenant Spatie RBAC are two layers. Super-admin ticking a module (or `changePlan` / `activate`) only writes Layer 1. It never creates permission rows or assigns them to default roles.

That is how `demo-hospital` became a live clinic with an enterprise plan and zero roles/permissions: convert/activate/changePlan skipped provisioning. It is also why ticking Backup on a plan does not show the sidebar item until Spatie names exist on the logged-in role.

`tenants:sync-permissions` already re-runs `RolePermissionSeeder` (including `syncPermissions`, which resets default roles to the catalog). It is the nuclear reset. This track adds an **additive, module-scoped grant** with an explicit confirm on hospitals that already have RBAC.

## Goals

1. **Auto-grant** when a tenant is new (SeedTenantData) or when plan assignment/activation hits a tenant with **empty RBAC** (no roles). Same outcome as a successful `SeedTenantData` RBAC step: catalog permissions + default roles.
2. **Prompt-to-grant** when an **existing** hospital’s entitled modules grow (tenant `changePlan`, or plan checkbox update that adds slugs). Plan JSON still saves; Spatie grants wait for confirm.
3. **Idempotent and additive:** second run adds nothing; custom extra permissions on any role are never revoked.
4. **Live proof:** run the new command on `demo-hospital` only. After Track 1 it already has 1 user / 7 roles / 220 permissions — expect a no-op (0 new grants) unless the catalog is ahead of that database.

## Non-goals

- Unified UI catalog (Track 4).
- Creating tenant users or issuing passwords (Track 1 already created `demo@gmail.com`).
- Replacing `tenants:sync-permissions` (keep it as full catalog reset).
- Granting custom (non-default) roles.
- Per-tenant module JSON overrides.

## Auto vs prompt

| Situation | Layer 1 (plan) | Layer 2 (Spatie) |
|---|---|---|
| New tenant `TenantProvisioningService` | plan_id set | Auto: existing onboarding seeder, then additive grant for plan modules (no-op if seeder already assigned them) |
| `activate` / `changePlan` and `roles` count is 0 | status/plan written as today | Auto: run `RolePermissionSeeder` (create catalog + default roles) |
| `changePlan` and roles already exist, new plan adds modules | plan written | **Do not grant** unless `grant_permissions=1`. Flash a Grant action if skipped. |
| `changePlan` and roles exist, no added modules | plan written | No grant |
| Plan `update` adds modules and the plan has hospitals | JSON saved | Flash/confirm bulk grant for those added modules on each tenant; do not grant until confirmed |
| Plan `update` removes modules | JSON saved | **Do not revoke** Spatie (CheckModule already hides the feature) |

Empty RBAC is detected as `roles` table count `0` on the tenant connection after `makeCurrent()`.

## Grant algorithm (existing RBAC)

`App\Services\TenantModuleProvisioner::grant(Tenant $tenant, array $modules): GrantResult`

1. `makeCurrent()`, set Spatie cache key `spatie.permission.cache.tenant.{id}`, forget cache.
2. `firstOrCreate` each permission string in `RolePermissionSeeder` catalog (so new names exist).
3. `firstOrCreate` default role names from the seeder.
4. For each module slug in `$modules` (already `ModuleRegistry::normalize`d):
   - Resolve Spatie names via `PermissionRegistry` groups for that slug (empty list if the registry has no group — skip, do not invent names).
   - Super Admin: `givePermissionTo` any missing names (additive).
   - Other default roles: `givePermissionTo` missing names that also appear in that role’s seeder list (`ROLE_PERMISSIONS` intersection).
5. Forget Spatie cache. `forgetCurrent()`.
6. Return counts: permissions created, grants added, already present.

Never call `syncPermissions` from this service.

## Super-admin UX

Reuse `confirm-dialog.js`.

**Tenant change plan:** if the selected plan adds modules vs current, confirm copy lists those module display names and two outcomes — Apply + Grant (`grant_permissions=1`) vs cancel (no POST). A follow-up “Grant now” on the flash covers the case where they applied earlier without grant (hidden field `grant_permissions=0` is not required on first version if cancel means no plan change; **prefer:** apply plan always on the existing Apply button, then if modules were added show flash with POST to `super-admin.tenants.grant-modules`).

Locked choice for this pass:

- **Apply plan always** (today’s `changePlan` body).
- If auto-empty: grant immediately.
- If existing + added modules: success flash includes a **Grant permissions** button posting the added slugs. Skipping leaves CheckModule entitled and Spatie unchanged.

**Plan edit:** after save, if slugs were added and `tenants_count > 0`, same flash with bulk grant for those slugs across that plan’s tenants.

## Command

```
php artisan tenants:provision-modules --tenant=demo-hospital
php artisan tenants:provision-modules --tenant=demo-hospital --grant
```

Without `--grant`: print what would be added (dry run).  
With `--grant`: apply.  
`--tenant` is required for live use in this pass (omit-all is easy to abuse; add later if needed).

## Live proof (demo-hospital only)

1. Read-only counts (expect Track 1 after-state).
2. Dry run, then `--grant`.
3. Re-count: users/roles/permissions/`model_has_roles` unchanged if already seeded; Hassan Health Care Centre unchanged.
4. Do not pass any other slug.

## Tests

- Empty tenant: grant (or changePlan auto) creates seeder permissions and default roles; second grant adds 0.
- Existing tenant: extra custom permission on Hospital Administrator survives grant.
- Existing tenant changePlan to a plan with a new module without grant: plan_id changes, Hospital Administrator does not gain that module’s Spatie names.
- Same with grant POST: those names appear on Super Admin and Hospital Administrator (intersection).
- Plan update adding `backup` with a tenant: no Spatie write until grant endpoint.
- Dry-run command does not write.

## Out of scope

Hassan’s extra permission rows vs demo-hospital (222 vs 220) — do not copy them. Catalog grant only.
