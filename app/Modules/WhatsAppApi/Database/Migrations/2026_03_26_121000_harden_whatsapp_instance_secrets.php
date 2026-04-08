<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $supportsAfter = in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);

        if (!$this->hasColumn('whatsapp_instances', 'api_token_hash')) {
            Schema::table('whatsapp_instances', function (Blueprint $table) use ($supportsAfter) {
                $column = $table->string('api_token_hash', 64)->nullable();

                if ($supportsAfter) {
                    $column->after('api_token');
                }

                $column->index();
            });
        }

        $this->changeApiTokenColumnToText();
        $this->backfillSecrets();
    }

    public function down(): void
    {
        if ($this->hasColumn('whatsapp_instances', 'api_token_hash')) {
            Schema::table('whatsapp_instances', function (Blueprint $table) {
                $table->dropColumn('api_token_hash');
            });
        }
    }

    private function backfillSecrets(): void
    {
        DB::table('whatsapp_instances')
            ->select(['id', 'api_token', 'api_token_hash', 'cloud_token', 'settings'])
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $apiToken = trim((string) ($row->api_token ?? ''));
                    $cloudToken = trim((string) ($row->cloud_token ?? ''));
                    $settings = json_decode((string) ($row->settings ?? ''), true);
                    $settings = is_array($settings) ? $settings : [];

                    $plainApiToken = $apiToken === '' ? '' : $this->decryptSecret($apiToken);
                    $plainCloudToken = $cloudToken === '' ? '' : $this->decryptSecret($cloudToken);

                    DB::table('whatsapp_instances')
                        ->where('id', $row->id)
                        ->update([
                            'api_token' => $plainApiToken !== '' ? $this->encryptSecret($plainApiToken) : null,
                            'api_token_hash' => $plainApiToken !== '' ? hash('sha256', $plainApiToken) : null,
                            'cloud_token' => $plainCloudToken !== '' ? $this->encryptSecret($plainCloudToken) : null,
                            'settings' => json_encode($this->encryptSettings($settings)),
                        ]);
                }
            });
    }

    private function changeApiTokenColumnToText(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE whatsapp_instances MODIFY api_token TEXT NULL');
        }
    }

    private function encryptSettings(array $settings): array
    {
        foreach (['wa_cloud_verify_token', 'wa_cloud_app_secret'] as $key) {
            $value = trim((string) ($settings[$key] ?? ''));
            if ($value !== '') {
                $settings[$key] = $this->encryptSecret($this->decryptSecret($value));
            }
        }

        return $settings;
    }

    private function hasColumn(string $table, string $column): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }

    private function encryptSecret(string $value): string
    {
        return str_starts_with($value, 'enc::')
            ? $value
            : 'enc::' . Crypt::encryptString($value);
    }

    private function decryptSecret(string $value): string
    {
        if (!str_starts_with($value, 'enc::')) {
            return $value;
        }

        return Crypt::decryptString(substr($value, 5));
    }
};
