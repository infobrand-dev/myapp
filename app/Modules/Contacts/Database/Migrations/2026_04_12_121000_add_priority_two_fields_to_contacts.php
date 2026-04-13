<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contacts')) {
            Schema::table('contacts', function (Blueprint $table) {
                if (!Schema::hasColumn('contacts', 'payment_term_days')) {
                    $table->unsignedInteger('payment_term_days')->nullable()->after('industry');
                }

                if (!Schema::hasColumn('contacts', 'credit_limit')) {
                    $table->decimal('credit_limit', 18, 2)->nullable()->after('payment_term_days');
                }

                if (!Schema::hasColumn('contacts', 'contact_person_name')) {
                    $table->string('contact_person_name')->nullable()->after('credit_limit');
                }

                if (!Schema::hasColumn('contacts', 'contact_person_phone')) {
                    $table->string('contact_person_phone')->nullable()->after('contact_person_name');
                }

                if (!Schema::hasColumn('contacts', 'billing_address')) {
                    $table->text('billing_address')->nullable()->after('country');
                }

                if (!Schema::hasColumn('contacts', 'shipping_address')) {
                    $table->text('shipping_address')->nullable()->after('billing_address');
                }

                if (!Schema::hasColumn('contacts', 'tags')) {
                    $table->json('tags')->nullable()->after('shipping_address');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contacts')) {
            Schema::table('contacts', function (Blueprint $table) {
                foreach (['tags', 'shipping_address', 'billing_address', 'contact_person_phone', 'contact_person_name', 'credit_limit', 'payment_term_days'] as $column) {
                    if (Schema::hasColumn('contacts', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
