<?php

use App\Models\ModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

it('maps pharmacy pos routes to pharmacy module', function () {
    expect(ModuleRegistry::moduleForRoute('pharmacy.pos.index'))->toBe('pharmacy');
});

it('maps departments routes to departments module', function () {
    expect(ModuleRegistry::moduleForRoute('departments.index'))->toBe('departments');
});

it('maps accounting routes to accounting module', function () {
    expect(ModuleRegistry::moduleForRoute('accounting.chart-of-accounts'))->toBe('accounting');
});

it('maps imaging routes to imaging module', function () {
    expect(ModuleRegistry::moduleForRoute('imaging.orders.index'))->toBe('imaging');
});

it('maps lab routes to laboratory module', function () {
    expect(ModuleRegistry::moduleForRoute('lab.orders.index'))->toBe('laboratory');
});

it('maps radiology results to imaging module', function () {
    expect(ModuleRegistry::moduleForRoute('radiology-results.create'))->toBe('imaging');
});

it('maps taxes routes to billing module', function () {
    expect(ModuleRegistry::moduleForRoute('taxes.index'))->toBe('billing');
});

it('maps settings child routes to their child modules', function () {
    expect(ModuleRegistry::moduleForRoute('settings.hospital-info'))->toBe('settings.hospital-info')
        ->and(ModuleRegistry::moduleForRoute('settings.update'))->toBe('settings.hospital-info')
        ->and(ModuleRegistry::moduleForRoute('settings.prescription-print-templates.index'))
        ->toBe('settings.prescription-print')
        ->and(ModuleRegistry::moduleForRoute('settings.index'))->toBe('settings');
});

it('does not gate timezone detection under settings', function () {
    expect(ModuleRegistry::moduleForRoute('settings.detect-timezone'))->toBeNull();
});

it('resolves visit routes to emergency or ipd from visit type', function () {
    $named = fn (string $query) => tap(Request::create('/visits?'.$query, 'GET'), function (Request $request) {
        $request->setRouteResolver(fn () => new Route(['GET'], '/visits', [
            'as' => 'visits.index',
            'uses' => fn () => null,
        ]));
    });

    expect(ModuleRegistry::moduleForRequest($named('visit_type=emergency')))->toBe('emergency')
        ->and(ModuleRegistry::moduleForRequest($named('visit_type=ipd')))->toBe('ipd')
        ->and(ModuleRegistry::moduleForRequest($named('visit_type=opd')))->toBe('visits');
});

it('lists settings children under the settings parent and emergency as top-level', function () {
    expect(ModuleRegistry::all())->toContain('emergency', 'settings', 'settings.hospital-info', 'settings.prescription-print')
        ->and(ModuleRegistry::topLevel())->toContain('emergency', 'settings')
        ->and(ModuleRegistry::topLevel())->not->toContain('settings.hospital-info', 'settings.prescription-print')
        ->and(ModuleRegistry::parentOf('settings.hospital-info'))->toBe('settings')
        ->and(ModuleRegistry::parentOf('emergency'))->toBeNull();
});

it('normalizes selected children by adding their parent slug', function () {
    expect(ModuleRegistry::normalize(['settings.hospital-info', 'visits']))
        ->toEqualCanonicalizing(['settings.hospital-info', 'visits', 'settings']);
});

it('maps each operational report route to its child slug', function () {
    expect(ModuleRegistry::moduleForRoute('reports.daily-cash-register'))->toBe('reports.daily-cash-register')
        ->and(ModuleRegistry::moduleForRoute('reports.lab-tests'))->toBe('reports.investigations')
        ->and(ModuleRegistry::moduleForRoute('reports.investigations'))->toBe('reports.investigations')
        ->and(ModuleRegistry::parentOf('reports.revenue'))->toBe('reports')
        ->and(ModuleRegistry::topLevel())->toHaveCount(20)
        ->and(ModuleRegistry::all())->toHaveCount(35)
        ->and(ModuleRegistry::normalize(['reports.revenue']))->toEqualCanonicalizing(['reports.revenue', 'reports'])
        ->and(ModuleRegistry::normalize(['reports']))->toBe(['reports'])
        ->and(ModuleRegistry::definitions()['reports']['children'])->toBe(ModuleRegistry::REPORT_CHILD_SLUGS);
});

it('registers 20 top-level modules including emergency and settings', function () {
    expect(ModuleRegistry::topLevel())->toHaveCount(20)
        ->and(ModuleRegistry::all())->toHaveCount(35);
});

it('declares entitlement and child-access flags without changing slug sets', function () {
    expect(ModuleRegistry::topLevel())->toHaveCount(20)
        ->and(ModuleRegistry::all())->toHaveCount(35);

    $reports = ModuleRegistry::definitions()['reports'];
    expect($reports['entitlement'] ?? 'plan')->toBe('plan')
        ->and($reports['child_access_requires_explicit_grant'] ?? false)->toBeTrue()
        ->and($reports['parent'] ?? null)->toBeNull();

    $backup = ModuleRegistry::definitions()['backup'];
    expect($backup['entitlement'] ?? 'plan')->toBe('plan')
        ->and($backup['child_access_requires_explicit_grant'] ?? false)->toBeFalse();

    $settingsChild = ModuleRegistry::definitions()['settings.hospital-info'];
    expect($settingsChild['entitlement'] ?? 'plan')->toBe('plan')
        ->and(array_key_exists('child_access_requires_explicit_grant', $settingsChild))->toBeFalse();
});
