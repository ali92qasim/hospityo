<?php

use App\Http\Controllers\InstallController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WardController;
use App\Http\Controllers\BedController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\MedicineCategoryController;
use App\Http\Controllers\MedicineBrandController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\InvestigationController;
use App\Http\Controllers\LabOrderController;
use App\Http\Controllers\InvestigationOrderController;
use App\Http\Controllers\LabResultController;
use App\Http\Controllers\RadiologyResultController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DoctorShareController;
use App\Http\Controllers\LanguageController;
use App\Models\Patient;
use Illuminate\Http\Request;
use App\Http\Controllers\TenantRegistrationController;
use Illuminate\Support\Facades\Route;

// Installation Routes
Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('index');
    Route::get('/requirements', [InstallController::class, 'requirements'])->name('requirements');
    Route::get('/database', [InstallController::class, 'database'])->name('database');
    Route::post('/database/setup', [InstallController::class, 'setupDatabase'])->name('database.setup');
    Route::get('/admin', [InstallController::class, 'admin'])->name('admin');
    Route::post('/admin/setup', [InstallController::class, 'setupAdmin'])->name('admin.setup');
    Route::get('/seed', [InstallController::class, 'seed'])->name('seed');
    Route::post('/seed/run', [InstallController::class, 'runSeed'])->name('seed.run');
    Route::get('/complete', [InstallController::class, 'complete'])->name('complete');
});

/*
|--------------------------------------------------------------------------
| Tenant Registration (Landlord Routes — no tenant required)
|--------------------------------------------------------------------------
*/
Route::prefix('register')->name('tenant.')->group(function () {
    Route::get('/', [TenantRegistrationController::class, 'create'])->name('register');
    Route::post('/', [TenantRegistrationController::class, 'store'])->name('register.store');
    Route::get('/{tenant}/provisioning', [TenantRegistrationController::class, 'provisioning'])->name('provisioning');
    Route::get('/{tenant}/status', [TenantRegistrationController::class, 'status'])->name('status');
});

// Public page route (Terms & Conditions, Privacy Policy, etc.)
Route::get('/page/{slug}', function ($slug) {
    $page = \App\Models\Page::where('slug', $slug)->where('is_active', true)->firstOrFail();
    return view('page', compact('page'));
})->name('page.show');

// Contact page
Route::get('/contact', function () {
    $site = \App\Models\SiteSetting::getAll();
    return view('contact', compact('site'));
})->name('contact');

Route::post('/contact', function (\Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'phone' => 'nullable|string|max:50',
        'subject' => 'required|string|max:255',
        'message' => 'required|string|max:5000',
    ]);
    \App\Models\ContactMessage::create($validated);
    return back()->with('success', 'Thank you for your message. We will get back to you shortly.');
})->name('contact.submit');

// Documentation page
Route::get('/documentation', function () {
    return view('documentation');
})->name('documentation');

// Central Login (main domain — finds tenant by email)
Route::get('/signin', [\App\Http\Controllers\CentralLoginController::class, 'showLogin'])->name('central.login');
Route::post('/signin', [\App\Http\Controllers\CentralLoginController::class, 'login'])->name('central.login.submit');

