<?php

namespace App\Http\Controllers;

use App\Http\Requests\CollectSampleRequest;
use App\Http\Requests\StoreLabOrderRequest;
use App\Http\Requests\UpdateLabOrderRequest;
use App\Models\LabOrder;
use Illuminate\Http\Request;

class LabOrderController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('lab.orders.index');
    }

    public function create()
    {
        return redirect()->route('lab.orders.create');
    }

    public function store(StoreLabOrderRequest $request)
    {
        return app(InvestigationOrderController::class)->store($request);
    }

    public function show(LabOrder $labOrder)
    {
        return app(InvestigationOrderController::class)->show($labOrder);
    }

    public function edit(LabOrder $labOrder)
    {
        return app(InvestigationOrderController::class)->edit($labOrder);
    }

    public function update(UpdateLabOrderRequest $request, LabOrder $labOrder)
    {
        return app(InvestigationOrderController::class)->update($request, $labOrder);
    }

    public function collectSample(CollectSampleRequest $request, LabOrder $labOrder)
    {
        return app(InvestigationOrderController::class)->collectSample($request, $labOrder);
    }

    public function receiveSample(Request $request, LabOrder $labOrder)
    {
        return app(InvestigationOrderController::class)->receiveSample($request, $labOrder);
    }
}
