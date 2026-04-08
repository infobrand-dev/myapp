<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inventory_locations')) {
            Schema::create('inventory_locations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(1)->index();
                $table->unsignedBigInteger('company_id')->default(1)->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->foreignId('parent_id')->nullable()->constrained('inventory_locations')->nullOnDelete();
                $table->string('code');
                $table->string('name');
                $table->string('type', 50)->default('warehouse');
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'company_id', 'branch_id', 'code']);
                $table->index(['tenant_id', 'company_id', 'branch_id', 'type', 'is_active'], 'inventory_locations_scope_type_idx');
                $table->index(['reference_type', 'reference_id']);
            });
        }

        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_locations');
    }

    private function seedDefaults(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $exists = DB::table('inventory_locations')
                ->where('tenant_id', 1)
                ->where('company_id', 1)
                ->whereNull('branch_id')
                ->where('code', 'MAIN')
                ->exists();

            if ($exists) {
                return;
            }

            DB::statement(
                "insert into inventory_locations (tenant_id, company_id, branch_id, code, name, type, is_default, is_active, meta, created_at, updated_at)
                 values (?, ?, ?, ?, ?, ?, true, true, ?::jsonb, now(), now())",
                [1, 1, null, 'MAIN', 'Main Warehouse', 'warehouse', json_encode(['seeded_by' => 'inventory'])]
            );

            return;
        }

        $timestamp = now();
        DB::table('inventory_locations')->updateOrInsert(
            [
                'tenant_id' => 1,
                'company_id' => 1,
                'branch_id' => null,
                'code' => 'MAIN',
            ],
            [
                'name' => 'Main Warehouse',
                'type' => 'warehouse',
                'is_default' => true,
                'is_active' => true,
                'meta' => json_encode(['seeded_by' => 'inventory']),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]
        );
    }
};
