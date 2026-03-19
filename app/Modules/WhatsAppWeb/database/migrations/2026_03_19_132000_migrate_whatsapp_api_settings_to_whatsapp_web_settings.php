<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_web_settings') || !Schema::hasTable('whatsapp_api_settings')) {
            return;
        }

        Schema::create('whatsapp_web_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('whatsapp_web');
            $table->string('base_url')->nullable();
            $table->string('verify_token')->nullable();
            $table->string('default_sender_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('timeout_seconds')->default(30);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status')->nullable();
            $table->text('last_test_message')->nullable();
            $table->timestamps();
        });

        DB::table('whatsapp_api_settings')
            ->orderBy('id')
            ->get()
            ->each(function ($row): void {
                DB::table('whatsapp_web_settings')->insert([
                    'id' => $row->id,
                    'provider' => 'whatsapp_web',
                    'base_url' => $row->base_url,
                    'verify_token' => $row->verify_token,
                    'default_sender_name' => $row->default_sender_name,
                    'is_active' => $row->is_active,
                    'timeout_seconds' => $row->timeout_seconds,
                    'notes' => $row->notes,
                    'created_by' => $row->created_by,
                    'updated_by' => $row->updated_by,
                    'last_tested_at' => $row->last_tested_at,
                    'last_test_status' => $row->last_test_status,
                    'last_test_message' => $row->last_test_message,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('whatsapp_web_settings')) {
            return;
        }

        Schema::dropIfExists('whatsapp_web_settings');
    }
};
