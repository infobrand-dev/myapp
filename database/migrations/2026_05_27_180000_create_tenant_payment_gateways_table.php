<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('provider', 50);
            $table->string('display_name', 120)->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'company_id', 'provider'], 'tenant_payment_gateways_scope_provider_unique');
            $table->index(['tenant_id', 'company_id', 'is_enabled'], 'tenant_payment_gateways_scope_enabled_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_payment_gateways');
    }
};
