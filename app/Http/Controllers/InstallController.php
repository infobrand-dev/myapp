<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\TenantRoleProvisioner;
use Database\Seeders\TenantSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class InstallController extends Controller
{
    public function index(): View
    {
        return $this->renderInstall();
    }

    public function testDatabase(Request $request): View
    {
        try {
            $data = $this->validatedConfig($request, false);
            $this->testConnection($data);
            return $this->renderInstall(
                $data,
                'Koneksi database berhasil.',
                'success'
            );
        } catch (Throwable $e) {
            return $this->renderInstall(
                $request->only([
                    'app_name',
                    'app_url',
                    'db_connection',
                    'db_host',
                    'db_port',
                    'db_database',
                    'db_username',
                    'db_password',
                    'db_sslmode',
                    'admin_name',
                    'admin_email',
                ]),
                'Koneksi database gagal: ' . $e->getMessage(),
                'error'
            );
        }
    }

    public function run(Request $request): RedirectResponse|View
    {
        try {
            $data = $this->validatedConfig($request, true);
            $this->ensureEnvFileExistsFromExample();

            if (!$this->isEnvWritable()) {
                return $this->renderInstall(
                    $data,
                    'File .env tidak bisa ditulis. Cek permission terlebih dahulu.',
                    'error'
                );
            }

            $this->writeEnv([
                'APP_NAME' => $data['app_name'],
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
                'APP_URL' => $data['app_url'],
                'APP_KEY' => trim((string) env('APP_KEY', '')),
                'APP_INSTALLED' => 'false',
                'LOG_LEVEL' => 'warning',
                'DB_CONNECTION' => $data['db_connection'],
                'DB_HOST' => $data['db_host'],
                'DB_PORT' => $data['db_port'],
                'DB_DATABASE' => $data['db_database'],
                'DB_USERNAME' => $data['db_username'],
                'DB_PASSWORD' => $data['db_password'],
                'DB_SSLMODE' => $data['db_sslmode'] ?? '',
            ]);

            $this->applyDatabaseConfig($data);
            $this->testConnection($data);

            $this->callArtisanOrFail('key:generate', ['--force' => true]);
            if (!$this->hasAppKeyInEnv()) {
                throw new RuntimeException('APP_KEY gagal dibuat atau tidak tersimpan di file .env.');
            }

            $this->callArtisanOrFail('migrate', [
                '--path' => 'database/migrations',
                '--force' => true,
            ]);

            // Installer web must always leave tenant id=1 available for tenant-aware writes.
            $this->callArtisanOrFail('db:seed', [
                '--class' => TenantSeeder::class,
                '--force' => true,
            ]);
            $this->ensureDefaultTenantExists();

            // Keep non-user seeders, but skip default superadmin account during installer.
            config(['installer.seed_default_superadmin' => false]);
            $this->callArtisanOrFail('db:seed', ['--force' => true]);
            $this->ensureDefaultTenantExists();

            DB::transaction(function () use ($data): void {
                app(TenantRoleProvisioner::class)->ensureForTenant(1);
                app(PermissionRegistrar::class)->setPermissionsTeamId(1);

                try {
                    $role = Role::findOrCreate('Super-admin');

                    $user = User::query()->firstOrNew([
                        'tenant_id' => 1,
                        'email' => $data['admin_email'],
                    ]);
                    $user->forceFill([
                        'tenant_id' => 1,
                        'name' => $data['admin_name'],
                        'email' => $data['admin_email'],
                        'password' => Hash::make($data['admin_password']),
                        'email_verified_at' => now(),
                    ]);
                    $user->saveOrFail();
                    $user->syncRoles([$role->name]);

                    if (!$user->fresh()->hasRole($role->name)) {
                        throw new RuntimeException('Akun Super-admin gagal dipasangkan role.');
                    }
                } finally {
                    app(PermissionRegistrar::class)->setPermissionsTeamId(null);
                }
            });

            // Keep bootstrap/cache fresh before finalizing installation.
            $this->callArtisanOrFail('optimize:clear');

            file_put_contents(storage_path('app/installed.lock'), now()->toDateTimeString());
            $this->writeEnv(['APP_INSTALLED' => 'true']);

            return redirect('/login?installed=1');
        } catch (Throwable $e) {
            return $this->renderInstall(
                $request->only([
                    'app_name',
                    'app_url',
                    'db_connection',
                    'db_host',
                    'db_port',
                    'db_database',
                    'db_username',
                    'db_password',
                    'db_sslmode',
                    'admin_name',
                    'admin_email',
                ]),
                'Instalasi gagal: ' . $e->getMessage(),
                'error'
            );
        }
    }

    private function renderInstall(array $overrides = [], ?string $statusMessage = null, string $statusLevel = 'info'): View
    {
        if ($statusMessage !== null && $statusMessage !== '') {
            $statusMessage = sprintf('[%s] %s', now()->format('H:i:s'), $statusMessage);
        }

        return view('install.index', [
            'defaults' => array_merge([
                'app_name' => env('APP_NAME', 'Meetra'),
                'app_url' => env('APP_URL', 'http://127.0.0.1:8000'),
                'db_connection' => env('DB_CONNECTION', 'mysql'),
                'db_host' => env('DB_HOST', '127.0.0.1'),
                'db_port' => env('DB_PORT', '3306'),
                'db_database' => env('DB_DATABASE', ''),
                'db_username' => env('DB_USERNAME', ''),
                'db_password' => env('DB_PASSWORD', ''),
                'db_sslmode' => env('DB_SSLMODE', ''),
                'admin_name' => 'Super Admin',
                'admin_email' => 'superadmin@myapp.test',
            ], $overrides),
            'checks' => $this->systemChecks(),
            'statusMessage' => $statusMessage,
            'statusLevel' => $statusLevel,
        ]);
    }

    private function validatedConfig(Request $request, bool $withAdmin): array
    {
        $rules = [
            'app_name' => ['required', 'string', 'max:100'],
            'app_url' => ['required', 'url', 'max:255'],
            'db_connection' => ['required', 'string', 'in:mysql,pgsql'],
            'db_host' => ['required', 'string', 'max:100'],
            'db_port' => ['required', 'numeric'],
            'db_database' => ['required', 'string', 'max:100'],
            'db_username' => ['required', 'string', 'max:100'],
            'db_password' => ['nullable', 'string', 'max:200'],
            'db_sslmode' => ['nullable', 'string', 'max:50'],
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
        $driver = $this->databaseDriver($data);

        config([
            'database.default' => $driver,
            'database.connections.mysql.host' => $data['db_host'],
            'database.connections.mysql.port' => $data['db_port'],
            'database.connections.mysql.database' => $data['db_database'],
            'database.connections.mysql.username' => $data['db_username'],
            'database.connections.mysql.password' => $data['db_password'] ?? '',
            'database.connections.pgsql.host' => $data['db_host'],
            'database.connections.pgsql.port' => $data['db_port'],
            'database.connections.pgsql.database' => $data['db_database'],
            'database.connections.pgsql.username' => $data['db_username'],
            'database.connections.pgsql.password' => $data['db_password'] ?? '',
            'database.connections.pgsql.sslmode' => $data['db_sslmode'] ?? env('DB_SSLMODE', 'prefer'),
        ]);

        DB::purge($driver);
        DB::reconnect($driver);
    }

    private function testConnection(array $data): void
    {
        $driver = $this->databaseDriver($data);
        $this->applyDatabaseConfig($data);
        DB::connection($driver)->getPdo();
    }

    private function systemChecks(): array
    {
        $extensions = ['openssl', 'pdo', 'pdo_mysql', 'pdo_pgsql', 'mbstring', 'json', 'curl', 'fileinfo'];
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
            $examplePath = base_path('.env.example');
            return file_exists($examplePath) && is_readable($examplePath) && is_writable(base_path());
        }
        return is_writable($path);
    }

    private function writeEnv(array $pairs): void
    {
        $this->ensureEnvFileExistsFromExample();

        $envPath = base_path('.env');
        $content = file_exists($envPath) ? (string) file_get_contents($envPath) : '';
        $content = $this->stripUtf8Bom($content);

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

    private function ensureEnvFileExistsFromExample(): void
    {
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            return;
        }

        $examplePath = base_path('.env.example');
        if (!file_exists($examplePath)) {
            throw new RuntimeException('File .env.example tidak ditemukan. Installer membutuhkan file ini untuk membuat .env awal.');
        }
        if (!is_readable($examplePath)) {
            throw new RuntimeException('File .env.example tidak bisa dibaca.');
        }
        if (!is_writable(base_path())) {
            throw new RuntimeException('Folder aplikasi tidak bisa ditulis untuk membuat file .env.');
        }

        $content = (string) file_get_contents($examplePath);
        if ($content === '' && filesize($examplePath) > 0) {
            throw new RuntimeException('Gagal membaca isi .env.example.');
        }
        $content = $this->stripUtf8Bom($content);

        file_put_contents($envPath, $content);
    }

    private function escapeEnvValue(string $value): string
    {
        if ($value === '') {
            return '""';
        }

        // Always quote installer-written env values so special characters
        // such as $, spaces, #, =, and backslashes round-trip safely.
        $escaped = str_replace(
            ['\\', '"', '$'],
            ['\\\\', '\\"', '\\$'],
            $value
        );

        return "\"{$escaped}\"";
    }

    private function callArtisanOrFail(string $command, array $parameters = []): void
    {
        $exitCode = Artisan::call($command, $parameters);
        if ($exitCode !== 0) {
            $output = trim((string) Artisan::output());
            throw new RuntimeException("Command '{$command}' gagal dijalankan. {$output}");
        }
    }

    private function hasAppKeyInEnv(): bool
    {
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            return false;
        }

        $content = (string) file_get_contents($envPath);
        $content = $this->stripUtf8Bom($content);
        if (!preg_match('/^APP_KEY=(.*)$/m', $content, $matches)) {
            return false;
        }

        $value = trim((string) $matches[1], " \t\n\r\0\x0B\"'");
        return $value !== '';
    }

    private function ensureDefaultTenantExists(): void
    {
        $tenant = DB::table('tenants')->where('id', 1)->first();

        if (!$tenant) {
            throw new RuntimeException('Default tenant id=1 gagal dibuat saat instalasi.');
        }
    }

    private function stripUtf8Bom(string $content): string
    {
        if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
            return substr($content, 3);
        }

        return $content;
    }

    private function databaseDriver(array $data): string
    {
        return in_array(($data['db_connection'] ?? 'mysql'), ['mysql', 'pgsql'], true)
            ? $data['db_connection']
            : 'mysql';
    }

}
