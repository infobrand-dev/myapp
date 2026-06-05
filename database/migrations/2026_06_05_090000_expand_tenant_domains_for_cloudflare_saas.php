<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection($this->connection)->table('tenant_domains', function (Blueprint $table) {
            $table->string('hostname')->nullable()->after('tenant_id');
            $table->string('hostname_type', 20)->nullable()->after('hostname');
            $table->string('provider', 40)->default('cloudflare_saas')->after('hostname_type');
            $table->string('cloudflare_hostname_id', 64)->nullable()->after('status');
            $table->string('cloudflare_ssl_status', 40)->nullable()->after('cloudflare_hostname_id');
            $table->string('verification_method', 20)->nullable()->after('cloudflare_ssl_status');
            $table->boolean('is_canonical')->default(false)->after('is_primary');
            $table->string('ownership_dns_name')->nullable()->after('verification_method');
            $table->text('ownership_dns_value')->nullable()->after('ownership_dns_name');
            $table->string('routing_record_type', 20)->nullable()->after('ownership_dns_value');
            $table->string('routing_record_name')->nullable()->after('routing_record_type');
            $table->text('routing_record_value')->nullable()->after('routing_record_name');
            $table->timestamp('last_synced_at')->nullable()->after('routing_record_value');
            $table->timestamp('last_verified_at')->nullable()->after('last_synced_at');
            $table->timestamp('activation_checked_at')->nullable()->after('last_verified_at');
            $table->string('last_error_code', 120)->nullable()->after('activation_checked_at');
            $table->text('last_error_message')->nullable()->after('last_error_code');
            $table->json('meta')->nullable()->after('last_error_message');

            $table->index(['status', 'is_canonical'], 'tenant_domains_status_canonical_idx');
            $table->index(['tenant_id', 'is_primary'], 'tenant_domains_tenant_primary_idx');
        });

        DB::connection($this->connection)->table('tenant_domains')
            ->orderBy('id')
            ->get(['id', 'domain', 'status', 'is_primary'])
            ->each(function (object $row): void {
                $hostname = strtolower(trim((string) $row->domain));
                $type = substr_count($hostname, '.') <= 1 ? 'apex' : 'subdomain';

                DB::connection($this->connection)->table('tenant_domains')
                    ->where('id', $row->id)
                    ->update([
                        'hostname' => $hostname,
                        'hostname_type' => $type,
                        'verification_method' => 'txt',
                        'routing_record_type' => $type === 'apex' ? 'A' : 'CNAME',
                        'is_canonical' => (bool) $row->is_primary,
                        'meta' => json_encode([
                            'legacy_domain_column' => $row->domain,
                            'migrated_to_hostname' => true,
                        ]),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('tenant_domains', function (Blueprint $table) {
            $table->dropIndex('tenant_domains_status_canonical_idx');
            $table->dropIndex('tenant_domains_tenant_primary_idx');
            $table->dropColumn([
                'hostname',
                'hostname_type',
                'provider',
                'cloudflare_hostname_id',
                'cloudflare_ssl_status',
                'verification_method',
                'is_canonical',
                'ownership_dns_name',
                'ownership_dns_value',
                'routing_record_type',
                'routing_record_name',
                'routing_record_value',
                'last_synced_at',
                'last_verified_at',
                'activation_checked_at',
                'last_error_code',
                'last_error_message',
                'meta',
            ]);
        });
    }
};
