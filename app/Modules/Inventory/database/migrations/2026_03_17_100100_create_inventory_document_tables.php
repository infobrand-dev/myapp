<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stock_openings', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('inventory_location_id')->constrained('inventory_locations')->cascadeOnDelete();
            $table->date('opening_date');
            $table->string('status', 20)->default('posted');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_stock_opening_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opening_id')->constrained('inventory_stock_openings')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('quantity', 18, 4);
            $table->decimal('minimum_quantity', 18, 4)->default(0);
            $table->decimal('reorder_quantity', 18, 4)->default(0);
            $table->foreignId('movement_id')->nullable()->constrained('inventory_stock_movements')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('inventory_location_id')->constrained('inventory_locations')->cascadeOnDelete();
            $table->date('adjustment_date');
            $table->string('status', 20)->default('posted');
            $table->string('reason_code', 100);
            $table->text('reason_text');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjustment_id')->constrained('inventory_stock_adjustments')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('direction', 10);
            $table->decimal('quantity', 18, 4);
            $table->foreignId('movement_id')->nullable()->constrained('inventory_stock_movements')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('source_location_id')->constrained('inventory_locations')->cascadeOnDelete();
            $table->foreignId('destination_location_id')->constrained('inventory_locations')->cascadeOnDelete();
            $table->date('transfer_date');
            $table->string('status', 20)->default('draft');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'transfer_date']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('inventory_stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('inventory_stock_transfers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->decimal('requested_quantity', 18, 4);
            $table->decimal('sent_quantity', 18, 4)->default(0);
            $table->decimal('received_quantity', 18, 4)->default(0);
            $table->foreignId('transfer_out_movement_id')->nullable()->constrained('inventory_stock_movements')->nullOnDelete();
            $table->foreignId('transfer_in_movement_id')->nullable()->constrained('inventory_stock_movements')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::table('inventory_locations')->insert([
            'code' => 'MAIN',
            'name' => 'Main Warehouse',
            'type' => 'warehouse',
            'is_default' => true,
            'is_active' => true,
            'meta' => json_encode(['seeded_by' => 'inventory']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_transfer_items');
        Schema::dropIfExists('inventory_stock_transfers');
        Schema::dropIfExists('inventory_stock_adjustment_items');
        Schema::dropIfExists('inventory_stock_adjustments');
        Schema::dropIfExists('inventory_stock_opening_items');
        Schema::dropIfExists('inventory_stock_openings');
    }
};
