<?php

use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\ModuleRegistry;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;

/*
|--------------------------------------------------------------------------
| Accounting route scoping (RBAC wave 1, Task 1)
|--------------------------------------------------------------------------
| Each of the 17 accounting routes accepts exactly one action permission.
| `view accounting` is accepted on read GETs only (D5).
*/

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);

    acScopeTenant();

    $owner = User::create([
        'name' => 'Accounting Fixture Owner',
        'email' => 'ac-scope-owner-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->cash = Account::create(['code' => '1100', 'name' => 'Cash in Hand', 'type' => 'asset']);
    $this->bank = Account::create(['code' => '1300', 'name' => 'Bank Account', 'type' => 'asset']);
    $this->revenue = Account::create(['code' => '4100', 'name' => 'OPD Revenue', 'type' => 'revenue']);

    $this->fy = FiscalYear::create([
        'name' => 'FY 2025-26',
        'start_date' => '2025-07-01',
        'end_date' => '2026-06-30',
        'is_active' => true,
        'is_closed' => false,
    ]);

    $this->entry = JournalEntry::create([
        'entry_date' => '2026-03-15',
        'description' => 'Manual fixture entry',
        'created_by' => $owner->id,
        'is_auto' => false,
        'entry_type' => 'original',
    ]);
    $this->entry->lines()->create(['account_id' => $this->cash->id, 'debit' => 1000, 'credit' => 0, 'narration' => 'Fixture debit']);
    $this->entry->lines()->create(['account_id' => $this->revenue->id, 'debit' => 0, 'credit' => 1000, 'narration' => 'Fixture credit']);
});

function acScopeTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    // The 6 report routes are gated by their own child module slugs
    // (CheckModule), so the plan must include them as well as `accounting`.
    $modules = ['accounting', ...ModuleRegistry::ACCOUNTING_CHILD_SLUGS];
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function acScopeUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Accounting Scope User',
        'email' => 'ac-scope-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

/** Resolve the dataset's fixture names to route parameters. */
function acScopeParams($test, array $fixtures): array
{
    $map = [
        'account' => fn () => ['account' => $test->cash->id],
        'entry' => fn () => ['journalEntry' => $test->entry->id],
        'fy' => fn () => ['fiscalYear' => $test->fy->id],
    ];

    $params = [];
    foreach ($fixtures as $fixture) {
        $params += $map[$fixture]();
    }

    return $params;
}

/** A valid payload for each write route, so a request that passes the gate really writes. */
function acScopePayload($test, string $route): array
{
    return match ($route) {
        'accounting.store-account' => ['code' => '1400', 'name' => 'Petty Cash', 'type' => 'asset'],
        'accounting.update-account' => ['code' => '1100', 'name' => 'Cash Renamed', 'type' => 'asset', 'is_active' => true],
        'accounting.process-deposit' => [
            'to_account_id' => $test->cash->id,
            'from_account_id' => $test->revenue->id,
            'amount' => 500,
            'date' => '2026-03-20',
            'description' => 'Scoping deposit',
        ],
        'accounting.process-transfer' => [
            'from_account_id' => $test->cash->id,
            'to_account_id' => $test->bank->id,
            'amount' => 200,
            'date' => '2026-03-20',
            'description' => 'Scoping transfer',
        ],
        'accounting.store-journal-entry' => [
            'entry_date' => '2026-03-20',
            'description' => 'Scoping journal entry',
            'lines' => [
                ['account_id' => $test->cash->id, 'debit' => 300, 'credit' => 0],
                ['account_id' => $test->revenue->id, 'debit' => 0, 'credit' => 300],
            ],
        ],
        'accounting.update-journal-entry' => [
            'entry_date' => '2026-03-15',
            'description' => 'Changed journal entry',
            'lines' => [
                ['account_id' => $test->bank->id, 'debit' => 750, 'credit' => 0],
                ['account_id' => $test->revenue->id, 'debit' => 0, 'credit' => 750],
            ],
        ],
        'accounting.fiscal-years.close' => ['confirmation' => 'CLOSE FY 2025-26'],
        default => [],
    };
}

function acScopeRequest($test, string $method, string $route, array $fixtures)
{
    $url = route($route, acScopeParams($test, $fixtures));

    return $method === 'get'
        ? $test->get($url)
        : $test->{$method}($url, acScopePayload($test, $route));
}

