<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'company_id', 'slug']);
            $table->unique(['tenant_id', 'company_id', 'code']);
            $table->index(['tenant_id', 'company_id', 'is_active', 'name']);
        });

        DB::table('branches')->insert([
            'id' => 1,
            'tenant_id' => 1,
            'company_id' => 1,
            'name' => 'Main Branch',
            'slug' => 'main-branch',
            'code' => 'MAIN',
            'is_active' => true,
            'meta' => json_encode(['bootstrap' => true]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
