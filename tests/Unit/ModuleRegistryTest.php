<?php

use App\Models\ModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

it('maps pharmacy pos routes to pharmacy module', function () {
    expect(ModuleRegistry::moduleForRoute('pharmacy.pos.index'))->toBe('pharmacy.pos');
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

it('locks hospital info onto any slug list that includes settings', function () {
    expect(ModuleRegistry::normalize(['settings']))
        ->toEqualCanonicalizing(['settings', 'settings.hospital-info'])
        ->and(ModuleRegistry::normalize(['settings.prescription-print']))
            ->toEqualCanonicalizing(['settings.prescription-print', 'settings', 'settings.hospital-info'])
        ->and(ModuleRegistry::normalize(['settings', 'settings.hospital-info']))
            ->not->toContain('settings.prescription-print');
});

it('backfills all report children only when reports is already on the plan', function () {
    expect(ModuleRegistry::backfillReportChildren(['patients']))->toBe(['patients'])
        ->and(ModuleRegistry::backfillReportChildren(['reports']))
            ->toEqualCanonicalizing(array_merge(['reports'], ModuleRegistry::reportChildSlugs()))
        ->and(ModuleRegistry::backfillReportChildren(['reports', 'reports.revenue']))
            ->toContain('reports.revenue', 'reports.daily-cash-register')
            ->and(count(array_unique(ModuleRegistry::backfillReportChildren(['reports', 'reports.revenue']))))
            ->toBe(1 + count(ModuleRegistry::reportChildSlugs()));
});

it('maps each operational report route to its child slug', function () {
    expect(ModuleRegistry::moduleForRoute('reports.daily-cash-register'))->toBe('reports.daily-cash-register')
        ->and(ModuleRegistry::moduleForRoute('reports.lab-tests'))->toBe('reports.investigations')
        ->and(ModuleRegistry::moduleForRoute('reports.investigations'))->toBe('reports.investigations')
        ->and(ModuleRegistry::parentOf('reports.revenue'))->toBe('reports')
        ->and(ModuleRegistry::topLevel())->toHaveCount(20)
        ->and(ModuleRegistry::all())->toHaveCount(52)
        ->and(ModuleRegistry::normalize(['reports.revenue']))->toEqualCanonicalizing(['reports.revenue', 'reports'])
        ->and(ModuleRegistry::normalize(['reports']))->toBe(['reports'])
        ->and(ModuleRegistry::definitions()['reports']['children'])->toBe(ModuleRegistry::REPORT_CHILD_SLUGS);
});

it('registers 20 top-level modules including emergency and settings', function () {
    expect(ModuleRegistry::topLevel())->toHaveCount(20)
        ->and(ModuleRegistry::all())->toHaveCount(52);
});

it('declares entitlement and child-access flags without changing slug sets', function () {
    expect(ModuleRegistry::topLevel())->toHaveCount(20)
        ->and(ModuleRegistry::all())->toHaveCount(52);

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

it('maps accounting statement routes to child slugs and pharmacy surfaces to pharmacy children', function () {
    expect(ModuleRegistry::moduleForRoute('accounting.profit-loss'))->toBe('accounting.profit-loss')
        ->and(ModuleRegistry::moduleForRoute('accounting.chart-of-accounts'))->toBe('accounting')
        ->and(ModuleRegistry::moduleForRoute('accounting.journal-entries'))->toBe('accounting')
        ->and(ModuleRegistry::moduleForRoute('accounting.fiscal-years'))->toBe('accounting')
        ->and(ModuleRegistry::moduleForRoute('pharmacy.pos.index'))->toBe('pharmacy.pos')
        ->and(ModuleRegistry::moduleForRoute('inventory.index'))->toBe('pharmacy.inventory')
        ->and(ModuleRegistry::moduleForRoute('purchases.index'))->toBe('pharmacy.inventory')
        ->and(ModuleRegistry::moduleForRoute('suppliers.index'))->toBe('pharmacy.inventory')
        ->and(ModuleRegistry::moduleForRoute('medicines.index'))->toBe('pharmacy.catalog')
        ->and(ModuleRegistry::moduleForRoute('prescriptions.index'))->toBe('pharmacy')
        ->and(ModuleRegistry::parentOf('accounting.employee-ledger'))->toBe('accounting')
        ->and(ModuleRegistry::parentOf('pharmacy.pos'))->toBe('pharmacy')
        ->and(ModuleRegistry::definitions()['accounting']['child_access_requires_explicit_grant'])->toBeTrue()
        ->and(ModuleRegistry::definitions()['pharmacy']['child_access_requires_explicit_grant'])->toBeTrue();
});

it('backfills accounting and pharmacy children only when the parent is on the plan', function () {
    expect(ModuleRegistry::backfillAccountingChildren(['patients']))->toBe(['patients'])
        ->and(ModuleRegistry::backfillPharmacyChildren(['patients']))->toBe(['patients'])
        ->and(ModuleRegistry::backfillAccountingChildren(['accounting']))
            ->toEqualCanonicalizing(array_merge(['accounting'], ModuleRegistry::accountingChildSlugs()))
        ->and(ModuleRegistry::backfillPharmacyChildren(['pharmacy']))
            ->toEqualCanonicalizing(array_merge(['pharmacy'], ModuleRegistry::pharmacyChildSlugs()));
});

it('marks settings as explicit-grant so print is not parent-implied in plan form js', function () {
    expect(ModuleRegistry::definitions()['settings']['child_access_requires_explicit_grant'])->toBeTrue();
});

it('registers four hr catalog children and maps cluster routes to them', function () {
    expect(ModuleRegistry::hrChildSlugs())->toBe(ModuleRegistry::HR_CHILD_SLUGS)
        ->and(ModuleRegistry::HR_CHILD_SLUGS)->toBe([
            'hr.employees',
            'hr.attendance-leave',
            'hr.payroll',
            'hr.scheduling',
        ])
        ->and(ModuleRegistry::definitions()['hr']['child_access_requires_explicit_grant'])->toBeTrue()
        ->and(ModuleRegistry::definitions()['hr']['children'])->toBe(ModuleRegistry::HR_CHILD_SLUGS)
        ->and(ModuleRegistry::parentOf('hr.payroll'))->toBe('hr')
        ->and(ModuleRegistry::moduleForRoute('hr.employees.index'))->toBe('hr.employees')
        ->and(ModuleRegistry::moduleForRoute('hr.employees.upload-document'))->toBe('hr.employees')
        ->and(ModuleRegistry::moduleForRoute('hr.designations.index'))->toBe('hr.employees')
        ->and(ModuleRegistry::moduleForRoute('hr.department-staff.index'))->toBe('hr.employees')
        ->and(ModuleRegistry::moduleForRoute('hr.documents.index'))->toBe('hr.employees')
        ->and(ModuleRegistry::moduleForRoute('hr.attendance.index'))->toBe('hr.attendance-leave')
        ->and(ModuleRegistry::moduleForRoute('hr.leave.index'))->toBe('hr.attendance-leave')
        ->and(ModuleRegistry::moduleForRoute('hr.leave.balances'))->toBe('hr.attendance-leave')
        ->and(ModuleRegistry::moduleForRoute('hr.leave-types.index'))->toBe('hr.attendance-leave')
        ->and(ModuleRegistry::moduleForRoute('hr.payroll.index'))->toBe('hr.payroll')
        ->and(ModuleRegistry::moduleForRoute('hr.payroll.payslip'))->toBe('hr.payroll')
        ->and(ModuleRegistry::moduleForRoute('hr.payroll.components'))->toBe('hr.payroll')
        ->and(ModuleRegistry::moduleForRoute('hr.payroll.employee-salary'))->toBe('hr.payroll')
        ->and(ModuleRegistry::moduleForRoute('hr.shifts.index'))->toBe('hr.scheduling')
        ->and(ModuleRegistry::moduleForRoute('hr.shifts.roster'))->toBe('hr.scheduling')
        ->and(ModuleRegistry::moduleForRoute('hr.shifts.swap-requests'))->toBe('hr.scheduling')
        ->and(ModuleRegistry::topLevel())->toHaveCount(20)
        ->and(ModuleRegistry::all())->toHaveCount(52)
        ->and(ModuleRegistry::normalize(['hr.payroll']))->toEqualCanonicalizing(['hr.payroll', 'hr'])
        ->and(ModuleRegistry::normalize(['hr']))->toBe(['hr'])
        ->and(ModuleRegistry::backfillHrChildren(['patients']))->toBe(['patients'])
        ->and(ModuleRegistry::backfillHrChildren(['hr', 'patients']))->toEqualCanonicalizing(array_merge(
            ['hr', 'patients'],
            ModuleRegistry::HR_CHILD_SLUGS
        ));
});

it('registers four ot catalog children and keeps core surgeries on the parent', function () {
    expect(ModuleRegistry::OT_CHILD_SLUGS)->toBe([
            'ot.pac',
            'ot.checklist',
            'ot.consumables',
            'ot.sterilization',
        ])
        ->and(ModuleRegistry::definitions()['ot']['child_access_requires_explicit_grant'])->toBeTrue()
        ->and(ModuleRegistry::moduleForRoute('ot.surgeries.index'))->toBe('ot')
        ->and(ModuleRegistry::moduleForRoute('ot.theatres'))->toBe('ot')
        ->and(ModuleRegistry::moduleForRoute('ot.calendar'))->toBe('ot')
        ->and(ModuleRegistry::moduleForRoute('ot.monitoring.vitals'))->toBe('ot')
        ->and(ModuleRegistry::moduleForRoute('ot.pac.index'))->toBe('ot.pac')
        ->and(ModuleRegistry::moduleForRoute('ot.checklist.show'))->toBe('ot.checklist')
        ->and(ModuleRegistry::moduleForRoute('ot.consumables.index'))->toBe('ot.consumables')
        ->and(ModuleRegistry::moduleForRoute('ot.consumables.usage'))->toBe('ot.consumables')
        ->and(ModuleRegistry::moduleForRoute('ot.sterilization.index'))->toBe('ot.sterilization')
        ->and(ModuleRegistry::normalize(['ot']))->toBe(['ot'])
        ->and(ModuleRegistry::all())->toHaveCount(52)
        ->and(ModuleRegistry::backfillOtChildren(['ot', 'hr']))->toContain(...ModuleRegistry::OT_CHILD_SLUGS)
        ->and(ModuleRegistry::backfillOtChildren(['hr']))->toBe(['hr']);
});
