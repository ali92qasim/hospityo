<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PrescriptionPrintTemplateController extends Controller
{
    public function index(): View
    {
        return view('settings.prescription-print-placeholder');
    }
}