/** Snapshot of the fixture JE's lines, for "unchanged" assertions. */
function acScopeLines(JournalEntry $entry): array
{
    return JournalEntryLine::where('journal_entry_id', $entry->id)
        ->orderBy('id')
        ->get(['account_id', 'debit', 'credit', 'narration'])
        ->map(fn ($line) => [$line->account_id, (float) $line->debit, (float) $line->credit, $line->narration])
        ->all();
}

// ── (a) Deny: a wrong-verb holder that passes today must get 403 ──────────

dataset('accounting escalations', [
    'create-account via view CoA' => ['get', 'accounting.create-account', [], ['view chart of accounts']],
    'store-account via view accounting' => ['post', 'accounting.store-account', [], ['view accounting']],
    'edit-account via create CoA' => ['get', 'accounting.edit-account', ['account'], ['create chart of accounts']],
    'update-account via view CoA' => ['put', 'accounting.update-account', ['account'], ['view chart of accounts']],
    'chart list via create CoA' => ['get', 'accounting.chart-of-accounts', [], ['create chart of accounts']],
    'deposit form via view deposits' => ['get', 'accounting.deposit', [], ['view deposits']],
    'process-deposit via view accounting' => ['post', 'accounting.process-deposit', [], ['view accounting']],
    'transfer form via view transfers' => ['get', 'accounting.transfer', [], ['view transfers']],
    'process-transfer via view accounting' => ['post', 'accounting.process-transfer', [], ['view accounting']],
    'JE list via create JE' => ['get', 'accounting.journal-entries', [], ['create journal entries']],
    'create JE via view JE' => ['get', 'accounting.create-journal-entry', [], ['view journal entries']],
    'store JE via view accounting' => ['post', 'accounting.store-journal-entry', [], ['view accounting']],
    'edit JE via create JE' => ['get', 'accounting.edit-journal-entry', ['entry'], ['create journal entries']],
    'update JE via view JE' => ['put', 'accounting.update-journal-entry', ['entry'], ['view journal entries']],
    'FY list via close FY' => ['get', 'accounting.fiscal-years', [], ['close fiscal years']],
    'pre-close via view FY' => ['get', 'accounting.fiscal-years.pre-close', ['fy'], ['view fiscal years']],
    'close via view FY' => ['post', 'accounting.fiscal-years.close', ['fy'], ['view fiscal years']],
]);

it('forbids a wrong-verb permission on each accounting route', function (string $method, string $route, array $fixtures, array $permissions) {
    $this->actingAs(acScopeUser($permissions));

    acScopeRequest($this, $method, $route, $fixtures)->assertForbidden();
})->with('accounting escalations');

// ── (b) Positive control: exactly the new permission passes ───────────────

dataset('accounting positive controls', [
    'chart-of-accounts' => ['get', 'accounting.chart-of-accounts', [], 'view chart of accounts', null],
    'create-account' => ['get', 'accounting.create-account', [], 'create chart of accounts', null],
    'store-account' => ['post', 'accounting.store-account', [], 'create chart of accounts', 'accounting.create-account'],
    'edit-account' => ['get', 'accounting.edit-account', ['account'], 'edit chart of accounts', null],
    'update-account' => ['put', 'accounting.update-account', ['account'], 'edit chart of accounts', 'accounting.edit-account'],
    'deposit' => ['get', 'accounting.deposit', [], 'create deposits', null],
    'process-deposit' => ['post', 'accounting.process-deposit', [], 'create deposits', 'accounting.deposit'],
    'transfer' => ['get', 'accounting.transfer', [], 'create transfers', null],
    'process-transfer' => ['post', 'accounting.process-transfer', [], 'create transfers', 'accounting.transfer'],
    'journal-entries' => ['get', 'accounting.journal-entries', [], 'view journal entries', null],
    'create-journal-entry' => ['get', 'accounting.create-journal-entry', [], 'create journal entries', null],
    'store-journal-entry' => ['post', 'accounting.store-journal-entry', [], 'create journal entries', 'accounting.create-journal-entry'],
    'edit-journal-entry' => ['get', 'accounting.edit-journal-entry', ['entry'], 'edit journal entries', null],
    'update-journal-entry' => ['put', 'accounting.update-journal-entry', ['entry'], 'edit journal entries', 'accounting.edit-journal-entry'],
    'fiscal-years' => ['get', 'accounting.fiscal-years', [], 'view fiscal years', null],
    'fiscal-years.pre-close' => ['get', 'accounting.fiscal-years.pre-close', ['fy'], 'close fiscal years', null],
    'fiscal-years.close' => ['post', 'accounting.fiscal-years.close', ['fy'], 'close fiscal years', 'accounting.fiscal-years.pre-close'],
]);

