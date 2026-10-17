<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RedirectsAfterWrite;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;

class DepartmentController extends Controller
{
    use RedirectsAfterWrite;
    public function index()
    {
        $departments = Department::latest()->paginate(10);
        return view('admin.departments.index', compact('departments'));
    }

    public function create()
    {
        return view('admin.departments.create');
    }

    public function store(StoreDepartmentRequest $request)
    {
        Department::create($request->validated());

        return $this->redirectAfterWrite(
            'departments.index',
            [],
            ['view departments'],
            'Department created successfully.',
            'departments.create',
        );
    }

    public function show(Department $department)
    {
        return view('admin.departments.show', compact('department'));
    }

    public function edit(Department $department)
    {
        return view('admin.departments.edit', compact('department'));
    }

    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $department->update($request->validated());

        return $this->redirectAfterWrite(
            'departments.index',
            [],
            ['view departments'],
            'Department updated successfully.',
            'departments.edit',
            ['department' => $department],
        );
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return $this->redirectAfterWrite(
            'departments.index',
            [],
            ['view departments'],
            'Department deleted successfully.',
        );
    }
}
