<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_user_presences') && !Schema::hasTable('user_presences')) {
            Schema::rename('whatsapp_user_presences', 'user_presences');
        }

        if (!Schema::hasTable('user_presences')) {
            Schema::create('user_presences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('manual_status', 20)->nullable();
                $table->timestamp('last_heartbeat_at')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();

                $table->unique('user_id');
                $table->index(['manual_status', 'last_heartbeat_at']);
            });

            return;
        }

        Schema::table('user_presences', function (Blueprint $table) {
            $sm = Schema::getConnection()->getSchemaBuilder();

            if (!$sm->hasColumn('user_presences', 'manual_status')) {
                $table->string('manual_status', 20)->nullable()->after('user_id');
            }

            if (!$sm->hasColumn('user_presences', 'last_heartbeat_at')) {
                $table->timestamp('last_heartbeat_at')->nullable()->after('manual_status');
            }

            if (!$sm->hasColumn('user_presences', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('last_heartbeat_at');
            }

            if (!$sm->hasColumn('user_presences', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (!$sm->hasColumn('user_presences', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('user_presences')) {
            Schema::drop('user_presences');
        }
    }
};
