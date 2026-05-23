<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_feature_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('product_line', 50)->default('accounting');
            $table->string('feature_key', 100);
            $table->string('value', 50);
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'product_line', 'feature_key'], 'user_feature_preferences_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_feature_preferences');
    }
};
