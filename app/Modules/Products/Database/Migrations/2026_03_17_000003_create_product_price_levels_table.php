<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_price_levels')) {
            Schema::create('product_price_levels', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(1)->index();
                $table->string('code');
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('minimum_qty', 18, 4)->default(1);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['tenant_id', 'code']);
            });
        }

        $this->ensureSchema();
        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_levels');
    }

    private function seedDefaults(): void
    {
        $rows = [
            [
                'tenant_id' => 1,
                'code' => 'default',
                'name' => 'Retail',
                'description' => 'Harga jual retail standar.',
                'minimum_qty' => 1,
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'tenant_id' => 1,
                'code' => 'wholesale',
                'name' => 'Wholesale',
                'description' => 'Harga grosir dasar.',
                'minimum_qty' => 1,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'tenant_id' => 1,
                'code' => 'member',
                'name' => 'Member',
                'description' => 'Harga khusus member.',
                'minimum_qty' => 1,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 20,
            ],
        ];

        if (DB::getDriverName() === 'pgsql') {
            foreach ($rows as $row) {
                DB::statement(
                    sprintf(
                        'insert into product_price_levels (tenant_id, code, name, description, minimum_qty, is_default, is_active, sort_order, created_at, updated_at)
                         values (?, ?, ?, ?, ?, %s, %s, ?, now(), now())
                         on conflict (tenant_id, code) do nothing',
                        $row['is_default'] ? 'true' : 'false',
                        $row['is_active'] ? 'true' : 'false'
                    ),
                    [$row['tenant_id'], $row['code'], $row['name'], $row['description'], $row['minimum_qty'], $row['sort_order']]
                );
            }

            return;
        }

        $timestamp = now();
        foreach ($rows as $row) {
            DB::table('product_price_levels')->updateOrInsert(
                [
                    'tenant_id' => $row['tenant_id'],
                    'code' => $row['code'],
                ],
                $row + ['created_at' => $timestamp, 'updated_at' => $timestamp]
            );
        }
    }

    private function ensureSchema(): void
    {
        if (!Schema::hasColumn('product_price_levels', 'tenant_id')) {
            Schema::table('product_price_levels', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->default(1)->index();
            });
        }

        if ($this->indexExists('product_price_levels', 'product_price_levels_code_unique')
            && !$this->indexExists('product_price_levels', 'product_price_levels_tenant_id_code_unique')) {
            Schema::table('product_price_levels', function (Blueprint $table) {
                $table->dropUnique('product_price_levels_code_unique');
            });
        }

        if (!$this->indexExists('product_price_levels', 'product_price_levels_tenant_id_code_unique')) {
            Schema::table('product_price_levels', function (Blueprint $table) {
                $table->unique(['tenant_id', 'code']);
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'pgsql') {
            return (bool) DB::table('pg_indexes')
                ->where('schemaname', 'public')
                ->where('tablename', $table)
                ->where('indexname', $index)
                ->exists();
        }

        return !empty(DB::select('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$index]));
    }
};
