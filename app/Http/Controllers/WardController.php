<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RedirectsAfterWrite;
use App\Http\Requests\StoreWardRequest;
use App\Http\Requests\UpdateWardRequest;
use App\Models\Ward;
use App\Models\Department;

class WardController extends Controller
{
    use RedirectsAfterWrite;

    public function index()
    {
        $wards = Ward::with('department')->latest()->paginate(10);
        return view('admin.wards.index', compact('wards'));
    }

    public function create()
    {
        $departments = Department::where('status', 'active')->get();
        return view('admin.wards.create', compact('departments'));
    }

    public function store(StoreWardRequest $request)
    {
        Ward::create($request->validated());

        return $this->redirectAfterWrite(
            'wards.index',
            [],
            ['view wards'],
            'Ward created successfully.',
            'wards.create',
        );
    }

    public function edit(Ward $ward)
    {
        $departments = Department::where('status', 'active')->get();
        return view('admin.wards.edit', compact('ward', 'departments'));
    }

    public function update(UpdateWardRequest $request, Ward $ward)
    {
        $ward->update($request->validated());

        return $this->redirectAfterWrite(
            'wards.index',
            [],
            ['view wards'],
            'Ward updated successfully.',
            'wards.edit',
            ['ward' => $ward],
        );
    }

    public function destroy(Ward $ward)
    {
        $ward->delete();

        return $this->redirectAfterWrite(
            'wards.index',
            [],
            ['view wards'],
            'Ward deleted successfully.',
        );
    }
}
