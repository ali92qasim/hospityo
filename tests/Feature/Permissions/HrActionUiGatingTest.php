<?php

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\Department;
use App\Models\Designation;
use App\Models\DocumentRequirement;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\SalaryComponent;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| HR UI gating: write controls render only with their own permission (Task 9)
|--------------------------------------------------------------------------
| Every `resources/views/admin/hr/**` control that links or posts to a write
| route is wrapped in @can/@canany for that route's permission. For each
| representative page: a view-only user (specific `view …` or legacy
| `view hr`) sees no write control; a holder of exactly one action permission
| (on top of the view permission) sees that action's controls and none of the
| other actions'. Fixtures are real rows so every control actually renders.
*/

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
    $this->withoutVite();

    $this->artisan('migrate', [
        '--path' => 'database/migrations/tenant',
        '--database' => 'tenant',
    ]);

    otCatalogTenant(['hr', 'hr.employees', 'hr.attendance-leave', 'hr.payroll', 'hr.scheduling']);

    $this->department = Department::create(['name' => 'Hr Ui Dept', 'code' => 'HUD1']);

    $this->employee = Employee::create([
        'name' => 'Hr Ui Employee',
        'joining_date' => '2026-01-01',
        'employment_type' => 'full_time',
        'status' => 'active',
        'department_id' => $this->department->id,
    ]);
    $this->employee2 = Employee::create([
        'name' => 'Hr Ui Employee Two',
        'joining_date' => '2026-01-01',
        'employment_type' => 'full_time',
        'status' => 'active',
        'department_id' => $this->department->id,
    ]);

    $this->designation = Designation::create([
        'name' => 'Hr Ui Designation', 'category' => 'admin', 'description' => 'Fixture',
    ]);
    $this->employeeDocument = EmployeeDocument::create([
        'employee_id' => $this->employee->id,
        'title' => 'Hr Ui Employee Document',
        'document_type' => 'other',
        'file_path' => 'employee-docs/hr-ui.pdf',
    ]);

    $this->leaveType = LeaveType::create(['name' => 'Hr Ui Leave', 'code' => 'HUL', 'default_days' => 20]);
    $leave = fn (string $status) => LeaveRequest::create([
        'employee_id' => $this->employee->id,
        'leave_type_id' => $this->leaveType->id,
        'start_date' => '2026-03-02',
        'end_date' => '2026-03-03',
        'total_days' => 2,
        'reason' => 'Fixture request',
        'status' => $status,
    ]);
    $this->pendingLeave = $leave('pending');
    $this->approvedLeave = $leave('approved');

    $this->component = SalaryComponent::create([
        'name' => 'Hr Ui Component', 'code' => 'HUC',
        'type' => 'allowance', 'calculation' => 'fixed', 'default_amount' => 100,
    ]);
    $creator = User::create([
        'name' => 'Hr Ui Creator',
        'email' => 'hr-ui-creator-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->payrollRun = PayrollRun::create([
        'title' => 'Payroll — Hr Ui', 'year' => 2026, 'month' => 2, 'status' => 'draft', 'created_by' => $creator->id,
    ]);
    $this->payslip = Payslip::create([
        'payroll_run_id' => $this->payrollRun->id,
        'employee_id' => $this->employee->id,
    ]);

    $shiftAttrs = fn (string $name, string $code) => [
        'name' => $name, 'code' => $code, 'start_time' => '08:00', 'end_time' => '16:00', 'working_hours' => 8,
    ];
    $this->shift = Shift::create($shiftAttrs('Hr Ui Shift', 'HUS'));
    $this->shift2 = Shift::create($shiftAttrs('Hr Ui Shift Two', 'HU2'));
    $this->swap = ShiftSwapRequest::create([
        'requester_id' => $this->employee->id,
        'target_id' => $this->employee2->id,
        'swap_date' => '2026-06-01',
        'requester_shift_id' => $this->shift->id,
        'target_shift_id' => $this->shift2->id,
        'status' => 'pending',
    ]);

    $docAttrs = fn (string $title, bool $verified) => [
        'employee_id' => $this->employee->id,
        'title' => $title,
        'document_type' => 'cnic',
        'file_path' => 'documents/hr-ui.pdf',
        'is_verified' => $verified,
    ];
    $this->docToVerify = EmployeeDocument::create($docAttrs('Hr Ui Unverified', false));
    $this->docToUnverify = EmployeeDocument::create($docAttrs('Hr Ui Verified', true));
    $this->requirement = DocumentRequirement::create([
        'document_type' => 'cnic', 'label' => 'Hr Ui Requirement', 'applicable_to' => 'all',
    ]);
});

