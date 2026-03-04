<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use ZipArchive;
use Carbon\Carbon;

class BackupController extends Controller
{
    /**
     * Display backup management page
     */
    public function index()
    {
        $backups = $this->getBackupList();
        
        return view('admin.backup.index', compact('backups'));
    }
    
    /**
     * Create a new backup
     */
    public function create(Request $request)
    {
        try {
            $backupType = $request->input('type', 'full'); // full, database, files
            
            $timestamp = Carbon::now()->format('Y-m-d_His');
            $backupName = "backup_{$backupType}_{$timestamp}";
            
            // Create backup directory if it doesn't exist
            $backupPath = storage_path('app/backups');
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }
            
            $zipFile = $backupPath . '/' . $backupName . '.zip';
            $zip = new ZipArchive();
            
            if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new \Exception('Could not create backup file');
            }
            
            // Backup database
            if ($backupType === 'full' || $backupType === 'database') {
                $this->backupDatabase($zip, $timestamp);
            }
            
            // Backup files
            if ($backupType === 'full' || $backupType === 'files') {
                $this->backupFiles($zip);
            }
            
            // Add metadata
            $metadata = [
                'type' => $backupType,
                'created_at' => Carbon::now()->toDateTimeString(),
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'database' => config('database.default'),
            ];
            $zip->addFromString('backup_info.json', json_encode($metadata, JSON_PRETTY_PRINT));
            
            $zip->close();
            
            return redirect()->route('backup.index')
                ->with('success', __('messages.backup_created_successfully'));
                
        } catch (\Exception $e) {
            return redirect()->route('backup.index')
                ->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Download a backup file
     */
    public function download($filename)
    {
        $filePath = storage_path('app/backups/' . $filename);
        
        if (!file_exists($filePath)) {
            return redirect()->route('backup.index')
                ->with('error', 'Backup file not found');
        }
        
        return response()->download($filePath);
    }
    
    /**
     * Delete a backup file
     */
    public function destroy($filename)
    {
        try {
            $filePath = storage_path('app/backups/' . $filename);
            
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            return redirect()->route('backup.index')
                ->with('success', 'Backup deleted successfully');
                
        } catch (\Exception $e) {
            return redirect()->route('backup.index')
                ->with('error', 'Failed to delete backup: ' . $e->getMessage());
        }
    }
    
    /**
     * Restore from backup
     */
    public function restore(Request $request, $filename)
    {
        try {
            $filePath = storage_path('app/backups/' . $filename);
            
            if (!file_exists($filePath)) {
                throw new \Exception('Backup file not found');
            }
            
            $zip = new ZipArchive();
            if ($zip->open($filePath) !== TRUE) {
                throw new \Exception('Could not open backup file');
            }
            
            // Extract to temporary directory
            $tempPath = storage_path('app/temp_restore_' . time());
            $zip->extractTo($tempPath);
            $zip->close();
            
            // Read metadata
            $metadataFile = $tempPath . '/backup_info.json';
            if (file_exists($metadataFile)) {
                $metadata = json_decode(file_get_contents($metadataFile), true);
            }
            
            // Restore database
            if (file_exists($tempPath . '/database.sql')) {
                $this->restoreDatabase($tempPath . '/database.sql');
            }
            
            // Restore files
            if (is_dir($tempPath . '/storage')) {
                $this->restoreFiles($tempPath . '/storage');
            }
            
            // Clean up temp directory
            $this->deleteDirectory($tempPath);
            
            // Clear caches
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            
            return redirect()->route('backup.index')
                ->with('success', 'Backup restored successfully');
                
        } catch (\Exception $e) {
            return redirect()->route('backup.index')
                ->with('error', 'Restore failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Backup database to SQL file
     */
    private function backupDatabase($zip, $timestamp)
    {
        $database = config('database.default');
        $connection = config("database.connections.{$database}");
        
        if ($database === 'sqlite') {
            // For SQLite, just copy the database file
            $dbPath = database_path('database.sqlite');
            if (file_exists($dbPath)) {
                $zip->addFile($dbPath, 'database.sqlite');
            }
        } else {
            // For MySQL/PostgreSQL, use mysqldump/pg_dump
            $sqlFile = storage_path("app/temp_db_{$timestamp}.sql");
            
            if ($database === 'mysql') {
                $command = sprintf(
                    'mysqldump -h %s -u %s -p%s %s > %s',
                    $connection['host'],
                    $connection['username'],
                    $connection['password'],
                    $connection['database'],
                    $sqlFile
                );
            } elseif ($database === 'pgsql') {
                $command = sprintf(
                    'PGPASSWORD=%s pg_dump -h %s -U %s %s > %s',
                    $connection['password'],
                    $connection['host'],
                    $connection['username'],
                    $connection['database'],
                    $sqlFile
                );
            }
            
            if (isset($command)) {
                exec($command);
                if (file_exists($sqlFile)) {
                    $zip->addFile($sqlFile, 'database.sql');
                    unlink($sqlFile);
                }
            }
        }
    }
    
    /**
     * Backup important files
     */
    private function backupFiles($zip)
    {
        // Backup storage/app/public (uploaded files)
        $this->addDirectoryToZip($zip, storage_path('app/public'), 'storage/public');
        
        // Backup .env file
        if (file_exists(base_path('.env'))) {
            $zip->addFile(base_path('.env'), 'env_backup.txt');
        }
    }
    
    /**
     * Restore database from SQL file
     */
    private function restoreDatabase($sqlFile)
    {
        $database = config('database.default');
        $connection = config("database.connections.{$database}");
        
        if ($database === 'sqlite') {
            // For SQLite, replace the database file
            $dbPath = database_path('database.sqlite');
            copy($sqlFile, $dbPath);
        } else {
            // For MySQL/PostgreSQL
            $sql = file_get_contents($sqlFile);
            
            if ($database === 'mysql') {
                DB::unprepared($sql);
            } elseif ($database === 'pgsql') {
                DB::unprepared($sql);
            }
        }
    }
    
    /**
     * Restore files from backup
     */
    private function restoreFiles($sourcePath)
    {
        // Restore storage/app/public
        if (is_dir($sourcePath . '/public')) {
            $this->copyDirectory($sourcePath . '/public', storage_path('app/public'));
        }
    }
    
    /**
     * Get list of available backups
     */
    private function getBackupList()
    {
        $backupPath = storage_path('app/backups');
        
        if (!file_exists($backupPath)) {
            return [];
        }
        
        $files = glob($backupPath . '/*.zip');
        $backups = [];
        
        foreach ($files as $file) {
            $backups[] = [
                'name' => basename($file),
                'size' => $this->formatBytes(filesize($file)),
                'date' => Carbon::createFromTimestamp(filemtime($file)),
                'path' => $file,
            ];
        }
        
        // Sort by date descending
        usort($backups, function($a, $b) {
            return $b['date']->timestamp - $a['date']->timestamp;
        });
        
        return $backups;
    }
    
    /**
     * Add directory to zip recursively
     */
    private function addDirectoryToZip($zip, $sourcePath, $zipPath)
    {
        if (!is_dir($sourcePath)) {
            return;
        }
        
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $zipPath . '/' . substr($filePath, strlen($sourcePath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
    
    /**
     * Copy directory recursively
     */
    private function copyDirectory($source, $destination)
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($files as $file) {
            $targetPath = $destination . '/' . substr($file->getRealPath(), strlen($source) + 1);
            
            if ($file->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                copy($file->getRealPath(), $targetPath);
            }
        }
    }
    
    /**
     * Delete directory recursively
     */
    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        rmdir($dir);
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
