<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conversations')) {
            return;
        }

        if (!Schema::hasColumn('conversations', 'instance_id_key')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->unsignedBigInteger('instance_id_key')->storedAs('coalesce(`instance_id`, 0)');
            });
        }

        DB::transaction(function () {
            $groups = DB::table('conversations')
                ->selectRaw('channel, COALESCE(instance_id, 0) as instance_key, contact_external_id, COUNT(*) as total')
                ->whereNotNull('contact_external_id')
                ->groupBy('channel', DB::raw('COALESCE(instance_id, 0)'), 'contact_external_id')
                ->having('total', '>', 1)
                ->get();

            foreach ($groups as $group) {
                $rows = DB::table('conversations')
                    ->where('channel', $group->channel)
                    ->where('contact_external_id', $group->contact_external_id)
                    ->whereRaw('COALESCE(instance_id, 0) = ?', [(int) $group->instance_key])
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->get();

                $keep = $rows->first();
                if (!$keep) {
                    continue;
                }

                $keepId = (int) $keep->id;
                $duplicateIds = $rows->skip(1)->pluck('id')->map(fn ($id) => (int) $id)->all();
                if (empty($duplicateIds)) {
                    continue;
                }

                foreach ($duplicateIds as $oldId) {
                    DB::table('conversation_messages')
                        ->where('conversation_id', $oldId)
                        ->update(['conversation_id' => $keepId]);

                    if (Schema::hasTable('conversation_activity_logs')) {
                        DB::table('conversation_activity_logs')
                            ->where('conversation_id', $oldId)
                            ->update(['conversation_id' => $keepId]);
                    }

                    DB::statement(
                        'INSERT IGNORE INTO conversation_participants (conversation_id, user_id, role, invited_by, invited_at, left_at, created_at, updated_at)
                         SELECT ?, user_id, role, invited_by, invited_at, left_at, created_at, updated_at
                         FROM conversation_participants WHERE conversation_id = ?',
                        [$keepId, $oldId]
                    );

                    DB::table('conversation_participants')
                        ->where('conversation_id', $oldId)
                        ->delete();

                    DB::table('conversations')
                        ->where('id', $oldId)
                        ->delete();
                }

                $agg = DB::table('conversation_messages')
                    ->where('conversation_id', $keepId)
                    ->selectRaw('
                        MAX(created_at) as last_message_at,
                        MAX(CASE WHEN direction = "in" THEN created_at END) as last_incoming_at,
                        MAX(CASE WHEN direction = "out" THEN created_at END) as last_outgoing_at
                    ')
                    ->first();

                $ownerId = $rows->pluck('owner_id')->filter()->first();
                $unread = (int) $rows->sum(fn ($r) => (int) ($r->unread_count ?? 0));

                DB::table('conversations')
                    ->where('id', $keepId)
                    ->update([
                        'owner_id' => $keep->owner_id ?: $ownerId,
                        'last_message_at' => $agg->last_message_at ?? $keep->last_message_at,
                        'last_incoming_at' => $agg->last_incoming_at ?? $keep->last_incoming_at,
                        'last_outgoing_at' => $agg->last_outgoing_at ?? $keep->last_outgoing_at,
                        'unread_count' => $unread,
                        'updated_at' => now(),
                    ]);
            }
        });

        try {
            DB::statement('ALTER TABLE conversations DROP INDEX conversations_channel_instance_id_contact_external_id_unique');
        } catch (\Throwable $e) {
            // Old index may not exist on some environments.
        }

        try {
            DB::statement('ALTER TABLE conversations ADD UNIQUE INDEX conversations_channel_instance_key_contact_unique (channel, instance_id_key, contact_external_id)');
        } catch (\Throwable $e) {
            // Ignore if already exists.
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('conversations')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE conversations DROP INDEX conversations_channel_instance_key_contact_unique');
        } catch (\Throwable $e) {
            // Ignore when missing.
        }

        if (Schema::hasColumn('conversations', 'instance_id_key')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->dropColumn('instance_id_key');
            });
        }

        try {
            DB::statement('ALTER TABLE conversations ADD UNIQUE INDEX conversations_channel_instance_id_contact_external_id_unique (channel, instance_id, contact_external_id)');
        } catch (\Throwable $e) {
            // Ignore if index already exists or data no longer valid for rollback index.
        }
    }
};