function hrUiHref(string $route, array|int $params = []): string
{
    return 'href="'.route($route, $params).'"';
}

function hrUiAction(string $route, array|int $params = []): string
{
    return 'action="'.route($route, $params).'"';
}

/**
 * page => [view permission, URL, [action permission => needles (raw HTML) that only that permission reveals]].
 * Closures take the test case so fixtures resolve at run time.
 */
function hrUiPages(): array
{
    return [
        'employees.index' => ['view employees', fn ($t) => route('hr.employees.index'), [
            'create employees' => fn ($t) => [hrUiHref('hr.employees.create')],
            'edit employees' => fn ($t) => [hrUiHref('hr.employees.edit', $t->employee->id)],
            'delete employees' => fn ($t) => [hrUiAction('hr.employees.destroy', $t->employee->id)],
        ]],
        'employees.show' => ['view employees', fn ($t) => route('hr.employees.show', $t->employee->id), [
            'edit employees' => fn ($t) => [hrUiHref('hr.employees.edit', $t->employee->id)],
            'delete employee documents' => fn ($t) => [hrUiAction('hr.employees.delete-document', $t->employeeDocument->id)],
            'create employee documents' => fn ($t) => [hrUiAction('hr.employees.upload-document', $t->employee->id)],
        ]],
        'designations.index' => ['view designations', fn ($t) => route('hr.designations.index'), [
            'create designations' => fn ($t) => [hrUiHref('hr.designations.create')],
            'edit designations' => fn ($t) => [hrUiHref('hr.designations.edit', $t->designation->id)],
            'delete designations' => fn ($t) => [hrUiAction('hr.designations.destroy', $t->designation->id)],
        ]],
        'attendance.index' => ['view attendance', fn ($t) => route('hr.attendance.index'), [
            'create attendance' => fn ($t) => ['href="'.route('hr.attendance.mark').'?date='],
        ]],
        'leave.index' => ['view leave requests', fn ($t) => route('hr.leave.index'), [
            'create leave requests' => fn ($t) => [hrUiHref('hr.leave.create')],
            'approve leave requests' => fn ($t) => [
                hrUiAction('hr.leave.approve', $t->pendingLeave->id),
                hrUiAction('hr.leave.reject', $t->pendingLeave->id),
            ],
            'edit leave requests' => fn ($t) => [hrUiAction('hr.leave.cancel', $t->approvedLeave->id)],
        ]],
        'leave-types.index' => ['view leave types', fn ($t) => route('hr.leave-types.index'), [
            'create leave types' => fn ($t) => [hrUiHref('hr.leave-types.create')],
            'edit leave types' => fn ($t) => [hrUiHref('hr.leave-types.edit', $t->leaveType->id)],
            'delete leave types' => fn ($t) => [hrUiAction('hr.leave-types.destroy', $t->leaveType->id)],
        ]],
        'payroll.index' => ['view payroll runs', fn ($t) => route('hr.payroll.index'), [
            'create payroll runs' => fn ($t) => [hrUiAction('hr.payroll.generate')],
        ]],
        'payroll.show' => ['view payroll runs', fn ($t) => route('hr.payroll.show', $t->payrollRun->id), [
            'approve payroll runs' => fn ($t) => [hrUiAction('hr.payroll.approve', $t->payrollRun->id)],
            'edit payroll runs' => fn ($t) => [hrUiAction('hr.payroll.cancel', $t->payrollRun->id)],
        ]],
        'payroll.payslip' => ['view payslips', fn ($t) => route('hr.payroll.payslip', $t->payslip->id), [
            'edit payslips' => fn ($t) => [hrUiAction('hr.payroll.mark-paid', $t->payslip->id)],
        ]],
        'payroll.components' => ['view salary components', fn ($t) => route('hr.payroll.components'), [
            'create salary components' => fn ($t) => [hrUiHref('hr.payroll.create-component')],
            'edit salary components' => fn ($t) => [hrUiHref('hr.payroll.edit-component', $t->component->id)],
            'delete salary components' => fn ($t) => [hrUiAction('hr.payroll.destroy-component', $t->component->id)],
        ]],
        'payroll.employee-salary' => ['view employee salary', fn ($t) => route('hr.payroll.employee-salary', $t->employee->id), [
            'edit employee salary' => fn ($t) => ['Save Salary Structure'],
        ]],
        'shifts.index' => ['view shifts', fn ($t) => route('hr.shifts.index'), [
            'create shifts' => fn ($t) => [hrUiHref('hr.shifts.create')],
            'edit shifts' => fn ($t) => [hrUiHref('hr.shifts.edit', $t->shift->id)],
            'delete shifts' => fn ($t) => [hrUiAction('hr.shifts.destroy', $t->shift->id)],
        ]],
        'shifts.roster' => ['view duty roster', fn ($t) => route('hr.shifts.roster'), [
            'create duty roster' => fn ($t) => [hrUiAction('hr.shifts.auto-generate'), 'Save Roster'],
        ]],
        'shifts.swap-requests' => ['view shift swaps', fn ($t) => route('hr.shifts.swap-requests'), [
            'approve shift swaps' => fn ($t) => [
                hrUiAction('hr.shifts.approve-swap', $t->swap->id),
                hrUiAction('hr.shifts.reject-swap', $t->swap->id),
            ],
        ]],
        'department-staff.show' => ['view department staff', fn ($t) => route('hr.department-staff.show', $t->department->id), [
            'edit department staff' => fn ($t) => [
                hrUiAction('hr.department-staff.update-head', $t->department->id),
                hrUiAction('hr.department-staff.transfer'),
            ],
        ]],
        'documents.index' => ['view hr documents', fn ($t) => route('hr.documents.index'), [
            'verify hr documents' => fn ($t) => [
                hrUiAction('hr.documents.verify', $t->docToVerify->id),
                hrUiAction('hr.documents.unverify', $t->docToUnverify->id),
            ],
        ]],
        'documents.requirements' => ['view hr documents', fn ($t) => route('hr.documents.requirements'), [
            'create hr documents' => fn ($t) => [hrUiHref('hr.documents.create-requirement')],
            'delete hr documents' => fn ($t) => [hrUiAction('hr.documents.destroy-requirement', $t->requirement->id)],
        ]],
    ];
}

