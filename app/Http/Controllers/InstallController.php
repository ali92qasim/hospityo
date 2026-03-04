<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\User;
use Spatie\Permission\Models\Role;

class InstallController extends Controller
{
    public function index()
    {
        if ($this->isInstalled()) {
            return redirect('/')->with('error', 'Application is already installed.');
        }
        
        return view('install.welcome');
    }
    
    public function requirements()
    {
        $requirements = [
            'PHP >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'PDO Extension' => extension_loaded('pdo'),
            'SQLite Extension' => extension_loaded('pdo_sqlite'),
            'OpenSSL Extension' => extension_loaded('openssl'),
            'Mbstring Extension' => extension_loaded('mbstring'),
            'Tokenizer Extension' => extension_loaded('tokenizer'),
            'JSON Extension' => extension_loaded('json'),
            'Fileinfo Extension' => extension_loaded('fileinfo'),
        ];
        
        $permissions = [
            'storage/app' => is_writable(storage_path('app')),
            'storage/framework' => is_writable(storage_path('framework')),
            'storage/logs' => is_writable(storage_path('logs')),
            'bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
        ];
        
        return view('install.requirements', compact('requirements', 'permissions'));
    }
    
    public function database()
    {
        return view('install.database');
    }
    
    public function setupDatabase(Request $request)
    {
        $validated = $request->validate([
            'db_connection' => 'required|in:sqlite,mysql',
            'db_host' => 'required_if:db_connection,mysql',
            'db_port' => 'required_if:db_connection,mysql',
            'db_database' => 'required',
            'db_username' => 'required_if:db_connection,mysql',
            'db_password' => 'nullable',
        ]);
        
        try {
            $this->updateEnv($validated);
            
            Artisan::call('config:clear');
            Artisan::call('migrate:fresh', ['--force' => true]);
            
            return redirect()->route('install.admin');
        } catch (\Exception $e) {
            return back()->with('error', 'Database setup failed: ' . $e->getMessage());
        }
    }
    
    public function admin()
    {
        return view('install.admin');
    }
    
    public function setupAdmin(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);
        
        try {
            DB::transaction(function () use ($validated) {
                Artisan::call('db:seed', ['--class' => 'RolePermissionSeeder', '--force' => true]);
                
                $admin = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => bcrypt($validated['password']),
                    'email_verified_at' => now(),
                ]);
                
                $superAdminRole = Role::where('name', 'Super Admin')->first();
                $admin->assignRole($superAdminRole);
            });
            
            return redirect()->route('install.seed');
        } catch (\Exception $e) {
            return back()->with('error', 'Admin setup failed: ' . $e->getMessage());
        }
    }
    
    public function seed()
    {
        return view('install.seed');
    }
    
    public function runSeed(Request $request)
    {
        try {
            if ($request->seed_sample_data) {
                Artisan::call('db:seed', ['--class' => 'ServiceSeeder', '--force' => true]);
                Artisan::call('db:seed', ['--class' => 'UnitSeeder', '--force' => true]);
                Artisan::call('db:seed', ['--class' => 'InvestigationSeeder', '--force' => true]);
            }
            
            $this->markAsInstalled();
            
            return redirect()->route('install.complete');
        } catch (\Exception $e) {
            return back()->with('error', 'Seeding failed: ' . $e->getMessage());
        }
    }
    
    public function complete()
    {
        return view('install.complete');
    }
    
    private function isInstalled()
    {
        return File::exists(storage_path('installed'));
    }
    
    private function markAsInstalled()
    {
        File::put(storage_path('installed'), now());
    }
    
    private function updateEnv($data)
    {
        $envPath = base_path('.env');
        $envContent = File::get($envPath);
        
        $replacements = [
            'DB_CONNECTION' => $data['db_connection'],
            'DB_DATABASE' => $data['db_database'],
        ];
        
        if ($data['db_connection'] === 'mysql') {
            $replacements['DB_HOST'] = $data['db_host'];
            $replacements['DB_PORT'] = $data['db_port'];
            $replacements['DB_USERNAME'] = $data['db_username'];
            $replacements['DB_PASSWORD'] = $data['db_password'];
        }
        
        foreach ($replacements as $key => $value) {
            $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
        }
        
        File::put($envPath, $envContent);
    }
}