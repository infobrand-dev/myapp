<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('memo_id')->nullable()->constrained('memos')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'done'])->default('pending');
            $table->date('due_date')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'due_date']);
            $table->index(['tenant_id', 'assigned_to', 'status']);

            if (in_array(DB::getDriverName(), ['mysql', 'pgsql'], true)) {
                $table->fullText(['title', 'description'], 'tasks_search_fulltext');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