/** Every needle on the page, keyed by the permission that reveals it. */
function hrUiAllNeedles($test, array $controls): array
{
    return array_map(fn ($needles) => $needles($test), $controls);
}

dataset('hr ui pages', fn () => array_combine(array_keys(hrUiPages()), array_map(fn ($p) => [$p], array_keys(hrUiPages()))));

dataset('hr ui page × action permission', function () {
    $cases = [];
    foreach (hrUiPages() as $page => [, , $controls]) {
        foreach (array_keys($controls) as $permission) {
            $cases["$page via $permission"] = [$page, $permission];
        }
    }

    return $cases;
});

it('hides every write control from a view-only user', function (string $page) {
    [$view, $url, $controls] = hrUiPages()[$page];

    $this->actingAs(otCatalogUser([$view]));
    $response = $this->get($url($this))->assertOk();

    foreach (hrUiAllNeedles($this, $controls) as $permission => $needles) {
        foreach ($needles as $needle) {
            $response->assertDontSee($needle, false);
        }
    }
})->with('hr ui pages');

it('hides every write control from a legacy view hr holder', function (string $page) {
    [, $url, $controls] = hrUiPages()[$page];

    $this->actingAs(otCatalogUser(['view hr']));
    $response = $this->get($url($this))->assertOk();

    foreach (hrUiAllNeedles($this, $controls) as $needles) {
        foreach ($needles as $needle) {
            $response->assertDontSee($needle, false);
        }
    }
})->with('hr ui pages');

it('shows only the controls of the action permission granted on top of view', function (string $page, string $granted) {
    [$view, $url, $controls] = hrUiPages()[$page];

    $this->actingAs(otCatalogUser([$view, $granted]));
    $response = $this->get($url($this))->assertOk();

    foreach (hrUiAllNeedles($this, $controls) as $permission => $needles) {
        foreach ($needles as $needle) {
            $permission === $granted
                ? $response->assertSee($needle, false)
                : $response->assertDontSee($needle, false);
        }
    }
})->with('hr ui page × action permission');

it('shows the attendance mark submit only with create attendance', function () {
    $this->actingAs(otCatalogUser(['create attendance']));

    $this->get(route('hr.attendance.mark'))
        ->assertOk()
        ->assertSee('Save Attendance');
});

it('disables the roster inputs for a roster viewer without create duty roster', function () {
    $this->actingAs(otCatalogUser(['view duty roster']));
    $viewer = $this->get(route('hr.shifts.roster'))->assertOk()->getContent();

    $this->actingAs(otCatalogUser(['view duty roster', 'create duty roster']));
    $editor = $this->get(route('hr.shifts.roster'))->assertOk()->getContent();

    $disabledIn = fn (string $html) => preg_match_all('/<select name="roster\[[^>]*\bdisabled\b/s', $html);

    expect($disabledIn($viewer))->toBeGreaterThan(0)
        ->and($disabledIn($editor))->toBe(0);
});
