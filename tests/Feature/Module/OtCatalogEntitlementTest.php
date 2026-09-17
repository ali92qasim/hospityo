<?php

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
});

it('hides the ot group when the module is on but the user has no ot item permissions', function () {
    $labels = collect(app(\App\Services\SidebarService::class)->build(
        otCatalogUser([]),
        otCatalogTenant(['ot', ...\App\Models\ModuleRegistry::OT_CHILD_SLUGS])
    ))->pluck('label');

    expect($labels)->not->toContain('Operation Theatre');
});

it('shows ot inventory for nurse manage ot consumables without view surgeries', function () {
    $group = collect(app(\App\Services\SidebarService::class)->build(
        otCatalogUser(['manage ot consumables']),
        otCatalogTenant(['ot', 'ot.consumables'])
    ))->firstWhere('id', 'ot');

    expect($group)->not->toBeNull()
        ->and(collect($group['items'])->pluck('label')->all())->toBe(['OT Inventory']);
});

it('shows theatres without showing inventory when user only has view surgeries', function () {
    $group = collect(app(\App\Services\SidebarService::class)->build(
        otCatalogUser(['view surgeries']),
        otCatalogTenant(['ot', ...\App\Models\ModuleRegistry::OT_CHILD_SLUGS])
    ))->firstWhere('id', 'ot');

    expect(collect($group['items'])->pluck('label')->all())->toBe(['Theatres', 'Surgeries', 'PAC Requests']);
});

it('forbids consumables index without manage ot consumables even with view surgeries', function () {
    otCatalogTenant(['ot', 'ot.consumables']);
    $this->actingAs(otCatalogUser(['view surgeries']));

    $this->get(route('ot.consumables.index'))->assertForbidden();
});

it('allows consumables index with manage ot consumables and the child slug', function () {
    otCatalogTenant(['ot', 'ot.consumables']);
    $this->actingAs(otCatalogUser(['manage ot consumables']));

    $this->get(route('ot.consumables.index'))->assertOk();
});

it('allows sterilization index with manage sterilization without view surgeries', function () {
    otCatalogTenant(['ot', 'ot.sterilization']);
    $this->actingAs(otCatalogUser(['manage sterilization']));

    $this->get(route('ot.sterilization.index'))->assertOk();
});

it('still allows pac index with view surgeries before the pac route split', function () {
    otCatalogTenant(['ot', 'ot.pac']);
    $this->actingAs(otCatalogUser(['view surgeries']));

    $this->get(route('ot.pac.index'))->assertOk();
});
