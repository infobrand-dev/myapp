<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection($this->connection)->create('tenant_domain_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_domain_id')->constrained('tenant_domains')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('event', 80);
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('actor_scope', 30)->default('system');
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'event'], 'tenant_domain_events_tenant_event_idx');
        });

        Schema::connection($this->connection)->create('cloudflare_saas_settings', function (Blueprint $table) {
            $table->id();
            $table->string('account_id', 80)->nullable();
            $table->string('zone_id', 80)->nullable();
            $table->text('api_token')->nullable();
            $table->string('fallback_origin_hostname')->nullable();
            $table->string('cname_target')->nullable();
            $table->boolean('apex_proxying_enabled')->default(false);
            $table->json('apex_ipv4_targets')->nullable();
            $table->json('apex_ipv6_targets')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_health_checked_at')->nullable();
            $table->text('last_error_summary')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('cloudflare_saas_settings');
        Schema::connection($this->connection)->dropIfExists('tenant_domain_events');
    }
};
