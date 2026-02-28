<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Throwable;

class InstallController extends Controller
{
    public function index(): View
    {
        return view('install.index', [
            'defaults' => [
                'app_name' => env('APP_NAME', 'MyApp'),
                'app_url' => env('APP_URL', 'http://127.0.0.1:8000'),
                'db_host' => env('DB_HOST', '127.0.0.1'),
                'db_port' => env('DB_PORT', '3306'),
                'db_database' => env('DB_DATABASE', ''),
                'db_username' => env('DB_USERNAME', ''),
                'db_password' => env('DB_PASSWORD', ''),
                'admin_name' => 'Super Admin',
                'admin_email' => 'superadmin@myapp.test',
            ],
            'checks' => $this->systemChecks(),
        ]);
    }

    public function testDatabase(Request $request): RedirectResponse
    {
        try {
            $data = $this->validatedConfig($request, false);
            $this->testConnection($data);
            return redirect()->route('install.index', [
                'level' => 'success',
                'message' => 'Koneksi database berhasil.',
            ]);
        } catch (Throwable $e) {
            return redirect()->route('install.index', [
                'level' => 'error',
                'message' => 'Koneksi database gagal: ' . $e->getMessage(),
            ]);
        }
    }

    public function run(Request $request): RedirectResponse
    {
        try {
            $data = $this->validatedConfig($request, true);

            if (!$this->isEnvWritable()) {
                return redirect()->route('install.index', [
                    'level' => 'error',
                    'message' => 'File .env tidak bisa ditulis. Cek permission terlebih dahulu.',
                ]);
            }

            $this->writeEnv([
                'APP_NAME' => $data['app_name'],
                'APP_URL' => $data['app_url'],
                'APP_INSTALLED' => 'false',
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => $data['db_host'],
                'DB_PORT' => $data['db_port'],
                'DB_DATABASE' => $data['db_database'],
                'DB_USERNAME' => $data['db_username'],
                'DB_PASSWORD' => $data['db_password'],
            ]);

            $this->applyDatabaseConfig($data);
            $this->testConnection($data);

            if (trim((string) config('app.key', '')) === '') {
                Artisan::call('key:generate', ['--force' => true]);
            }

            Artisan::call('migrate', ['--seed' => true, '--force' => true]);

            $role = Role::findOrCreate('Super-admin');
            Role::findOrCreate('Admin');

            $user = User::updateOrCreate(
                ['email' => $data['admin_email']],
                [
                    'name' => $data['admin_name'],
                    'password' => Hash::make($data['admin_password']),
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles([$role->name]);

            file_put_contents(storage_path('app/installed.lock'), now()->toDateTimeString());
            $this->writeEnv(['APP_INSTALLED' => 'true']);

            Artisan::call('optimize:clear');

            return redirect('/login')->with('status', 'Instalasi selesai. Silakan login dengan akun Super-admin.');
        } catch (Throwable $e) {
            return redirect()->route('install.index', [
                'level' => 'error',
                'message' => 'Instalasi gagal: ' . $e->getMessage(),
            ]);
        }
    }

    private function validatedConfig(Request $request, bool $withAdmin): array
    {
        $rules = [
            'app_name' => ['required', 'string', 'max:100'],
            'app_url' => ['required', 'url', 'max:255'],
            'db_host' => ['required', 'string', 'max:100'],
            'db_port' => ['required', 'numeric'],
            'db_database' => ['required', 'string', 'max:100'],
            'db_username' => ['required', 'string', 'max:100'],
            'db_password' => ['nullable', 'string', 'max:200'],
        ];

        if ($withAdmin) {
            $rules['admin_name'] = ['required', 'string', 'max:100'];
            $rules['admin_email'] = ['required', 'email', 'max:150'];
            $rules['admin_password'] = ['required', 'string', 'min:8', 'max:100'];
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }

        return $validator->validated();
    }

    private function applyDatabaseConfig(array $data): void
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $data['db_host'],
            'database.connections.mysql.port' => $data['db_port'],
            'database.connections.mysql.database' => $data['db_database'],
            'database.connections.mysql.username' => $data['db_username'],
            'database.connections.mysql.password' => $data['db_password'] ?? '',
        ]);

        DB::purge('mysql');
        DB::reconnect('mysql');
    }

    private function testConnection(array $data): void
    {
        $this->applyDatabaseConfig($data);
        DB::connection('mysql')->getPdo();
    }

    private function systemChecks(): array
    {
        $extensions = ['openssl', 'pdo', 'pdo_mysql', 'mbstring', 'json', 'curl', 'fileinfo'];
        $extChecks = [];
        foreach ($extensions as $ext) {
            $extChecks[$ext] = extension_loaded($ext);
        }

        return [
            'php_version' => PHP_VERSION,
            'php_ok' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'extensions' => $extChecks,
            'env_writable' => $this->isEnvWritable(),
            'storage_writable' => is_writable(storage_path()),
            'cache_writable' => is_writable(base_path('bootstrap/cache')),
        ];
    }

    private function isEnvWritable(): bool
    {
        $path = base_path('.env');
        if (!file_exists($path)) {
            return is_writable(base_path());
        }
        return is_writable($path);
    }

    private function writeEnv(array $pairs): void
    {
        $envPath = base_path('.env');
        $content = file_exists($envPath) ? file_get_contents($envPath) : '';

        foreach ($pairs as $key => $value) {
            $value = $this->escapeEnvValue((string) $value);
            $pattern = "/^{$key}=.*$/m";
            $line = "{$key}={$value}";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $line, $content);
            } else {
                $content .= (str_ends_with($content, PHP_EOL) ? '' : PHP_EOL) . $line . PHP_EOL;
            }
        }

        file_put_contents($envPath, $content);
    }

    private function escapeEnvValue(string $value): string
    {
        if ($value === '') {
            return '""';
        }

        if (preg_match('/\s|#|=/', $value)) {
            $escaped = str_replace('"', '\"', $value);
            return "\"{$escaped}\"";
        }

        return $value;
    }
}
