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
        if (!$this->hasColumn('social_accounts', 'access_token_hash')) {
            Schema::table('social_accounts', function (Blueprint $table) {
                $table->string('access_token_hash', 64)->nullable()->after('access_token')->index();
            });
        }

        $this->changeTokenColumnToText();
        $this->backfillAccessTokens();
    }

    public function down(): void
    {
        if ($this->hasColumn('social_accounts', 'access_token_hash')) {
            Schema::table('social_accounts', function (Blueprint $table) {
                $table->dropColumn('access_token_hash');
            });
        }
    }

    private function backfillAccessTokens(): void
    {
        DB::table('social_accounts')
            ->select(['id', 'access_token', 'access_token_hash'])
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $token = trim((string) ($row->access_token ?? ''));
                    if ($token === '') {
                        continue;
                    }

                    $plainToken = $this->decryptSecret($token);

                    DB::table('social_accounts')
                        ->where('id', $row->id)
                        ->update([
                            'access_token' => $this->encryptSecret($plainToken),
                            'access_token_hash' => hash('sha256', $plainToken),
                        ]);
                }
            });
    }

    private function changeTokenColumnToText(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE social_accounts MODIFY access_token TEXT NOT NULL');
        }
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
