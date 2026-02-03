<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Department;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::with('department')->paginate(10);
        return view('admin.services.index', compact('services'));
    }

    public function create()
    {
        $departments = Department::all();
        return view('admin.services.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:services,code',
            'category' => 'required|in:consultation,procedure,lab_test,imaging,medication,other',
            'price' => 'required|numeric|min:0'
        ]);

        Service::create($request->all());

        return redirect()->route('services.index')->with('success', 'Service created successfully');
    }

    public function edit(Service $service)
    {
        $departments = Department::all();
        return view('admin.services.edit', compact('service', 'departments'));
    }

    public function update(Request $request, Service $service)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:services,code,' . $service->id,
            'category' => 'required|in:consultation,procedure,lab_test,imaging,medication,other',
            'price' => 'required|numeric|min:0'
        ]);

        $service->update($request->all());

        return redirect()->route('services.index')->with('success', 'Service updated successfully');
    }

    public function destroy(Service $service)
    {
        $service->delete();
        return redirect()->route('services.index')->with('success', 'Service deleted successfully');
    }
}