it('allows exactly the new permission on each accounting route', function (string $method, string $route, array $fixtures, string $permission, ?string $redirectsTo) {
    $this->actingAs(acScopeUser([$permission]));

    $response = acScopeRequest($this, $method, $route, $fixtures);

    if ($method === 'get') {
        $response->assertOk();

        return;
    }

    // AD-1 (Task 2): without a view permission the write falls back to its form.
    $response->assertRedirect(route($redirectsTo, acScopeParams($this, $fixtures)))
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error')
        ->assertSessionHas('success');
})->with('accounting positive controls');

// ── Coarse `view accounting`: read GETs only (D5) ─────────────────────────

dataset('accounting coarse reads', [
    'chart-of-accounts' => ['accounting.chart-of-accounts'],
    'journal-entries' => ['accounting.journal-entries'],
    'fiscal-years' => ['accounting.fiscal-years'],
    'general-ledger' => ['accounting.general-ledger'],
    'patient-ledger' => ['accounting.patient-ledger'],
    'vendor-ledger' => ['accounting.vendor-ledger'],
    'employee-ledger' => ['accounting.employee-ledger'],
    'profit-loss' => ['accounting.profit-loss'],
    'balance-sheet' => ['accounting.balance-sheet'],
]);

it('lets view accounting open each read page', function (string $route) {
    $this->actingAs(acScopeUser(['view accounting']));

    $this->get(route($route))->assertOk();
})->with('accounting coarse reads');

dataset('accounting form and write routes', [
    'create-account' => ['get', 'accounting.create-account', []],
    'store-account' => ['post', 'accounting.store-account', []],
    'edit-account' => ['get', 'accounting.edit-account', ['account']],
    'update-account' => ['put', 'accounting.update-account', ['account']],
    'deposit' => ['get', 'accounting.deposit', []],
    'process-deposit' => ['post', 'accounting.process-deposit', []],
    'transfer' => ['get', 'accounting.transfer', []],
    'process-transfer' => ['post', 'accounting.process-transfer', []],
    'create-journal-entry' => ['get', 'accounting.create-journal-entry', []],
    'store-journal-entry' => ['post', 'accounting.store-journal-entry', []],
    'edit-journal-entry' => ['get', 'accounting.edit-journal-entry', ['entry']],
    'update-journal-entry' => ['put', 'accounting.update-journal-entry', ['entry']],
    'fiscal-years.pre-close' => ['get', 'accounting.fiscal-years.pre-close', ['fy']],
    'fiscal-years.close' => ['post', 'accounting.fiscal-years.close', ['fy']],
]);

it('forbids view accounting on each form, write and close route', function (string $method, string $route, array $fixtures) {
    $this->actingAs(acScopeUser(['view accounting']));

    acScopeRequest($this, $method, $route, $fixtures)->assertForbidden();
})->with('accounting form and write routes');

// ── (c) Side effects: a blocked write writes nothing ──────────────────────
// The "nothing written" assertions run before the status assertion, so that
// before the fix these tests fail because the write actually happened.

it('creates no account when view chart of accounts posts store-account', function () {
    $this->actingAs(acScopeUser(['view chart of accounts']));
    $before = Account::count();

    $response = acScopeRequest($this, 'post', 'accounting.store-account', []);

    expect(Account::count())->toBe($before)
        ->and(Account::where('code', '1400')->exists())->toBeFalse();
    $response->assertForbidden();
});

it('posts no journal entry when view accounting submits a posting write', function (string $route) {
    $this->actingAs(acScopeUser(['view accounting']));
    $entries = JournalEntry::count();
    $lines = JournalEntryLine::count();

    $response = acScopeRequest($this, 'post', $route, []);

    expect(JournalEntry::count())->toBe($entries)
        ->and(JournalEntryLine::count())->toBe($lines);
    $response->assertForbidden();
})->with([
    'process-deposit' => ['accounting.process-deposit'],
    'process-transfer' => ['accounting.process-transfer'],
    'store-journal-entry' => ['accounting.store-journal-entry'],
]);

