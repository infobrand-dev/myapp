<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('approval_requests', 'required_approvals')) {
                $table->unsignedInteger('required_approvals')->default(1)->after('status');
            }

            if (!Schema::hasColumn('approval_requests', 'current_approvals')) {
                $table->unsignedInteger('current_approvals')->default(0)->after('required_approvals');
            }
        });

        Schema::create('approval_request_decisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_request_id')->index();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('approver_id')->index();
            $table->string('decision', 20);
            $table->text('notes')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->unique(['approval_request_id', 'approver_id'], 'approval_request_decision_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_request_decisions');

        Schema::table('approval_requests', function (Blueprint $table) {
            foreach (['required_approvals', 'current_approvals'] as $column) {
                if (Schema::hasColumn('approval_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
