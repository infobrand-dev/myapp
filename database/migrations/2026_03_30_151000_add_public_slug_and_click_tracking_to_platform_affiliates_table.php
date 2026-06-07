<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_affiliates', function (Blueprint $table) {
            $table->string('slug', 120)->nullable()->after('phone');
            $table->unsignedInteger('click_count')->default(0)->after('meta');
            $table->timestamp('last_clicked_at')->nullable()->after('welcome_emailed_at');
        });

        DB::table('platform_affiliates')->orderBy('id')->get()->each(function ($affiliate): void {
            $slug = Str::slug($affiliate->name) ?: 'affiliate';
            $baseSlug = $slug;
            $counter = 2;

            while (DB::table('platform_affiliates')
                ->where('id', '!=', $affiliate->id)
                ->where('slug', $slug)
                ->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            DB::table('platform_affiliates')
                ->where('id', $affiliate->id)
                ->update(['slug' => $slug]);
        });

        Schema::table('platform_affiliates', function (Blueprint $table) {
            $table->string('slug', 120)->nullable(false)->change();
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('platform_affiliates', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'click_count', 'last_clicked_at']);
        });
    }
};
