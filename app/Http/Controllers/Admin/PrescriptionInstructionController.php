<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePrescriptionInstructionRequest;
use App\Http\Requests\UpdatePrescriptionInstructionRequest;
use App\Models\PrescriptionInstruction;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PrescriptionInstructionController extends Controller
{
    public function index(): View
    {
        $instructions = PrescriptionInstruction::latest()->paginate(15);
        
        return view('admin.prescription-instructions.index', compact('instructions'));
    }

    public function create(): View
    {
        return view('admin.prescription-instructions.create');
    }

    public function store(StorePrescriptionInstructionRequest $request): RedirectResponse
    {
        PrescriptionInstruction::create($request->validated());

        return redirect()
            ->route('prescription-instructions.index')
            ->with('success', 'Prescription instruction created successfully.');
    }

    public function edit(PrescriptionInstruction $prescriptionInstruction): View
    {
        return view('admin.prescription-instructions.edit', compact('prescriptionInstruction'));
    }

    public function update(
        UpdatePrescriptionInstructionRequest $request,
        PrescriptionInstruction $prescriptionInstruction
    ): RedirectResponse {
        $prescriptionInstruction->update($request->validated());

        return redirect()
            ->route('prescription-instructions.index')
            ->with('success', 'Prescription instruction updated successfully.');
    }

    public function destroy(PrescriptionInstruction $prescriptionInstruction): RedirectResponse
    {
        $prescriptionInstruction->delete();

        return redirect()
            ->route('prescription-instructions.index')
            ->with('success', 'Prescription instruction deleted successfully.');
    }
}
