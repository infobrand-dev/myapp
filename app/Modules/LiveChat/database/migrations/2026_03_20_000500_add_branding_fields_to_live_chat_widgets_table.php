<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('live_chat_widgets')) {
            return;
        }

        Schema::table('live_chat_widgets', function (Blueprint $table) {
            if (!Schema::hasColumn('live_chat_widgets', 'launcher_label')) {
                $table->string('launcher_label', 40)->nullable()->after('theme_color');
            }
            if (!Schema::hasColumn('live_chat_widgets', 'position')) {
                $table->string('position', 20)->nullable()->after('launcher_label');
            }
            if (!Schema::hasColumn('live_chat_widgets', 'logo_url')) {
                $table->string('logo_url', 500)->nullable()->after('position');
            }
            if (!Schema::hasColumn('live_chat_widgets', 'header_bg_color')) {
                $table->string('header_bg_color', 20)->nullable()->after('logo_url');
            }
            if (!Schema::hasColumn('live_chat_widgets', 'visitor_bubble_color')) {
                $table->string('visitor_bubble_color', 20)->nullable()->after('header_bg_color');
            }
            if (!Schema::hasColumn('live_chat_widgets', 'agent_bubble_color')) {
                $table->string('agent_bubble_color', 20)->nullable()->after('visitor_bubble_color');
            }
        });

        if (Schema::hasColumn('live_chat_widgets', 'launcher_label')) {
            DB::table('live_chat_widgets')->where('launcher_label', '')->update(['launcher_label' => null]);
        }

        if (Schema::hasColumn('live_chat_widgets', 'position')) {
            DB::table('live_chat_widgets')->where('position', '')->update(['position' => null]);
        }

        if (Schema::hasColumn('live_chat_widgets', 'logo_url')) {
            DB::table('live_chat_widgets')->where('logo_url', '')->update(['logo_url' => null]);
        }

        if (Schema::hasColumn('live_chat_widgets', 'header_bg_color')) {
            DB::table('live_chat_widgets')->where('header_bg_color', '')->update(['header_bg_color' => null]);
        }

        if (Schema::hasColumn('live_chat_widgets', 'visitor_bubble_color')) {
            DB::table('live_chat_widgets')->where('visitor_bubble_color', '')->update(['visitor_bubble_color' => null]);
        }

        if (Schema::hasColumn('live_chat_widgets', 'agent_bubble_color')) {
            DB::table('live_chat_widgets')->where('agent_bubble_color', '')->update(['agent_bubble_color' => null]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('live_chat_widgets')) {
            return;
        }

        Schema::table('live_chat_widgets', function (Blueprint $table) {
            foreach ([
                'agent_bubble_color',
                'visitor_bubble_color',
                'header_bg_color',
                'logo_url',
                'position',
                'launcher_label',
            ] as $column) {
                if (Schema::hasColumn('live_chat_widgets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
