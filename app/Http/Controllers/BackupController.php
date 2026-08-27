<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function __construct(private BackupService $backups) {}

    public function index()
    {
        $backups = $this->backups->list();

        return view('admin.backup.index', compact('backups'));
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'type' => 'nullable|in:full,database,files',
        ]);

        try {
            $this->backups->create($validated['type'] ?? 'full');

            return redirect()->route('backup.index')
                ->with('success', __('messages.backup_created_successfully'));
        } catch (\Exception $e) {
            return redirect()->route('backup.index')
                ->with('error', 'Backup failed: '.$e->getMessage());
        }
    }

    public function download($filename)
    {
        try {
            $filePath = $this->backups->path($filename);
        } catch (\Exception $e) {
            return redirect()->route('backup.index')
                ->with('error', $e->getMessage());
        }

        if (! is_file($filePath)) {
            return redirect()->route('backup.index')
                ->with('error', 'Backup file not found');
        }

        return response()->download($filePath);
    }

    public function destroy($filename)
    {
        try {
            $this->backups->delete($filename);

            return redirect()->route('backup.index')
                ->with('success', 'Backup deleted successfully');
        } catch (\Exception $e) {
            return redirect()->route('backup.index')
                ->with('error', 'Failed to delete backup: '.$e->getMessage());
        }
    }

    public function restore(Request $request, $filename)
    {
        try {
            $this->backups->restore($filename);

            return redirect()->route('backup.index')
                ->with('success', 'Backup restored successfully');
        } catch (\Exception $e) {
            return redirect()->route('backup.index')
                ->with('error', 'Restore failed: '.$e->getMessage());
        }
    }
}