it('leaves the account name unchanged when view chart of accounts puts update-account', function () {
    $this->actingAs(acScopeUser(['view chart of accounts']));

    $response = acScopeRequest($this, 'put', 'accounting.update-account', ['account']);

    expect($this->cash->fresh()->name)->toBe('Cash in Hand');
    $response->assertForbidden();
});

it('leaves the journal entry lines unchanged when view journal entries puts update-journal-entry', function () {
    $this->actingAs(acScopeUser(['view journal entries']));
    $before = acScopeLines($this->entry);

    $response = acScopeRequest($this, 'put', 'accounting.update-journal-entry', ['entry']);

    expect(acScopeLines($this->entry))->toBe($before)
        ->and($this->entry->fresh()->description)->toBe('Manual fixture entry');
    $response->assertForbidden();
});

it('keeps the fiscal year open when view fiscal years posts close', function () {
    $this->actingAs(acScopeUser(['view fiscal years']));

    $response = acScopeRequest($this, 'post', 'accounting.fiscal-years.close', ['fy']);

    $fy = $this->fy->fresh();
    expect($fy->is_closed)->toBeFalse()
        ->and($fy->closed_by)->toBeNull()
        ->and($fy->closed_at)->toBeNull();
    $response->assertForbidden();
});

it('closes the fiscal year for a close fiscal years holder', function () {
    $user = acScopeUser(['close fiscal years', 'view fiscal years']);
    $this->actingAs($user);

    acScopeRequest($this, 'post', 'accounting.fiscal-years.close', ['fy'])
        ->assertRedirect(route('accounting.fiscal-years'))
        ->assertSessionHasNoErrors();

    $fy = $this->fy->fresh();
    expect($fy->is_closed)->toBeTrue()
        ->and($fy->closed_by)->toBe($user->id);
});

// ── AC-1: the seeded Hospital Administrator loses fiscal-year close ───────