// Language Switcher Route
Route::get('/language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

// PayFast Webhook (server-to-server, no auth/tenant required)
Route::post('/billing/payfast/webhook', [\App\Http\Controllers\BillingController::class, 'webhook'])
    ->name('billing.payfast.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Paddle Webhook (server-to-server, no auth/tenant required)
Route::post('/paddle/webhook', [\App\Http\Controllers\SubscriptionController::class, 'paddleWebhook'])
    ->name('paddle.webhook')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

/*
|--------------------------------------------------------------------------
| Super Admin Panel (Landlord Domain — no tenant required)
|--------------------------------------------------------------------------
*/
Route::prefix('super-admin')->name('super-admin.')->group(function () {
    // Auth
    Route::get('/login', [\App\Http\Controllers\SuperAdmin\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\SuperAdmin\AuthController::class, 'login']);
    Route::post('/logout', [\App\Http\Controllers\SuperAdmin\AuthController::class, 'logout'])->name('logout');

    // Protected routes
    Route::middleware('super_admin')->group(function () {
        Route::get('/', [\App\Http\Controllers\SuperAdmin\DashboardController::class, 'index'])->name('dashboard');

        // Profile
        Route::get('/profile', [\App\Http\Controllers\SuperAdmin\ProfileController::class, 'edit'])->name('profile');
        Route::patch('/profile', [\App\Http\Controllers\SuperAdmin\ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [\App\Http\Controllers\SuperAdmin\ProfileController::class, 'updatePassword'])->name('profile.password');

        // Tenant management
        Route::get('/tenants', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'index'])->name('tenants.index');
        Route::get('/tenants/{tenant}', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'show'])->name('tenants.show');
        Route::post('/tenants/{tenant}/suspend', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('/tenants/{tenant}/activate', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'activate'])->name('tenants.activate');
        Route::post('/tenants/{tenant}/change-plan', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'changePlan'])->name('tenants.change-plan');

        // Plan management
        Route::get('/plans', [\App\Http\Controllers\SuperAdmin\PlanController::class, 'index'])->name('plans.index');
        Route::get('/plans/create', [\App\Http\Controllers\SuperAdmin\PlanController::class, 'create'])->name('plans.create');
        Route::post('/plans', [\App\Http\Controllers\SuperAdmin\PlanController::class, 'store'])->name('plans.store');
        Route::get('/plans/{plan}/edit', [\App\Http\Controllers\SuperAdmin\PlanController::class, 'edit'])->name('plans.edit');
        Route::put('/plans/{plan}', [\App\Http\Controllers\SuperAdmin\PlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [\App\Http\Controllers\SuperAdmin\PlanController::class, 'destroy'])->name('plans.destroy');

        // Page management
        Route::get('/pages', [\App\Http\Controllers\SuperAdmin\PageController::class, 'index'])->name('pages.index');
        Route::get('/pages/create', [\App\Http\Controllers\SuperAdmin\PageController::class, 'create'])->name('pages.create');
        Route::post('/pages', [\App\Http\Controllers\SuperAdmin\PageController::class, 'store'])->name('pages.store');
        Route::get('/pages/{page}/edit', [\App\Http\Controllers\SuperAdmin\PageController::class, 'edit'])->name('pages.edit');
        Route::put('/pages/{page}', [\App\Http\Controllers\SuperAdmin\PageController::class, 'update'])->name('pages.update');
        Route::delete('/pages/{page}', [\App\Http\Controllers\SuperAdmin\PageController::class, 'destroy'])->name('pages.destroy');

        // Site settings
        Route::get('/site-settings', [\App\Http\Controllers\SuperAdmin\SiteSettingsController::class, 'edit'])->name('site-settings.edit');
        Route::put('/site-settings', [\App\Http\Controllers\SuperAdmin\SiteSettingsController::class, 'update'])->name('site-settings.update');

        // Contact messages
        Route::get('/contact-messages', [\App\Http\Controllers\SuperAdmin\ContactMessageController::class, 'index'])->name('contact-messages.index');
        Route::get('/contact-messages/{contactMessage}', [\App\Http\Controllers\SuperAdmin\ContactMessageController::class, 'show'])->name('contact-messages.show');
        Route::delete('/contact-messages/{contactMessage}', [\App\Http\Controllers\SuperAdmin\ContactMessageController::class, 'destroy'])->name('contact-messages.destroy');

        // Payment gateways
        Route::get('/payment-gateways', [\App\Http\Controllers\SuperAdmin\PaymentGatewayController::class, 'index'])->name('payment-gateways.index');
        Route::get('/payment-gateways/{paymentGateway}/edit', [\App\Http\Controllers\SuperAdmin\PaymentGatewayController::class, 'edit'])->name('payment-gateways.edit');
        Route::put('/payment-gateways/{paymentGateway}', [\App\Http\Controllers\SuperAdmin\PaymentGatewayController::class, 'update'])->name('payment-gateways.update');
        Route::patch('/payment-gateways/{paymentGateway}/toggle', [\App\Http\Controllers\SuperAdmin\PaymentGatewayController::class, 'toggle'])->name('payment-gateways.toggle');
    });
});

/*
|--------------------------------------------------------------------------
| Landing Page (Main Domain — no tenant required)
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    $tenant = \App\Models\Tenant::current();
    if ($tenant) {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }
        return redirect(config('app.url') . '/signin');
    }

    try {
        $landingPlans = \App\Models\Plan::active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    } catch (\Throwable $e) {
        $landingPlans = null;
    }

    $salesEmail = \App\Models\SiteSetting::get('sales_contact_email');

    return view('landing', compact('landingPlans', 'salesEmail'));
})->name('home');

/*
|--------------------------------------------------------------------------
| Tenant-Aware Routes
|--------------------------------------------------------------------------
| All routes below require a valid tenant (resolved via subdomain).
| The 'tenant' middleware group runs NeedsTenant + EnsureValidTenantSession.
*/
Route::middleware('tenant')->group(function () {

Route::middleware('auth')->group(function () {
    
    Route::get('/dashboard', function () {
        $user = auth()->user();

        // Near-expiry count for the dashboard banner (admins and pharmacists)
        $nearExpiryCount = 0;
        try {
            if ($user->hasAnyRole(['Super Admin', 'Hospital Administrator', 'Pharmacist'])) {
                $nearExpiryCount = \App\Models\InventoryTransaction::nearExpiry(6)->count();
            }
        } catch (\Throwable $e) {
            \Log::warning('[Dashboard] Failed to load near-expiry count', ['error' => $e->getMessage()]);
        }

        // Check if user is a doctor
        if ($user->hasRole('Doctor')) {
            $doctor = \App\Models\Doctor::where('user_id', $user->id)->first();
            if ($doctor) {
                $assignedPatients = $doctor->assignedPatients()->limit(5)->get();
                $totalAssigned = $doctor->assignedPatients()->count();
                return view('admin.dashboard', compact('assignedPatients', 'totalAssigned', 'nearExpiryCount'));
            }
        }

        return view('admin.dashboard', compact('nearExpiryCount'));
    })->name('dashboard');
    
    // Doctor assignments route
    Route::get('/doctor/assignments', function () {
        $user = auth()->user();
        
        if (!$user->hasRole('Doctor')) {
            abort(403);
        }
        
        $doctor = \App\Models\Doctor::where('user_id', $user->id)->first();
        if (!$doctor) {
            abort(404);
        }
        
        $assignedPatients = $doctor->assignedPatients()->paginate(8);
        return view('admin.doctor.assignments', compact('assignedPatients'));
    })->name('doctor.assignments');
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Billing & Subscription Routes
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [\App\Http\Controllers\BillingController::class, 'index'])->name('index');
        Route::post('/subscribe', [\App\Http\Controllers\BillingController::class, 'subscribe'])->name('subscribe');
        Route::get('/payfast/success', [\App\Http\Controllers\BillingController::class, 'success'])->name('payfast.success');
        Route::get('/payfast/cancel', [\App\Http\Controllers\BillingController::class, 'cancel'])->name('payfast.cancel');
    });

    // Subscription management (Paddle)
    Route::get('/subscription', [\App\Http\Controllers\SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/subscription/activate', [\App\Http\Controllers\SubscriptionController::class, 'activate'])->name('subscription.activate');
});

Route::middleware('auth')->group(function () {
    Route::resource('patients', PatientController::class)->middleware('permission:view patients|create patients|edit patients|delete patients');
    Route::get('patients/{patient}/history', [PatientController::class, 'history'])->name('patients.history')->middleware('permission:view patients');
    Route::resource('doctors', DoctorController::class)->middleware('permission:view doctors|create doctors|edit doctors|delete doctors');
    Route::resource('departments', DepartmentController::class)->middleware('permission:view departments|create departments|edit departments|delete departments');
    Route::resource('visits', VisitController::class)->middleware('permission:view visits|create visits|edit visits|delete visits');
    Route::get('visits/{visit}/workflow', [VisitController::class, 'workflow'])->name('visits.workflow')->middleware('permission:view visits');
    Route::get('visits/{visit}/print', [VisitController::class, 'print'])->name('visits.print')->middleware('permission:view visits');
    Route::post('visits/{visit}/vitals', [VisitController::class, 'updateVitals'])->name('visits.vitals')->middleware('permission:edit visits');
    Route::post('visits/{visit}/assign-doctor', [VisitController::class, 'assignDoctor'])->name('visits.assign-doctor')->middleware('permission:edit visits');
    Route::post('visits/{visit}/consultation', [VisitController::class, 'updateConsultation'])->name('visits.consultation')->middleware('permission:edit visits');
    Route::post('visits/{visit}/order-test', [VisitController::class, 'orderTest'])->name('visits.order-test')->middleware('permission:edit visits');
    Route::post('visits/{visit}/add-test-orders', [VisitController::class, 'addTestOrders'])->name('visits.add-test-orders')->middleware('permission:edit visits');
    Route::delete('test-orders/{testOrder}', [VisitController::class, 'removeTestOrder'])->name('test-orders.remove')->middleware('permission:edit visits');
    Route::post('test-orders/{testOrder}/result', [VisitController::class, 'updateTestResult'])->name('test-orders.result')->middleware('permission:edit visits');
    Route::get('visits/{visit}/complete', [VisitController::class, 'completeVisit'])->name('visits.complete')->middleware('permission:edit visits');
    Route::post('visits/{visit}/check-patient', [VisitController::class, 'checkPatient'])->name('visits.check-patient')->middleware('permission:edit visits');
    Route::post('visits/{visit}/admit', [VisitController::class, 'admitPatient'])->name('visits.admit')->middleware('permission:edit visits');
    Route::post('visits/{visit}/discharge', [VisitController::class, 'dischargePatient'])->name('visits.discharge')->middleware('permission:edit visits');
    Route::post('visits/{visit}/triage', [VisitController::class, 'triagePatient'])->name('visits.triage')->middleware('permission:edit visits');
    Route::resource('appointments', AppointmentController::class)->middleware('permission:view appointments|create appointments|edit appointments|delete appointments');
    Route::get('calendar/events', [AppointmentController::class, 'getCalendarEvents'])->name('calendar.events')->middleware('permission:view appointments');
    
    // Billing Routes
    Route::resource('bills', BillController::class)->middleware('permission:view bills|create bills|edit bills|delete bills');
    Route::post('bills/{bill}/payment', [BillController::class, 'addPayment'])->name('bills.add-payment')->middleware('permission:create payments');
    Route::get('bills/{bill}/print', [BillController::class, 'print'])->name('bills.print')->middleware('permission:view bills');

    // Tax Configuration
    Route::resource('taxes', \App\Http\Controllers\TaxController::class)->middleware('permission:view bills|create bills');
    Route::post('taxes/calculate', [\App\Http\Controllers\TaxController::class, 'calculate'])->name('taxes.calculate');

    // Accounting
    Route::prefix('accounting')->name('accounting.')->middleware('permission:view accounting')->group(function () {
        Route::get('chart-of-accounts', [\App\Http\Controllers\AccountingController::class, 'chartOfAccounts'])->name('chart-of-accounts');
        Route::get('chart-of-accounts/create', [\App\Http\Controllers\AccountingController::class, 'createAccount'])->name('create-account');
        Route::post('chart-of-accounts', [\App\Http\Controllers\AccountingController::class, 'storeAccount'])->name('store-account');
        Route::get('general-ledger', [\App\Http\Controllers\AccountingController::class, 'generalLedger'])->name('general-ledger');
        Route::get('journal-entries', [\App\Http\Controllers\AccountingController::class, 'journalEntries'])->name('journal-entries');
        Route::get('patient-ledger', [\App\Http\Controllers\AccountingController::class, 'patientLedger'])->name('patient-ledger');
        Route::get('vendor-ledger', [\App\Http\Controllers\AccountingController::class, 'vendorLedger'])->name('vendor-ledger');
        Route::get('profit-loss', [\App\Http\Controllers\AccountingController::class, 'profitAndLoss'])->name('profit-loss');
        Route::get('balance-sheet', [\App\Http\Controllers\AccountingController::class, 'balanceSheet'])->name('balance-sheet');
    });

    // HR Module
    Route::prefix('hr')->name('hr.')->middleware('permission:view hr')->group(function () {
        Route::resource('employees', \App\Http\Controllers\EmployeeController::class);
        Route::post('employees/{employee}/documents', [\App\Http\Controllers\EmployeeController::class, 'uploadDocument'])->name('employees.upload-document');
        Route::delete('employees/documents/{document}', [\App\Http\Controllers\EmployeeController::class, 'deleteDocument'])->name('employees.delete-document');
        Route::resource('designations', \App\Http\Controllers\DesignationController::class);

        // Attendance
        Route::get('attendance', [\App\Http\Controllers\AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('attendance/mark', [\App\Http\Controllers\AttendanceController::class, 'markDaily'])->name('attendance.mark');
        Route::post('attendance/mark', [\App\Http\Controllers\AttendanceController::class, 'storeDaily'])->name('attendance.store-daily');
        Route::get('attendance/monthly', [\App\Http\Controllers\AttendanceController::class, 'monthly'])->name('attendance.monthly');

        // Leave Requests
        Route::get('leave', [\App\Http\Controllers\LeaveController::class, 'index'])->name('leave.index');
        Route::get('leave/create', [\App\Http\Controllers\LeaveController::class, 'create'])->name('leave.create');
        Route::post('leave', [\App\Http\Controllers\LeaveController::class, 'store'])->name('leave.store');
        Route::post('leave/{leaveRequest}/approve', [\App\Http\Controllers\LeaveController::class, 'approve'])->name('leave.approve');
        Route::post('leave/{leaveRequest}/reject', [\App\Http\Controllers\LeaveController::class, 'reject'])->name('leave.reject');
        Route::post('leave/{leaveRequest}/cancel', [\App\Http\Controllers\LeaveController::class, 'cancel'])->name('leave.cancel');
        Route::get('leave/balances', [\App\Http\Controllers\LeaveController::class, 'balances'])->name('leave.balances');

        // Leave Types
        Route::get('leave-types', [\App\Http\Controllers\LeaveController::class, 'types'])->name('leave-types.index');
        Route::get('leave-types/create', [\App\Http\Controllers\LeaveController::class, 'createType'])->name('leave-types.create');
        Route::post('leave-types', [\App\Http\Controllers\LeaveController::class, 'storeType'])->name('leave-types.store');
        Route::get('leave-types/{leaveType}/edit', [\App\Http\Controllers\LeaveController::class, 'editType'])->name('leave-types.edit');
        Route::put('leave-types/{leaveType}', [\App\Http\Controllers\LeaveController::class, 'updateType'])->name('leave-types.update');
        Route::delete('leave-types/{leaveType}', [\App\Http\Controllers\LeaveController::class, 'destroyType'])->name('leave-types.destroy');

        // Payroll
        Route::get('payroll', [\App\Http\Controllers\PayrollController::class, 'index'])->name('payroll.index');
        Route::post('payroll/generate', [\App\Http\Controllers\PayrollController::class, 'generate'])->name('payroll.generate');
        Route::get('payroll/{payrollRun}', [\App\Http\Controllers\PayrollController::class, 'show'])->name('payroll.show');
        Route::post('payroll/{payrollRun}/approve', [\App\Http\Controllers\PayrollController::class, 'approve'])->name('payroll.approve');
        Route::post('payroll/{payrollRun}/cancel', [\App\Http\Controllers\PayrollController::class, 'cancel'])->name('payroll.cancel');

        // Payslips
        Route::get('payslip/{payslip}', [\App\Http\Controllers\PayrollController::class, 'payslip'])->name('payroll.payslip');
        Route::get('payslip/{payslip}/print', [\App\Http\Controllers\PayrollController::class, 'printPayslip'])->name('payroll.print-payslip');
        Route::post('payslip/{payslip}/mark-paid', [\App\Http\Controllers\PayrollController::class, 'markPaid'])->name('payroll.mark-paid');

        // Salary Components
        Route::get('payroll-components', [\App\Http\Controllers\PayrollController::class, 'components'])->name('payroll.components');
        Route::get('payroll-components/create', [\App\Http\Controllers\PayrollController::class, 'createComponent'])->name('payroll.create-component');
        Route::post('payroll-components', [\App\Http\Controllers\PayrollController::class, 'storeComponent'])->name('payroll.store-component');
        Route::get('payroll-components/{salaryComponent}/edit', [\App\Http\Controllers\PayrollController::class, 'editComponent'])->name('payroll.edit-component');
        Route::put('payroll-components/{salaryComponent}', [\App\Http\Controllers\PayrollController::class, 'updateComponent'])->name('payroll.update-component');
        Route::delete('payroll-components/{salaryComponent}', [\App\Http\Controllers\PayrollController::class, 'destroyComponent'])->name('payroll.destroy-component');

        // Employee Salary Structure
        Route::get('employees/{employee}/salary', [\App\Http\Controllers\PayrollController::class, 'employeeSalary'])->name('payroll.employee-salary');
        Route::post('employees/{employee}/salary', [\App\Http\Controllers\PayrollController::class, 'updateEmployeeSalary'])->name('payroll.update-employee-salary');

        // Shifts
        Route::get('shifts', [\App\Http\Controllers\ShiftController::class, 'shifts'])->name('shifts.index');
        Route::get('shifts/create', [\App\Http\Controllers\ShiftController::class, 'createShift'])->name('shifts.create');
        Route::post('shifts', [\App\Http\Controllers\ShiftController::class, 'storeShift'])->name('shifts.store');
        Route::get('shifts/{shift}/edit', [\App\Http\Controllers\ShiftController::class, 'editShift'])->name('shifts.edit');
        Route::put('shifts/{shift}', [\App\Http\Controllers\ShiftController::class, 'updateShift'])->name('shifts.update');
        Route::delete('shifts/{shift}', [\App\Http\Controllers\ShiftController::class, 'destroyShift'])->name('shifts.destroy');

        // Duty Roster
        Route::get('duty-roster', [\App\Http\Controllers\ShiftController::class, 'roster'])->name('shifts.roster');
        Route::post('duty-roster', [\App\Http\Controllers\ShiftController::class, 'storeRoster'])->name('shifts.store-roster');
        Route::post('duty-roster/auto-generate', [\App\Http\Controllers\ShiftController::class, 'autoGenerate'])->name('shifts.auto-generate');

        // Shift Swap Requests
        Route::get('shift-swaps', [\App\Http\Controllers\ShiftController::class, 'swapRequests'])->name('shifts.swap-requests');
        Route::post('shift-swaps/{shiftSwapRequest}/approve', [\App\Http\Controllers\ShiftController::class, 'approveSwap'])->name('shifts.approve-swap');
        Route::post('shift-swaps/{shiftSwapRequest}/reject', [\App\Http\Controllers\ShiftController::class, 'rejectSwap'])->name('shifts.reject-swap');

        // Department Staff Management
        Route::get('department-staff', [\App\Http\Controllers\DepartmentStaffController::class, 'index'])->name('department-staff.index');
        Route::get('department-staff/{department}', [\App\Http\Controllers\DepartmentStaffController::class, 'show'])->name('department-staff.show');
        Route::put('department-staff/{department}/head', [\App\Http\Controllers\DepartmentStaffController::class, 'updateHead'])->name('department-staff.update-head');
        Route::post('department-staff/transfer', [\App\Http\Controllers\DepartmentStaffController::class, 'transferEmployee'])->name('department-staff.transfer');

        // Document Management
        Route::get('documents', [\App\Http\Controllers\DocumentManagementController::class, 'index'])->name('documents.index');
        Route::get('documents/compliance', [\App\Http\Controllers\DocumentManagementController::class, 'compliance'])->name('documents.compliance');
        Route::post('documents/{document}/verify', [\App\Http\Controllers\DocumentManagementController::class, 'verify'])->name('documents.verify');
        Route::post('documents/{document}/unverify', [\App\Http\Controllers\DocumentManagementController::class, 'unverify'])->name('documents.unverify');
        Route::get('documents/requirements', [\App\Http\Controllers\DocumentManagementController::class, 'requirements'])->name('documents.requirements');
        Route::get('documents/requirements/create', [\App\Http\Controllers\DocumentManagementController::class, 'createRequirement'])->name('documents.create-requirement');
        Route::post('documents/requirements', [\App\Http\Controllers\DocumentManagementController::class, 'storeRequirement'])->name('documents.store-requirement');
        Route::delete('documents/requirements/{documentRequirement}', [\App\Http\Controllers\DocumentManagementController::class, 'destroyRequirement'])->name('documents.destroy-requirement');
    });
    // Service import routes — must be BEFORE the resource route to avoid
    // the resource route capturing 'import' as a {service} parameter
    Route::post('services/import', [ServiceController::class, 'import'])->name('services.import')->middleware('permission:create services');
    Route::get('services/import-status', [ServiceController::class, 'importStatus'])->name('services.import-status')->middleware('permission:create services');

    Route::resource('services', ServiceController::class)->middleware('permission:view services|create services|edit services|delete services');
    
    // IPD Management Routes
    Route::resource('wards', WardController::class)->middleware('permission:view wards|create wards|edit wards|delete wards');
    Route::resource('beds', BedController::class)->middleware('permission:view beds|create beds|edit beds|delete beds');
    
    // Pharmacy Routes
    Route::resource('medicine-categories', MedicineCategoryController::class)->middleware('permission:view services|view pharmacy|manage pharmacy');
    Route::resource('medicine-brands', MedicineBrandController::class)->middleware('permission:view services|view pharmacy|manage pharmacy');
    Route::resource('medicines', MedicineController::class)->middleware('permission:view services|view pharmacy|manage pharmacy');
    Route::post('visits/{visit}/prescription', [VisitController::class, 'createPrescription'])->name('visits.prescription')->middleware('permission:edit visits');
    Route::post('visits/{visit}/order-multiple-lab-tests', [VisitController::class, 'orderMultipleLabTests'])->name('visits.order-multiple-lab-tests')->middleware('permission:edit visits');
    Route::post('prescriptions/{prescription}/dispense', [PrescriptionController::class, 'dispense'])->name('prescriptions.dispense')->middleware('permission:edit visits');

    // Prescription Instructions Routes
    Route::resource('prescription-instructions', \App\Http\Controllers\Admin\PrescriptionInstructionController::class)->middleware('permission:view services|view pharmacy|manage pharmacy');

    // Unit Routes
    Route::resource('units', UnitController::class)->middleware('permission:view services|view pharmacy|manage pharmacy');

    // Inventory Routes
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index')->middleware('permission:view services|view pharmacy|view inventory|manage inventory');
    Route::get('inventory/stock-in', [InventoryController::class, 'stockIn'])->name('inventory.stock-in')->middleware('permission:view services|manage pharmacy|manage inventory');
    Route::post('inventory/stock-in', [InventoryController::class, 'processStockIn'])->name('inventory.process-stock-in')->middleware('permission:view services|manage pharmacy|manage inventory');
    Route::get('inventory/stock-out', [InventoryController::class, 'stockOut'])->name('inventory.stock-out')->middleware('permission:view services|manage pharmacy|manage inventory');
    Route::post('inventory/stock-out', [InventoryController::class, 'processStockOut'])->name('inventory.process-stock-out')->middleware('permission:view services|manage pharmacy|manage inventory');
    Route::get('inventory/low-stock', [InventoryController::class, 'lowStock'])->name('inventory.low-stock')->middleware('permission:view services|view pharmacy|view inventory|manage inventory');
    Route::get('inventory/expiring', [InventoryController::class, 'expiring'])->name('inventory.expiring')->middleware('permission:view services|view pharmacy|view inventory|manage inventory');

    // Supplier Routes
    Route::resource('suppliers', SupplierController::class)->middleware('permission:view services|view pharmacy|manage pharmacy');

    // Purchase Routes
    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show'])->middleware('permission:view services|view pharmacy|manage pharmacy');
    Route::post('purchases/{purchase}/approve', [PurchaseController::class, 'approve'])->name('purchases.approve')->middleware('permission:view services|manage pharmacy');
    Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive')->middleware('permission:view services|manage pharmacy');
    Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel')->middleware('permission:view services|manage pharmacy');
    
    // Laboratory Routes
    Route::resource('investigations', InvestigationController::class)->middleware('permission:view investigations|create investigations|edit investigations|delete investigations');
    Route::post('investigations/import', [InvestigationController::class, 'import'])->name('investigations.import')->middleware('permission:create investigations');
    Route::get('investigations/import-status', [InvestigationController::class, 'importStatus'])->name('investigations.import-status')->middleware('permission:create investigations');
    Route::resource('lab-tests', InvestigationController::class)->middleware('permission:view investigations|create investigations|edit investigations|delete investigations');
    
    // Investigation Orders (new routes)
    Route::resource('investigation-orders', InvestigationOrderController::class)->middleware('permission:view investigation orders|create investigation orders|edit investigation orders|delete investigation orders');
    Route::post('investigation-orders/{investigationOrder}/collect-sample', [InvestigationOrderController::class, 'collectSample'])->name('investigation-orders.collect-sample')->middleware('permission:edit investigation orders');
    Route::post('investigation-orders/{investigationOrder}/receive-sample', [InvestigationOrderController::class, 'receiveSample'])->name('investigation-orders.receive-sample')->middleware('permission:edit investigation orders');
    
    // Lab Orders (legacy routes - kept for backward compatibility)
    Route::resource('lab-orders', LabOrderController::class)->middleware('permission:view lab orders|create lab orders|edit lab orders|delete lab orders');
    Route::post('lab-orders/{labOrder}/collect-sample', [LabOrderController::class, 'collectSample'])->name('lab-orders.collect-sample')->middleware('permission:edit lab orders');
    Route::post('lab-orders/{labOrder}/receive-sample', [LabOrderController::class, 'receiveSample'])->name('lab-orders.receive-sample')->middleware('permission:edit lab orders');
    
    // Lab Results - Custom routes BEFORE resource route
    Route::get('lab-results/create-batch', [LabResultController::class, 'createBatch'])->name('lab-results.create-batch')->middleware('permission:create lab results');
    Route::post('lab-results/store-batch', [LabResultController::class, 'storeBatch'])->name('lab-results.store-batch')->middleware('permission:create lab results');
    Route::get('lab-orders/{orderItem}/results/create', [LabResultController::class, 'create'])->name('lab-orders.results.create')->middleware('permission:create lab results');
    Route::post('lab-orders/{orderItem}/results', [LabResultController::class, 'store'])->name('lab-orders.results.store')->middleware('permission:create lab results');
    Route::post('lab-results/{labResult}/verify', [LabResultController::class, 'verify'])->name('lab-results.verify')->middleware('permission:edit lab results');
    Route::get('lab-results/{labResult}/report', [LabResultController::class, 'report'])->name('lab-results.report')->middleware('permission:view lab results');
    
    Route::resource('lab-results', LabResultController::class)->middleware('permission:view lab results|create lab results|edit lab results|delete lab results');
    
    // Radiology Results Routes
    Route::get('investigation-orders/{investigationOrder}/radiology-results/create', [RadiologyResultController::class, 'create'])->name('radiology-results.create')->middleware('permission:create radiology results');
    Route::post('investigation-orders/{investigationOrder}/radiology-results', [RadiologyResultController::class, 'store'])->name('radiology-results.store')->middleware('permission:create radiology results');
    Route::resource('radiology-results', RadiologyResultController::class)->except(['create', 'store'])->middleware('permission:view radiology results|edit radiology results|delete radiology results');

    
    // Doctor Share Routes
    Route::prefix('doctor-share')->name('doctor-share.')->middleware('permission:manage doctor shares')->group(function () {
        // Share Rules
        Route::get('rules', [DoctorShareController::class, 'rulesIndex'])->name('rules.index');
        Route::get('rules/create', [DoctorShareController::class, 'rulesCreate'])->name('rules.create');
        Route::post('rules', [DoctorShareController::class, 'rulesStore'])->name('rules.store');
        Route::get('rules/{rule}/edit', [DoctorShareController::class, 'rulesEdit'])->name('rules.edit');
        Route::put('rules/{rule}', [DoctorShareController::class, 'rulesUpdate'])->name('rules.update');
        Route::delete('rules/{rule}', [DoctorShareController::class, 'rulesDestroy'])->name('rules.destroy');
        Route::patch('rules/{rule}/toggle', [DoctorShareController::class, 'toggleRule'])->name('rules.toggle');

        // Share Items
        Route::get('items', [DoctorShareController::class, 'itemsIndex'])->name('items.index');

        // Settlements
        Route::get('settlements', [DoctorShareController::class, 'settlementsIndex'])->name('settlements.index');
        Route::get('settlements/preview', [DoctorShareController::class, 'settlementsPreview'])->name('settlements.preview');
        Route::post('settlements', [DoctorShareController::class, 'settlementsStore'])->name('settlements.store');
        Route::get('settlements/{settlement}', [DoctorShareController::class, 'settlementsShow'])->name('settlements.show');

        // Reports
        Route::get('reports', [DoctorShareController::class, 'reportsIndex'])->name('reports.index');
        Route::get('reports/print', [DoctorShareController::class, 'reportsPrint'])->name('reports.print');
    });

    // RBAC Routes
    Route::resource('users', UserController::class)->middleware('role:Super Admin|Hospital Administrator');
    Route::resource('roles', RoleController::class)->middleware('permission:view roles|create roles|edit roles|delete roles');
    Route::resource('permissions', PermissionController::class)->middleware('permission:view permissions|create permissions|edit permissions|delete permissions');
    
    // Reports Routes
    Route::prefix('reports')->name('reports.')->middleware('permission:view reports')->group(function () {
        Route::get('daily-cash-register', [ReportController::class, 'dailyCashRegister'])->name('daily-cash-register');
        Route::get('patient-visits', [ReportController::class, 'patientVisits'])->name('patient-visits');
        Route::get('revenue', [ReportController::class, 'revenue'])->name('revenue');
        Route::get('outstanding-bills', [ReportController::class, 'outstandingBills'])->name('outstanding-bills');
        Route::get('lab-tests', [ReportController::class, 'labTests'])->name('lab-tests');
        Route::get('medicine-sales', [ReportController::class, 'medicineSales'])->name('medicine-sales');
        Route::get('inventory-status', [ReportController::class, 'inventoryStatus'])->name('inventory-status');
        Route::get('expiry-report', [ReportController::class, 'expiryReport'])->name('expiry-report');
        Route::get('doctor-performance', [ReportController::class, 'doctorPerformance'])->name('doctor-performance');
        Route::get('appointment-statistics', [ReportController::class, 'appointmentStatistics'])->name('appointment-statistics');
        Route::get('ipd-report', [ReportController::class, 'ipdReport'])->name('ipd-report');
        Route::get('department-performance', [ReportController::class, 'departmentPerformance'])->name('department-performance');
        Route::get('patient-demographics', [ReportController::class, 'patientDemographics'])->name('patient-demographics');
    });
    
    // Audit Logs
    Route::resource('audit-logs', AuditLogController::class)->only(['index', 'show'])->middleware('role:Super Admin|Hospital Administrator');
    
    // Backup & Restore Routes
    Route::prefix('backup')->name('backup.')->middleware('role:Super Admin|Hospital Administrator')->group(function () {
        Route::get('/', [\App\Http\Controllers\BackupController::class, 'index'])->name('index');
        Route::post('/create', [\App\Http\Controllers\BackupController::class, 'create'])->name('create');
        Route::get('/download/{filename}', [\App\Http\Controllers\BackupController::class, 'download'])->name('download');
        Route::delete('/delete/{filename}', [\App\Http\Controllers\BackupController::class, 'destroy'])->name('destroy');
        Route::post('/restore/{filename}', [\App\Http\Controllers\BackupController::class, 'restore'])->name('restore');
    });
    
    // Patient Search API
    Route::get('api/patients/search', function (Request $request) {
        $phone = $request->query('phone');
        
        if (!$phone || strlen($phone) < 3) {
            return response()->json(['found' => false]);
        }
        
        $patient = Patient::where('phone', 'like', "%{$phone}%")->first();
        
        if ($patient) {
            return response()->json([
                'found' => true,
                'patient' => [
                    'id' => $patient->id,
                    'name' => $patient->name,
                    'patient_no' => $patient->patient_no,
                    'phone' => $patient->phone
                ]
            ]);
        }
        
        return response()->json(['found' => false]);
    })->name('api.patients.search');
});

require __DIR__.'/auth.php';

}); // end tenant middleware group
