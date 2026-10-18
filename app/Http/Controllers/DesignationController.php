<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RedirectsAfterWrite;
use App\Models\Designation;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    use RedirectsAfterWrite;

    public function index()
    {
        $designations = Designation::withCount('employees')->orderBy('category')->orderBy('name')->paginate(20);
        return view('admin.hr.designations.index', compact('designations'));
    }

    public function create()
    {
        return view('admin.hr.designations.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:medical,nursing,admin,technical,support',
            'description' => 'nullable|string|max:500',
        ]);

        Designation::create($request->only('name', 'category', 'description'));

        return $this->redirectAfterWrite(
            'hr.designations.index',
            [],
            ['view designations', 'view hr'],
            'Designation created.',
            'hr.designations.create',
        );
    }

    public function edit(Designation $designation)
    {
        return view('admin.hr.designations.edit', compact('designation'));
    }

    public function update(Request $request, Designation $designation)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|in:medical,nursing,admin,technical,support',
            'description' => 'nullable|string|max:500',
        ]);

        $designation->update($request->only('name', 'category', 'description'));

        return $this->redirectAfterWrite(
            'hr.designations.index',
            [],
            ['view designations', 'view hr'],
            'Designation updated.',
            'hr.designations.edit',
            ['designation' => $designation],
        );
    }

    public function destroy(Designation $designation)
    {
        if ($designation->employees()->count() > 0) {
            return back()->with('error', 'Cannot delete — designation has employees assigned.');
        }
        $designation->delete();

        return $this->redirectAfterWrite(
            'hr.designations.index',
            [],
            ['view designations', 'view hr'],
            'Designation deleted.',
        );
    }
}
