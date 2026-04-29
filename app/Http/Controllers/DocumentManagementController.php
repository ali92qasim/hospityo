<?php

namespace App\Http\Controllers;

use App\Models\EmployeeDocument;
use App\Models\DocumentRequirement;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentManagementController extends Controller
{
    /**
     * Document dashboard — expiry alerts, compliance overview.
     */
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'all');

        $query = EmployeeDocument::with(['employee.department', 'employee.designation']);

        if ($filter === 'expired') {
            $query->expired();
        } elseif ($filter === 'expiring') {
            $query->expiringSoon(30);
        } elseif ($filter === 'unverified') {
            $query->unverified();
        } elseif ($filter === 'mandatory') {
            $query->mandatory();
        }

        $documents = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total' => EmployeeDocument::count(),
            'expired' => EmployeeDocument::expired()->count(),
            'expiring_30' => EmployeeDocument::expiringSoon(30)->count(),
            'unverified' => EmployeeDocument::unverified()->count(),
            'mandatory_missing' => $this->countMissingMandatory(),
        ];

        return view('admin.hr.documents.index', compact('documents', 'stats', 'filter'));
    }

    /**
     * Compliance report — per employee, which required documents are present/missing.
     */
    public function compliance(Request $request)
    {
        $departmentId = $request->input('department_id');

        $query = Employee::active()->with(['department', 'designation', 'documents']);
        if ($departmentId) $query->where('department_id', $departmentId);
        $employees = $query->orderBy('first_name')->get();

        $requirements = DocumentRequirement::active()->get();
        $departments = \App\Models\Department::orderBy('name')->get();

        // Build compliance matrix
        $compliance = $employees->map(function ($emp) use ($requirements) {
            $applicable = DocumentRequirement::getForEmployee($emp);
            $existingTypes = $emp->documents->pluck('document_type')->toArray();

            $missing = $applicable->filter(fn($req) => $req->is_mandatory && !in_array($req->document_type, $existingTypes));
            $expired = $emp->documents->filter(fn($doc) => $doc->isExpired());
            $expiring = $emp->documents->filter(fn($doc) => $doc->isExpiringSoon(30));

            return [
                'employee' => $emp,
                'required' => $applicable->count(),
                'uploaded' => $emp->documents->count(),
                'missing' => $missing->count(),
                'missing_docs' => $missing->pluck('label'),
                'expired' => $expired->count(),
                'expiring' => $expiring->count(),
                'compliant' => $missing->isEmpty() && $expired->isEmpty(),
            ];
        });

        $overallStats = [
            'total_employees' => $employees->count(),
            'fully_compliant' => $compliance->where('compliant', true)->count(),
            'with_missing' => $compliance->where('missing', '>', 0)->count(),
            'with_expired' => $compliance->where('expired', '>', 0)->count(),
        ];

        return view('admin.hr.documents.compliance', compact('compliance', 'requirements', 'departments', 'overallStats', 'departmentId'));
    }

    /**
     * Verify a document.
     */
    public function verify(EmployeeDocument $document)
    {
        $document->update([
            'is_verified' => true,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        return back()->with('success', "Document '{$document->title}' verified.");
    }

    /**
     * Unverify a document.
     */
    public function unverify(EmployeeDocument $document)
    {
        $document->update([
            'is_verified' => false,
            'verified_by' => null,
            'verified_at' => null,
        ]);

        return back()->with('success', "Document verification removed.");
    }

    // ── Document Requirements ──

    public function requirements()
    {
        $requirements = DocumentRequirement::orderBy('applicable_to')->orderBy('label')->get();
        return view('admin.hr.documents.requirements', compact('requirements'));
    }

    public function createRequirement()
    {
        $designations = \App\Models\Designation::active()->orderBy('category')->orderBy('name')->get();
        return view('admin.hr.documents.create-requirement', compact('designations'));
    }

    public function storeRequirement(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string|max:50',
            'label' => 'required|string|max:255',
            'applicable_to' => 'required|string|max:100',
            'is_mandatory' => 'nullable|boolean',
            'has_expiry' => 'nullable|boolean',
            'expiry_reminder_days' => 'nullable|integer|min:1|max:365',
            'description' => 'nullable|string|max:500',
        ]);

        DocumentRequirement::create([
            ...$request->only('document_type', 'label', 'applicable_to', 'expiry_reminder_days', 'description'),
            'is_mandatory' => $request->boolean('is_mandatory', true),
            'has_expiry' => $request->boolean('has_expiry'),
        ]);

        return redirect()->route('hr.documents.requirements')->with('success', 'Document requirement created.');
    }

    public function destroyRequirement(DocumentRequirement $documentRequirement)
    {
        $documentRequirement->delete();
        return back()->with('success', 'Document requirement deleted.');
    }

    /**
     * Count employees missing mandatory documents.
     */
    private function countMissingMandatory(): int
    {
        $count = 0;
        $requirements = DocumentRequirement::active()->where('is_mandatory', true)->get();
        $employees = Employee::active()->with('documents')->get();

        foreach ($employees as $emp) {
            $applicable = DocumentRequirement::getForEmployee($emp)->where('is_mandatory', true);
            $existingTypes = $emp->documents->pluck('document_type')->toArray();
            $missing = $applicable->filter(fn($req) => !in_array($req->document_type, $existingTypes));
            if ($missing->isNotEmpty()) $count++;
        }

        return $count;
    }
}
