<?php

use App\Models\PlatformAffiliate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        PlatformAffiliate::query()->orderBy('id')->get()->each(function (PlatformAffiliate $affiliate): void {
            $slug = Str::slug($affiliate->name) ?: 'affiliate';
            $baseSlug = $slug;
            $counter = 2;

            while (PlatformAffiliate::query()
                ->where('id', '!=', $affiliate->id)
                ->where('slug', $slug)
                ->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $affiliate->forceFill(['slug' => $slug])->save();
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
