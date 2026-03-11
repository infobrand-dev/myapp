<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_contact_phone_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 30)->unique();
            $table->string('last_contact_name')->nullable();
            $table->string('status', 30)->default('active'); // active|blocked
            $table->unsignedInteger('failure_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('last_failed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_failed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_contact_phone_statuses');
    }
};
