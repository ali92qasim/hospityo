<?php

use App\Http\Controllers\InstallController;
use App\Http\Controllers\SuperAdmin\AuthController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\SuperAdmin\ContactMessageController;
use App\Http\Controllers\SuperAdmin\TenantController;
use App\Http\Controllers\SuperAdmin\ProfileController as SuperAdminProfileController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CentralLoginController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\SuperAdmin\PageController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\SuperAdmin\PaymentGatewayController;
use App\Http\Controllers\SuperAdmin\SiteSettingsController;
use App\Http\Controllers\Admin\PrescriptionInstructionController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShiftController;
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
use App\Http\Controllers\PublicLabReportController;
use App\Http\Controllers\RadiologyResultController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DoctorShareController;
use App\Http\Controllers\DepartmentStaffController;
use App\Http\Controllers\DocumentManagementController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\OTController;
use App\Http\Controllers\OtConsumableController;
use App\Http\Controllers\OperativeMonitoringController;
use App\Http\Controllers\PacController;
use App\Http\Controllers\SterilizationController;
use App\Http\Controllers\SurgicalChecklistController;
use App\Http\Controllers\TenantRegistrationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

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
Route::get('/page/{slug}', [PublicPageController::class, 'show'])->name('page.show');

// Contact page
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.submit');

// Documentation page
Route::get('/documentation', [DocumentationController::class, 'index'])->name('documentation');

// Central Login (main domain — finds tenant by email)
Route::get('/signin', [CentralLoginController::class, 'showLogin'])->name('central.login');
Route::post('/signin', [CentralLoginController::class, 'login'])->name('central.login.submit');

// Language Switcher Route
Route::get('/language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

// PayFast Webhook (server-to-server, no auth/tenant required)
Route::post('/billing/payfast/webhook', [BillingController::class, 'webhook'])
    ->name('billing.payfast.webhook')
    ->withoutMiddleware([VerifyCsrfToken::class]);

// Paddle Webhook (server-to-server, no auth/tenant required)
Route::post('/paddle/webhook', [SubscriptionController::class, 'paddleWebhook'])
    ->name('paddle.webhook')
    ->withoutMiddleware([VerifyCsrfToken::class]);

/*
|--------------------------------------------------------------------------
| Super Admin Panel (Landlord Domain — no tenant required)
|--------------------------------------------------------------------------
*/
Route::prefix('super-admin')->name('super-admin.')->group(function () {
    // Auth
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Protected routes
    Route::middleware('super_admin')->group(function () {
        Route::get('/', [SuperAdminDashboardController::class, 'index'])->name('dashboard');

        // Profile
        Route::get('/profile', [SuperAdminProfileController::class, 'edit'])->name('profile');
        Route::patch('/profile', [SuperAdminProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [SuperAdminProfileController::class, 'updatePassword'])->name('profile.password');

        // Tenant management
        Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
        Route::post('/tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('/tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
        Route::post('/tenants/{tenant}/change-plan', [TenantController::class, 'changePlan'])->name('tenants.change-plan');

        // Plan management
        Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
        Route::get('/plans/create', [PlanController::class, 'create'])->name('plans.create');
        Route::post('/plans', [PlanController::class, 'store'])->name('plans.store');
        Route::get('/plans/{plan}/edit', [PlanController::class, 'edit'])->name('plans.edit');
        Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');

        // Page management
        Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
        Route::get('/pages/create', [PageController::class, 'create'])->name('pages.create');
        Route::post('/pages', [PageController::class, 'store'])->name('pages.store');
        Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
        Route::put('/pages/{page}', [PageController::class, 'update'])->name('pages.update');
        Route::delete('/pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');

        // Site settings
        Route::get('/site-settings', [SiteSettingsController::class, 'edit'])->name('site-settings.edit');
        Route::put('/site-settings', [SiteSettingsController::class, 'update'])->name('site-settings.update');

        // Contact messages
        Route::get('/contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages.index');
        Route::get('/contact-messages/{contactMessage}', [ContactMessageController::class, 'show'])->name('contact-messages.show');
        Route::delete('/contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy'])->name('contact-messages.destroy');

        // Payment gateways
        Route::get('/payment-gateways', [PaymentGatewayController::class, 'index'])->name('payment-gateways.index');
        Route::get('/payment-gateways/{paymentGateway}/edit', [PaymentGatewayController::class, 'edit'])->name('payment-gateways.edit');
        Route::put('/payment-gateways/{paymentGateway}', [PaymentGatewayController::class, 'update'])->name('payment-gateways.update');
        Route::patch('/payment-gateways/{paymentGateway}/toggle', [PaymentGatewayController::class, 'toggle'])->name('payment-gateways.toggle');
    });
});

/*
|--------------------------------------------------------------------------
| Landing Page (Main Domain — no tenant required)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| Tenant-Aware Routes
|--------------------------------------------------------------------------
| All routes below require a valid tenant (resolved via subdomain).
| The 'tenant' middleware group runs NeedsTenant + EnsureValidTenantSession.
*/
Route::middleware('tenant')->group(function () {

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/doctor/assignments', [DoctorController::class, 'assignments'])->name('doctor.assignments');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index')->middleware('permission:manage settings');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update')->middleware('permission:manage settings');

    // Billing & Subscription Routes
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [BillingController::class, 'index'])->name('index');
        Route::post('/subscribe', [BillingController::class, 'subscribe'])->name('subscribe');
        Route::get('/payfast/success', [BillingController::class, 'success'])->name('payfast.success');
        Route::get('/payfast/cancel', [BillingController::class, 'cancel'])->name('payfast.cancel');
    });

    // Subscription management (Paddle)
    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('/subscription/activate', [SubscriptionController::class, 'activate'])->name('subscription.activate');
});

