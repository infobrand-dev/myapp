<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('language')->default('en');
            $table->string('category')->nullable(); // marketing/utility/authentication
            $table->string('namespace')->nullable();
            $table->text('body');
            $table->json('components')->nullable(); // header/footer/buttons
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_templates');
    }
};
