<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_shipping_providers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('provider', 100)->index();
            $table->string('display_name', 150)->nullable();
            $table->boolean('is_enabled')->default(false)->index();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'company_id', 'provider'], 'tenant_shipping_provider_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_shipping_providers');
    }
};