Route::middleware('auth')->group(function () {
    Route::get('/patients/data', [PatientController::class, 'data'])
        ->name('patients.data')
        ->middleware('permission:view patients|create patients|edit patients|delete patients');
    Route::resource('patients', PatientController::class)->middleware('permission:view patients|create patients|edit patients|delete patients');
    Route::get('patients/{patient}/history', [PatientController::class, 'history'])->name('patients.history')->middleware('permission:view patients');
    Route::get('/doctors/data', [DoctorController::class, 'data'])
        ->name('doctors.data')
        ->middleware('permission:view doctors|create doctors|edit doctors|delete doctors');
    Route::resource('doctors', DoctorController::class)->middleware('permission:view doctors|create doctors|edit doctors|delete doctors');
    Route::resource('departments', DepartmentController::class)->middleware('permission:view departments|create departments|edit departments|delete departments');
    Route::get('/visits/data', [VisitController::class, 'data'])
        ->name('visits.data')
        ->middleware('permission:view visits|create visits|edit visits|delete visits');
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
    Route::post('visits/{visit}/admission-advance', [VisitController::class, 'storeAdmissionAdvance'])->name('visits.admission-advance')->middleware('permission:edit visits');
    Route::post('visits/{visit}/triage', [VisitController::class, 'triagePatient'])->name('visits.triage')->middleware('permission:edit visits');
    Route::resource('appointments', AppointmentController::class)->middleware('permission:view appointments|create appointments|edit appointments|delete appointments');
    Route::get('calendar/events', [AppointmentController::class, 'getCalendarEvents'])->name('calendar.events')->middleware('permission:view appointments');

    // Billing Routes
    Route::get('/bills/data', [BillController::class, 'data'])
        ->name('bills.data')
        ->middleware('permission:view bills|create bills|edit bills|delete bills');
    Route::resource('bills', BillController::class)->middleware('permission:view bills|create bills|edit bills|delete bills');
    Route::post('bills/{bill}/payment', [BillController::class, 'addPayment'])->name('bills.add-payment')->middleware('permission:create payments');
    Route::put('bills/{bill}/payment/{payment}', [BillController::class, 'updatePayment'])->name('bills.update-payment')->middleware('permission:edit payments')->scopeBindings();
    Route::delete('bills/{bill}/payment/{payment}', [BillController::class, 'removePayment'])->name('bills.remove-payment')->middleware('permission:delete payments')->scopeBindings();
    Route::get('bills/{bill}/print', [BillController::class, 'print'])->name('bills.print')->middleware('permission:view bills');

    // Tax Configuration
    Route::resource('taxes', TaxController::class)->middleware('permission:view bills|create bills');
    Route::post('taxes/calculate', [TaxController::class, 'calculate'])->name('taxes.calculate')->middleware('permission:view bills|create bills');

    // Accounting
    Route::prefix('accounting')->name('accounting.')->middleware('permission:view accounting')->group(function () {
        Route::get('chart-of-accounts', [AccountingController::class, 'chartOfAccounts'])->name('chart-of-accounts');
        Route::get('chart-of-accounts/create', [AccountingController::class, 'createAccount'])->name('create-account');
        Route::post('chart-of-accounts', [AccountingController::class, 'storeAccount'])->name('store-account');
        Route::get('chart-of-accounts/{account}/edit', [AccountingController::class, 'editAccount'])->name('edit-account');
        Route::put('chart-of-accounts/{account}', [AccountingController::class, 'updateAccount'])->name('update-account');
        Route::get('deposit', [AccountingController::class, 'deposit'])->name('deposit');
        Route::post('deposit', [AccountingController::class, 'processDeposit'])->name('process-deposit');
        Route::get('transfer', [AccountingController::class, 'transfer'])->name('transfer');
        Route::post('transfer', [AccountingController::class, 'processTransfer'])->name('process-transfer');
        Route::get('general-ledger', [AccountingController::class, 'generalLedger'])->name('general-ledger');
        Route::get('journal-entries', [AccountingController::class, 'journalEntries'])->name('journal-entries');
        Route::get('journal-entries/create', [AccountingController::class, 'createJournalEntry'])->name('create-journal-entry');
        Route::post('journal-entries', [AccountingController::class, 'storeJournalEntry'])->name('store-journal-entry');
        Route::get('journal-entries/{journalEntry}/edit', [AccountingController::class, 'editJournalEntry'])->name('edit-journal-entry');
        Route::put('journal-entries/{journalEntry}', [AccountingController::class, 'updateJournalEntry'])->name('update-journal-entry');
        Route::get('patient-ledger', [AccountingController::class, 'patientLedger'])->name('patient-ledger');
        Route::get('vendor-ledger', [AccountingController::class, 'vendorLedger'])->name('vendor-ledger');
        Route::get('employee-ledger', [AccountingController::class, 'employeeLedger'])->name('employee-ledger');
        Route::get('profit-loss', [AccountingController::class, 'profitAndLoss'])->name('profit-loss');
        Route::get('balance-sheet', [AccountingController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('fiscal-years', [AccountingController::class, 'fiscalYears'])->name('fiscal-years');
        Route::get('fiscal-years/{fiscalYear}/pre-close', [AccountingController::class, 'preCloseSummary'])->name('fiscal-years.pre-close');
        Route::post('fiscal-years/{fiscalYear}/close', [AccountingController::class, 'closeFiscalYear'])->name('fiscal-years.close');
    });

    // HR Module
    Route::prefix('hr')->name('hr.')->middleware('permission:view hr')->group(function () {
        Route::resource('employees', EmployeeController::class);
        Route::post('employees/{employee}/documents', [EmployeeController::class, 'uploadDocument'])->name('employees.upload-document');
        Route::delete('employees/documents/{document}', [EmployeeController::class, 'deleteDocument'])->name('employees.delete-document');
        Route::resource('designations', DesignationController::class);

        // Attendance
        Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('attendance/mark', [AttendanceController::class, 'markDaily'])->name('attendance.mark');
        Route::post('attendance/mark', [AttendanceController::class, 'storeDaily'])->name('attendance.store-daily');
        Route::get('attendance/monthly', [AttendanceController::class, 'monthly'])->name('attendance.monthly');

        // Leave Requests
        Route::get('leave', [LeaveController::class, 'index'])->name('leave.index');
        Route::get('leave/create', [LeaveController::class, 'create'])->name('leave.create');
        Route::post('leave', [LeaveController::class, 'store'])->name('leave.store');
        Route::post('leave/{leaveRequest}/approve', [LeaveController::class, 'approve'])->name('leave.approve');
        Route::post('leave/{leaveRequest}/reject', [LeaveController::class, 'reject'])->name('leave.reject');
        Route::post('leave/{leaveRequest}/cancel', [LeaveController::class, 'cancel'])->name('leave.cancel');
        Route::get('leave/balances', [LeaveController::class, 'balances'])->name('leave.balances');

        // Leave Types
        Route::get('leave-types', [LeaveController::class, 'types'])->name('leave-types.index');
        Route::get('leave-types/create', [LeaveController::class, 'createType'])->name('leave-types.create');
        Route::post('leave-types', [LeaveController::class, 'storeType'])->name('leave-types.store');
        Route::get('leave-types/{leaveType}/edit', [LeaveController::class, 'editType'])->name('leave-types.edit');
        Route::put('leave-types/{leaveType}', [LeaveController::class, 'updateType'])->name('leave-types.update');
        Route::delete('leave-types/{leaveType}', [LeaveController::class, 'destroyType'])->name('leave-types.destroy');

        // Payroll
        Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
        Route::get('payroll/{payrollRun}', [PayrollController::class, 'show'])->name('payroll.show');
        Route::post('payroll/{payrollRun}/approve', [PayrollController::class, 'approve'])->name('payroll.approve');
        Route::post('payroll/{payrollRun}/cancel', [PayrollController::class, 'cancel'])->name('payroll.cancel');

        // Payslips
        Route::get('payslip/{payslip}', [PayrollController::class, 'payslip'])->name('payroll.payslip');
        Route::get('payslip/{payslip}/print', [PayrollController::class, 'printPayslip'])->name('payroll.print-payslip');
        Route::post('payslip/{payslip}/mark-paid', [PayrollController::class, 'markPaid'])->name('payroll.mark-paid');

        // Salary Components
        Route::get('payroll-components', [PayrollController::class, 'components'])->name('payroll.components');
        Route::get('payroll-components/create', [PayrollController::class, 'createComponent'])->name('payroll.create-component');
        Route::post('payroll-components', [PayrollController::class, 'storeComponent'])->name('payroll.store-component');
        Route::get('payroll-components/{salaryComponent}/edit', [PayrollController::class, 'editComponent'])->name('payroll.edit-component');
        Route::put('payroll-components/{salaryComponent}', [PayrollController::class, 'updateComponent'])->name('payroll.update-component');
        Route::delete('payroll-components/{salaryComponent}', [PayrollController::class, 'destroyComponent'])->name('payroll.destroy-component');

        // Employee Salary Structure
        Route::get('employees/{employee}/salary', [PayrollController::class, 'employeeSalary'])->name('payroll.employee-salary');
        Route::post('employees/{employee}/salary', [PayrollController::class, 'updateEmployeeSalary'])->name('payroll.update-employee-salary');

        // Shifts
        Route::get('shifts', [ShiftController::class, 'shifts'])->name('shifts.index');
        Route::get('shifts/create', [ShiftController::class, 'createShift'])->name('shifts.create');
        Route::post('shifts', [ShiftController::class, 'storeShift'])->name('shifts.store');
        Route::get('shifts/{shift}/edit', [ShiftController::class, 'editShift'])->name('shifts.edit');
        Route::put('shifts/{shift}', [ShiftController::class, 'updateShift'])->name('shifts.update');
        Route::delete('shifts/{shift}', [ShiftController::class, 'destroyShift'])->name('shifts.destroy');

        // Duty Roster
        Route::get('duty-roster', [ShiftController::class, 'roster'])->name('shifts.roster');
        Route::post('duty-roster', [ShiftController::class, 'storeRoster'])->name('shifts.store-roster');
        Route::post('duty-roster/auto-generate', [ShiftController::class, 'autoGenerate'])->name('shifts.auto-generate');

        // Shift Swap Requests
        Route::get('shift-swaps', [ShiftController::class, 'swapRequests'])->name('shifts.swap-requests');
        Route::post('shift-swaps/{shiftSwapRequest}/approve', [ShiftController::class, 'approveSwap'])->name('shifts.approve-swap');
        Route::post('shift-swaps/{shiftSwapRequest}/reject', [ShiftController::class, 'rejectSwap'])->name('shifts.reject-swap');

        // Department Staff Management
        Route::get('department-staff', [DepartmentStaffController::class, 'index'])->name('department-staff.index');
        Route::get('department-staff/{department}', [DepartmentStaffController::class, 'show'])->name('department-staff.show');
        Route::put('department-staff/{department}/head', [DepartmentStaffController::class, 'updateHead'])->name('department-staff.update-head');
        Route::post('department-staff/transfer', [DepartmentStaffController::class, 'transferEmployee'])->name('department-staff.transfer');

        // Document Management
        Route::get('documents', [DocumentManagementController::class, 'index'])->name('documents.index');
        Route::get('documents/compliance', [DocumentManagementController::class, 'compliance'])->name('documents.compliance');
        Route::post('documents/{document}/verify', [DocumentManagementController::class, 'verify'])->name('documents.verify');
        Route::post('documents/{document}/unverify', [DocumentManagementController::class, 'unverify'])->name('documents.unverify');
        Route::get('documents/requirements', [DocumentManagementController::class, 'requirements'])->name('documents.requirements');
        Route::get('documents/requirements/create', [DocumentManagementController::class, 'createRequirement'])->name('documents.create-requirement');
        Route::post('documents/requirements', [DocumentManagementController::class, 'storeRequirement'])->name('documents.store-requirement');
        Route::delete('documents/requirements/{documentRequirement}', [DocumentManagementController::class, 'destroyRequirement'])->name('documents.destroy-requirement');
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
    Route::get('/medicines/data', [MedicineController::class, 'data'])
        ->name('medicines.data')
        ->middleware('permission:view services|view pharmacy|manage pharmacy');
    Route::resource('medicine-categories', MedicineCategoryController::class)->middleware('permission:view services|view pharmacy|manage pharmacy');
    Route::resource('medicine-brands', MedicineBrandController::class)->middleware('permission:view services|view pharmacy|manage pharmacy');
    Route::resource('medicines', MedicineController::class)->middleware('permission:view services|view pharmacy|manage pharmacy');
    Route::post('visits/{visit}/prescription', [VisitController::class, 'createPrescription'])->name('visits.prescription')->middleware('permission:edit visits');
    Route::post('visits/{visit}/order-multiple-lab-tests', [VisitController::class, 'orderMultipleLabTests'])->name('visits.order-multiple-lab-tests')->middleware('permission:edit visits');
    Route::post('prescriptions/{prescription}/dispense', [PrescriptionController::class, 'dispense'])->name('prescriptions.dispense')->middleware('permission:edit visits');

    // Prescription Instructions Routes
    Route::resource('prescription-instructions', PrescriptionInstructionController::class)->middleware('permission:view services|view pharmacy|manage pharmacy');

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
    Route::get('/investigations/data', [InvestigationController::class, 'data'])
        ->name('investigations.data')
        ->middleware('permission:view investigations|create investigations|edit investigations|delete investigations');
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
    Route::get('investigation-orders/{investigationOrder}/report', [LabResultController::class, 'orderReport'])->name('investigation-orders.report')->middleware('permission:view lab results');
    Route::get('lab-results/create-batch', [LabResultController::class, 'createBatch'])->name('lab-results.create-batch')->middleware('permission:create lab results');
    Route::post('lab-results/store-batch', [LabResultController::class, 'storeBatch'])->name('lab-results.store-batch')->middleware('permission:create lab results');
    Route::get('lab-orders/{orderItem}/results/create', [LabResultController::class, 'create'])->name('lab-orders.results.create')->middleware('permission:create lab results');
    Route::post('lab-orders/{orderItem}/results', [LabResultController::class, 'store'])->name('lab-orders.results.store')->middleware('permission:create lab results');
    Route::post('lab-results/{labResult}/verify', [LabResultController::class, 'verify'])->name('lab-results.verify')->middleware('permission:edit lab results');
    Route::get('lab-results/{labResult}/report', [LabResultController::class, 'report'])->name('lab-results.report')->middleware('permission:view lab results');
    Route::get('lab-results/{labResult}/share-whatsapp', [LabResultController::class, 'shareWhatsApp'])->name('lab-results.share-whatsapp')->middleware('permission:view lab results');
    Route::get('investigation-orders/{investigationOrder}/share-whatsapp', [LabResultController::class, 'shareOrderWhatsApp'])->name('investigation-orders.share-whatsapp')->middleware('permission:view lab results');

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
    Route::get('/users/data', [UserController::class, 'data']);
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
        Route::get('investigations', [ReportController::class, 'labTests'])->name('investigations');
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

    // Operation Theatre Management
    Route::prefix('ot')->name('ot.')->middleware('permission:view surgeries|create surgeries|edit surgeries|delete surgeries')->group(function () {
        // Calendar
        Route::get('calendar', [OTController::class, 'calendar'])->name('calendar');
        Route::get('calendar/events', [OTController::class, 'calendarEvents'])->name('calendar.events');

        // Conflict detection API
        Route::get('conflicts', [OTController::class, 'checkConflicts'])->name('check-conflicts');

        // Theatres
        Route::get('theatres', [OTController::class, 'theatres'])->name('theatres');
        Route::get('theatres/create', [OTController::class, 'createTheatre'])->name('theatres.create');
        Route::post('theatres', [OTController::class, 'storeTheatre'])->name('theatres.store');
        Route::get('theatres/{theatre}/edit', [OTController::class, 'editTheatre'])->name('theatres.edit');
        Route::put('theatres/{theatre}', [OTController::class, 'updateTheatre'])->name('theatres.update');

        // Surgeries
        Route::get('surgeries', [OTController::class, 'index'])->name('surgeries.index');
        Route::get('surgeries/create', [OTController::class, 'create'])->name('surgeries.create');
        Route::post('surgeries', [OTController::class, 'store'])->name('surgeries.store');
        Route::get('surgeries/{surgery}', [OTController::class, 'show'])->name('surgeries.show');
        Route::get('surgeries/{surgery}/edit', [OTController::class, 'edit'])->name('surgeries.edit');
        Route::put('surgeries/{surgery}', [OTController::class, 'update'])->name('surgeries.update');
        Route::post('surgeries/{surgery}/start', [OTController::class, 'start'])->name('surgeries.start');
        Route::post('surgeries/{surgery}/complete', [OTController::class, 'complete'])->name('surgeries.complete');
        Route::post('surgeries/{surgery}/cancel', [OTController::class, 'cancel'])->name('surgeries.cancel');
        Route::post('surgeries/{surgery}/postpone', [OTController::class, 'postpone'])->name('surgeries.postpone');

        // Pre-Anaesthesia Checkup (PAC)
        Route::get('pac', [PacController::class, 'index'])->name('pac.index');
        Route::get('pac/create/{surgery}', [PacController::class, 'create'])->name('pac.create');
        Route::post('pac/{surgery}', [PacController::class, 'store'])->name('pac.store');
        Route::get('pac/{pac}', [PacController::class, 'show'])->name('pac.show');
        Route::post('pac/{pac}/clear', [PacController::class, 'clear'])->name('pac.clear');
        Route::post('pac/{pac}/decline', [PacController::class, 'decline'])->name('pac.decline');
        Route::post('pac/{pac}/further-eval', [PacController::class, 'requireFurtherEval'])->name('pac.further-eval');

        // Surgical Safety Checklist
        Route::get('checklist/{surgery}', [SurgicalChecklistController::class, 'show'])->name('checklist.show');
        Route::post('checklist/item/{item}/toggle', [SurgicalChecklistController::class, 'toggleItem'])->name('checklist.toggle-item');
        Route::post('checklist/{checklist}/complete-phase', [SurgicalChecklistController::class, 'completePhase'])->name('checklist.complete-phase');

        // OT Consumables & Inventory
        Route::get('consumables', [OtConsumableController::class, 'index'])->name('consumables.index');
        Route::get('consumables/create', [OtConsumableController::class, 'create'])->name('consumables.create');
        Route::post('consumables', [OtConsumableController::class, 'store'])->name('consumables.store');
        Route::get('consumables/{consumable}/edit', [OtConsumableController::class, 'edit'])->name('consumables.edit');
        Route::put('consumables/{consumable}', [OtConsumableController::class, 'update'])->name('consumables.update');
        Route::get('consumables/{consumable}/stock-in', [OtConsumableController::class, 'stockIn'])->name('consumables.stock-in');
        Route::post('consumables/{consumable}/stock-in', [OtConsumableController::class, 'processStockIn'])->name('consumables.process-stock-in');
        Route::get('consumables/reorder-alerts', [OtConsumableController::class, 'reorderAlerts'])->name('consumables.reorder-alerts');
        Route::get('surgeries/{surgery}/usage', [OtConsumableController::class, 'usageForm'])->name('consumables.usage');
        Route::post('surgeries/{surgery}/usage', [OtConsumableController::class, 'recordUsage'])->name('consumables.record-usage');

        // Sterilization & Audit Logs
        Route::get('sterilization', [SterilizationController::class, 'index'])->name('sterilization.index');
        Route::get('sterilization/create', [SterilizationController::class, 'create'])->name('sterilization.create');
        Route::post('sterilization', [SterilizationController::class, 'store'])->name('sterilization.store');
        Route::get('sterilization/{sterilization}', [SterilizationController::class, 'show'])->name('sterilization.show');
        Route::post('sterilization/{sterilization}/start', [SterilizationController::class, 'start'])->name('sterilization.start');
        Route::post('sterilization/{sterilization}/complete', [SterilizationController::class, 'complete'])->name('sterilization.complete');
        Route::post('sterilization/{sterilization}/verify', [SterilizationController::class, 'verify'])->name('sterilization.verify');
        Route::post('sterilization/{sterilization}/fail', [SterilizationController::class, 'fail'])->name('sterilization.fail');

        // Intra-operative & Post-operative Monitoring
        Route::get('surgeries/{surgery}/anaesthesia', [OperativeMonitoringController::class, 'anaesthesiaForm'])->name('monitoring.anaesthesia');
        Route::post('surgeries/{surgery}/anaesthesia', [OperativeMonitoringController::class, 'storeAnaesthesia'])->name('monitoring.store-anaesthesia');
        Route::get('surgeries/{surgery}/vitals', [OperativeMonitoringController::class, 'vitalsForm'])->name('monitoring.vitals');
        Route::post('surgeries/{surgery}/vitals', [OperativeMonitoringController::class, 'storeVitals'])->name('monitoring.store-vitals');
        Route::get('surgeries/{surgery}/vitals-data', [OperativeMonitoringController::class, 'vitalsData'])->name('monitoring.vitals-data');
        Route::get('surgeries/{surgery}/post-op', [OperativeMonitoringController::class, 'postOpForm'])->name('monitoring.post-op');
        Route::post('surgeries/{surgery}/post-op', [OperativeMonitoringController::class, 'storePostOp'])->name('monitoring.store-post-op');
    });

    // Backup & Restore Routes
    Route::prefix('backup')->name('backup.')->middleware('role:Super Admin|Hospital Administrator')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->name('index');
        Route::post('/create', [BackupController::class, 'create'])->name('create');
        Route::get('/download/{filename}', [BackupController::class, 'download'])->name('download');
        Route::delete('/delete/{filename}', [BackupController::class, 'destroy'])->name('destroy');
        Route::post('/restore/{filename}', [BackupController::class, 'restore'])->name('restore');
    });

    // Patient Search API
    Route::get('api/patients/search', [PatientController::class, 'search'])->name('api.patients.search');
});

require __DIR__.'/auth.php';

// Legacy signed URL — redirects into the verify-gate flow (numeric id only)
Route::get('lab-report/{labResult}', [LabResultController::class, 'publicReport'])
    ->name('lab-results.public-report')
    ->middleware('signed')
    ->whereNumber('labResult');

// Public lab report access — verify with patient number + mobile (no auth)
Route::get('lab-report/{shareToken}', [PublicLabReportController::class, 'show'])
    ->name('lab-report.show')
    ->where('shareToken', '[A-Za-z0-9]{20,64}');
Route::post('lab-report/{shareToken}/verify', [PublicLabReportController::class, 'verify'])
    ->name('lab-report.verify')
    ->middleware('throttle:10,1')
    ->where('shareToken', '[A-Za-z0-9]{20,64}');
Route::get('lab-report/{shareToken}/view', [PublicLabReportController::class, 'view'])
    ->name('lab-report.view')
    ->where('shareToken', '[A-Za-z0-9]{20,64}');

}); // end tenant middleware group
