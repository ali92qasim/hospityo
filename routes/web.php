<?php

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
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\LabTestController;
use App\Http\Controllers\LabOrderController;
use App\Http\Controllers\LabResultController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\UnitController;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        $user = auth()->user();
        
        // Check if user is a doctor
        if ($user->hasRole('Doctor')) {
            $doctor = \App\Models\Doctor::where('user_id', $user->id)->first();
            if ($doctor) {
                $assignedPatients = $doctor->assignedPatients()->limit(5)->get();
                $totalAssigned = $doctor->assignedPatients()->count();
                return view('admin.dashboard', compact('assignedPatients', 'totalAssigned'));
            }
        }
        
        return view('admin.dashboard');
    });
    
    Route::get('/dashboard', function () {
        $user = auth()->user();
        
        // Check if user is a doctor
        if ($user->hasRole('Doctor')) {
            $doctor = \App\Models\Doctor::where('user_id', $user->id)->first();
            if ($doctor) {
                $assignedPatients = $doctor->assignedPatients()->limit(5)->get();
                $totalAssigned = $doctor->assignedPatients()->count();
                return view('admin.dashboard', compact('assignedPatients', 'totalAssigned'));
            }
        }
        
        return view('admin.dashboard');
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
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('patients', PatientController::class)->middleware('permission:view patients|create patients|edit patients|delete patients');
    Route::get('patients/{patient}/history', [PatientController::class, 'history'])->name('patients.history')->middleware('permission:view patients');
    Route::resource('doctors', DoctorController::class)->middleware('permission:view doctors|create doctors|edit doctors|delete doctors');
    Route::resource('departments', DepartmentController::class)->middleware('permission:view departments|create departments|edit departments|delete departments');
    Route::resource('visits', VisitController::class)->middleware('permission:view visits|create visits|edit visits|delete visits');
    Route::get('visits/{visit}/workflow', [VisitController::class, 'workflow'])->name('visits.workflow')->middleware('permission:view visits');
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
    Route::resource('services', ServiceController::class)->middleware('permission:view services|create services|edit services|delete services');
    
    // IPD Management Routes
    Route::resource('wards', WardController::class)->middleware('permission:view departments|create departments|edit departments|delete departments');
    Route::resource('beds', BedController::class)->middleware('permission:view departments|create departments|edit departments|delete departments');
    
    // Pharmacy Routes
    Route::resource('medicines', MedicineController::class)->middleware('permission:view services|create services|edit services|delete services');
    Route::post('visits/{visit}/prescription', [VisitController::class, 'createPrescription'])->name('visits.prescription')->middleware('permission:edit visits');
    Route::post('visits/{visit}/order-lab-test', [VisitController::class, 'orderLabTest'])->name('visits.order-lab-test')->middleware('permission:edit visits');
    Route::post('prescriptions/{prescription}/dispense', [PrescriptionController::class, 'dispense'])->name('prescriptions.dispense')->middleware('permission:edit visits');
    
    // Unit Routes
    Route::resource('units', UnitController::class)->middleware('permission:view services|create services|edit services|delete services');
    
    // Inventory Routes
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index')->middleware('permission:view services');
    Route::get('inventory/stock-in', [InventoryController::class, 'stockIn'])->name('inventory.stock-in')->middleware('permission:create services');
    Route::post('inventory/stock-in', [InventoryController::class, 'processStockIn'])->name('inventory.process-stock-in')->middleware('permission:create services');
    Route::get('inventory/stock-out', [InventoryController::class, 'stockOut'])->name('inventory.stock-out')->middleware('permission:edit services');
    Route::post('inventory/stock-out', [InventoryController::class, 'processStockOut'])->name('inventory.process-stock-out')->middleware('permission:edit services');
    Route::get('inventory/low-stock', [InventoryController::class, 'lowStock'])->name('inventory.low-stock')->middleware('permission:view services');
    Route::get('inventory/expiring', [InventoryController::class, 'expiring'])->name('inventory.expiring')->middleware('permission:view services');
    
    // Supplier Routes
    Route::resource('suppliers', SupplierController::class)->middleware('permission:view services|create services|edit services|delete services');
    
    // Purchase Routes
    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show'])->middleware('permission:view services|create services');
    Route::post('purchases/{purchase}/approve', [PurchaseController::class, 'approve'])->name('purchases.approve')->middleware('permission:edit services');
    Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive')->middleware('permission:edit services');
    Route::post('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel')->middleware('permission:edit services');
    
    // Laboratory Routes
    Route::resource('lab-tests', LabTestController::class)->middleware('permission:view services|create services|edit services|delete services');
    Route::resource('lab-orders', LabOrderController::class)->middleware('permission:view visits|create visits|edit visits|delete visits');
    Route::post('lab-orders/{labOrder}/collect-sample', [LabOrderController::class, 'collectSample'])->name('lab-orders.collect-sample')->middleware('permission:edit visits');
    Route::post('lab-orders/{labOrder}/receive-sample', [LabOrderController::class, 'receiveSample'])->name('lab-orders.receive-sample')->middleware('permission:edit visits');
    Route::resource('lab-results', LabResultController::class)->middleware('permission:view visits|create visits|edit visits|delete visits');
    Route::get('lab-orders/{labOrder}/results/create', [LabResultController::class, 'create'])->name('lab-orders.results.create')->middleware('permission:edit visits');
    Route::post('lab-orders/{labOrder}/results', [LabResultController::class, 'store'])->name('lab-orders.results.store')->middleware('permission:edit visits');
    Route::post('lab-results/{labResult}/verify', [LabResultController::class, 'verify'])->name('lab-results.verify')->middleware('permission:edit visits');
    Route::get('lab-results/{labResult}/report', [LabResultController::class, 'report'])->name('lab-results.report')->middleware('permission:view visits');
    

    
    // RBAC Routes
    Route::resource('users', UserController::class)->middleware('role:Super Admin|Hospital Administrator');
    Route::resource('roles', RoleController::class)->middleware('permission:view roles|create roles|edit roles|delete roles');
    Route::resource('permissions', PermissionController::class)->middleware('permission:view permissions|create permissions|edit permissions|delete permissions');
    
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
