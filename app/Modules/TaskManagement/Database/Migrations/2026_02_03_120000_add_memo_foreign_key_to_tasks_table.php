<?php

use App\Support\Database\SchemaInspector;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tasks') || !Schema::hasTable('memos')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            if (!SchemaInspector::foreignKeyExists('tasks', 'tasks_memo_id_foreign')) {
                $table->foreign('memo_id')->references('id')->on('memos')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tasks')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            if (SchemaInspector::foreignKeyExists('tasks', 'tasks_memo_id_foreign')) {
                $table->dropForeign('tasks_memo_id_foreign');
            }
        });
    }
};
