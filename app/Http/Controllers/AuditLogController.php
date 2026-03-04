<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->latest();

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->event) {
            $query->where('event', $request->event);
        }

        if ($request->model) {
            $query->where('auditable_type', 'like', '%' . $request->model . '%');
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(20);
        $users = \App\Models\User::select('id', 'name')->get();

        return view('admin.audit-logs.index', compact('logs', 'users'));
    }

    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user', 'auditable');
        return view('admin.audit-logs.show', compact('auditLog'));
    }
}