it('denies the seeded Hospital Administrator pre-close and close but keeps every other accounting page', function () {
    $this->seed(RolePermissionSeeder::class);

    $ha = User::create([
        'name' => 'Seeded HA',
        'email' => 'ac-scope-ha-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $ha->assignRole('Hospital Administrator');
    $this->actingAs($ha);

    acScopeRequest($this, 'get', 'accounting.fiscal-years.pre-close', ['fy'])->assertForbidden();
    acScopeRequest($this, 'post', 'accounting.fiscal-years.close', ['fy'])->assertForbidden();
    expect($this->fy->fresh()->is_closed)->toBeFalse();

    foreach ([
        'accounting.chart-of-accounts',
        'accounting.journal-entries',
        'accounting.fiscal-years',
        'accounting.create-account',
        'accounting.create-journal-entry',
        'accounting.deposit',
        'accounting.transfer',
        'accounting.general-ledger',
        'accounting.patient-ledger',
        'accounting.vendor-ledger',
        'accounting.employee-ledger',
        'accounting.profit-loss',
        'accounting.balance-sheet',
    ] as $route) {
        $this->get(route($route))->assertOk();
    }
});

// ── AD-1: after a write, redirect only to a page the user can view (Task 2) ─

dataset('accounting write redirects', [
    // write route, fixtures, write permission, fallback route, fallback fixtures, list route, list view permission
    'store-account' => ['post', 'accounting.store-account', [], 'create chart of accounts', 'accounting.create-account', [], 'accounting.chart-of-accounts', 'view chart of accounts'],
    'update-account' => ['put', 'accounting.update-account', ['account'], 'edit chart of accounts', 'accounting.edit-account', ['account'], 'accounting.chart-of-accounts', 'view chart of accounts'],
    'process-deposit' => ['post', 'accounting.process-deposit', [], 'create deposits', 'accounting.deposit', [], 'accounting.chart-of-accounts', 'view chart of accounts'],
    'process-transfer' => ['post', 'accounting.process-transfer', [], 'create transfers', 'accounting.transfer', [], 'accounting.chart-of-accounts', 'view chart of accounts'],
    'store-journal-entry' => ['post', 'accounting.store-journal-entry', [], 'create journal entries', 'accounting.create-journal-entry', [], 'accounting.journal-entries', 'view journal entries'],
    'update-journal-entry' => ['put', 'accounting.update-journal-entry', ['entry'], 'edit journal entries', 'accounting.edit-journal-entry', ['entry'], 'accounting.journal-entries', 'view journal entries'],
    'fiscal-years.close' => ['post', 'accounting.fiscal-years.close', ['fy'], 'close fiscal years', 'accounting.fiscal-years.pre-close', ['fy'], 'accounting.fiscal-years', 'view fiscal years'],
]);

it('redirects a write-only holder to the fallback with the success flash', function (string $method, string $route, array $fixtures, string $write, string $fallback, array $fallbackFixtures) {
    $this->actingAs(acScopeUser([$write]));

    acScopeRequest($this, $method, $route, $fixtures)
        ->assertRedirect(route($fallback, acScopeParams($this, $fallbackFixtures)))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
})->with('accounting write redirects');

it('redirects a write + view holder to the list with the success flash', function (string $method, string $route, array $fixtures, string $write, string $fallback, array $fallbackFixtures, string $list, string $view) {
    $this->actingAs(acScopeUser([$write, $view]));

    acScopeRequest($this, $method, $route, $fixtures)
        ->assertRedirect(route($list))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
})->with('accounting write redirects');

it('treats view accounting as view for the list after a write', function (string $method, string $route, array $fixtures, string $write, string $list) {
    $this->actingAs(acScopeUser([$write, 'view accounting']));

    acScopeRequest($this, $method, $route, $fixtures)
        ->assertRedirect(route($list))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
})->with([
    'store-account → chart-of-accounts' => ['post', 'accounting.store-account', [], 'create chart of accounts', 'accounting.chart-of-accounts'],
    'store-journal-entry → journal-entries' => ['post', 'accounting.store-journal-entry', [], 'create journal entries', 'accounting.journal-entries'],
    'fiscal-years.close → fiscal-years' => ['post', 'accounting.fiscal-years.close', ['fy'], 'close fiscal years', 'accounting.fiscal-years'],
]);

// ── UI gating: action controls render only with their own permission (Task 3) ─

/** An exact `href="…"` attribute, as Blade renders it. */
function acScopeHref(string $route, array $params = []): string
{
    return 'href="'.e(route($route, $params)).'"';
}

/** A pattern matching an href to this route for any id in place of $param. */
function acScopeHrefPattern(string $route, string $param): string
{
    $url = preg_quote(e(route($route, [$param => 'ACSCOPEID'])), '/');

    return '/href="'.str_replace('ACSCOPEID', '\d+', $url).'"/';
}

it('hides every chart-of-accounts action from a view-only user', function () {
    $this->actingAs(acScopeUser(['view chart of accounts', 'view accounting']));

    $html = $this->get(route('accounting.chart-of-accounts'))
        ->assertOk()
        ->assertSee('Cash in Hand') // the account rows render
        ->assertDontSee(acScopeHref('accounting.deposit'), false)
        ->assertDontSee(acScopeHref('accounting.transfer'), false)
        ->assertDontSee(acScopeHref('accounting.create-account'), false)
        ->getContent();

    expect(preg_match(acScopeHrefPattern('accounting.edit-account', 'account'), $html))->toBe(0);
});

it('shows each chart-of-accounts action with its own permission', function (string $permission, string $route, bool $perAccount) {
    $this->actingAs(acScopeUser(['view chart of accounts', 'view accounting', $permission]));

    $response = $this->get(route('accounting.chart-of-accounts'))->assertOk();

    if ($perAccount) {
        foreach ([$this->cash, $this->bank, $this->revenue] as $account) {
            $response->assertSee(acScopeHref($route, ['account' => $account->id]), false);
        }
    } else {
        $response->assertSee(acScopeHref($route), false);
        expect(preg_match(acScopeHrefPattern('accounting.edit-account', 'account'), $response->getContent()))->toBe(0);
    }

    // The other header actions stay hidden.
    foreach (['accounting.deposit', 'accounting.transfer', 'accounting.create-account'] as $other) {
        if ($other !== $route) {
            $response->assertDontSee(acScopeHref($other), false);
        }
    }
})->with([
    'create deposits → deposit' => ['create deposits', 'accounting.deposit', false],
    'create transfers → transfer' => ['create transfers', 'accounting.transfer', false],
    'create chart of accounts → new account' => ['create chart of accounts', 'accounting.create-account', false],
    'edit chart of accounts → row edit' => ['edit chart of accounts', 'accounting.edit-account', true],
]);

/** Seed an auto (system-posted) entry beside the manual fixture entry. */
function acScopeAutoEntry($test): JournalEntry
{
    $auto = JournalEntry::create([
        'entry_date' => '2026-03-16',
        'description' => 'Auto fixture entry',
        'created_by' => $test->entry->created_by,
        'is_auto' => true,
        'entry_type' => 'original',
    ]);
    $auto->lines()->create(['account_id' => $test->cash->id, 'debit' => 400, 'credit' => 0, 'narration' => 'Auto debit']);
    $auto->lines()->create(['account_id' => $test->revenue->id, 'debit' => 0, 'credit' => 400, 'narration' => 'Auto credit']);

    return $auto;
}

it('hides New and manual-row Edit on journal entries from a view-only user but keeps the auto lock', function () {
    $auto = acScopeAutoEntry($this);
    $this->actingAs(acScopeUser(['view journal entries', 'view accounting']));

    $this->get(route('accounting.journal-entries'))
        ->assertOk()
        ->assertSee('Manual fixture entry')
        ->assertSee('Auto fixture entry')
        ->assertDontSee(acScopeHref('accounting.create-journal-entry'), false)
        ->assertDontSee(acScopeHref('accounting.edit-journal-entry', ['journalEntry' => $this->entry->id]), false)
        ->assertDontSee(acScopeHref('accounting.edit-journal-entry', ['journalEntry' => $auto->id]), false)
        ->assertSee('title="Auto entries cannot be edited"', false);
});

it('shows the journal-entry New link with create journal entries', function () {
    acScopeAutoEntry($this);
    $this->actingAs(acScopeUser(['view journal entries', 'create journal entries']));

    $this->get(route('accounting.journal-entries'))
        ->assertOk()
        ->assertSee(acScopeHref('accounting.create-journal-entry'), false)
        ->assertDontSee(acScopeHref('accounting.edit-journal-entry', ['journalEntry' => $this->entry->id]), false);
});

it('shows the manual-row Edit link with edit journal entries, never on the auto entry', function () {
    $auto = acScopeAutoEntry($this);
    $this->actingAs(acScopeUser(['view journal entries', 'edit journal entries']));

    $this->get(route('accounting.journal-entries'))
        ->assertOk()
        ->assertSee(acScopeHref('accounting.edit-journal-entry', ['journalEntry' => $this->entry->id]), false)
        ->assertDontSee(acScopeHref('accounting.edit-journal-entry', ['journalEntry' => $auto->id]), false)
        ->assertDontSee(acScopeHref('accounting.create-journal-entry'), false)
        ->assertSee('title="Auto entries cannot be edited"', false);
});

/** The text of the last cell (Actions) in the fiscal-year row that names $name. */
function acScopeFyActionCell(string $html, string $name): ?string
{
    preg_match_all('/<tr\b[^>]*>(.*?)<\/tr>/s', $html, $rows);
    foreach ($rows[1] as $row) {
        if (! str_contains($row, e($name))) {
            continue;
        }
        preg_match_all('/<td\b[^>]*>(.*?)<\/td>/s', $row, $cells);

        return $cells[1] === [] ? null : trim(strip_tags(end($cells[1])));
    }

    return null;
}

it('hides Close Period from a view fiscal years user and shows a dash for the open year', function () {
    $this->actingAs(acScopeUser(['view fiscal years']));

    $html = $this->get(route('accounting.fiscal-years'))
        ->assertOk()
        ->assertSee('FY 2025-26')
        ->assertDontSee(acScopeHref('accounting.fiscal-years.pre-close', ['fiscalYear' => $this->fy->id]), false)
        ->getContent();

    expect(acScopeFyActionCell($html, 'FY 2025-26'))->toBe('—');
});

it('shows Close Period with close fiscal years', function () {
    $this->actingAs(acScopeUser(['view fiscal years', 'close fiscal years']));

    $html = $this->get(route('accounting.fiscal-years'))
        ->assertOk()
        ->assertSee(acScopeHref('accounting.fiscal-years.pre-close', ['fiscalYear' => $this->fy->id]), false)
        ->getContent();

    expect(acScopeFyActionCell($html, 'FY 2025-26'))->toBe('Close Period');
});

it('still shows Locked for a closed year without close fiscal years', function () {
    $this->fy->update(['is_closed' => true]);
    $this->actingAs(acScopeUser(['view fiscal years']));

    $html = $this->get(route('accounting.fiscal-years'))->assertOk()->getContent();

    expect(acScopeFyActionCell($html, 'FY 2025-26'))->toBe('Locked');
});
