<?php

use App\Support\PlanLimit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        $defaults = [
            'free' => [
                PlanLimit::BRANCHES => 1,
                PlanLimit::SOCIAL_ACCOUNTS => 0,
                PlanLimit::LIVE_CHAT_WIDGETS => 0,
                PlanLimit::CHATBOT_ACCOUNTS => 0,
                PlanLimit::EMAIL_INBOX_ACCOUNTS => 0,
                PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY => 0,
                PlanLimit::EMAIL_RECIPIENTS_MONTHLY => 0,
                PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => 0,
                PlanLimit::AUTOMATION_WORKFLOWS => 0,
                PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 0,
            ],
            'starter' => [
                PlanLimit::BRANCHES => 1,
                PlanLimit::SOCIAL_ACCOUNTS => 2,
                PlanLimit::LIVE_CHAT_WIDGETS => 1,
                PlanLimit::CHATBOT_ACCOUNTS => 0,
                PlanLimit::EMAIL_INBOX_ACCOUNTS => 0,
                PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY => 0,
                PlanLimit::EMAIL_RECIPIENTS_MONTHLY => 0,
                PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => 0,
                PlanLimit::AUTOMATION_WORKFLOWS => 0,
                PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 0,
            ],
            'growth' => [
                PlanLimit::BRANCHES => 3,
                PlanLimit::SOCIAL_ACCOUNTS => 5,
                PlanLimit::LIVE_CHAT_WIDGETS => 2,
                PlanLimit::CHATBOT_ACCOUNTS => 2,
                PlanLimit::EMAIL_INBOX_ACCOUNTS => 1,
                PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY => 1500,
                PlanLimit::EMAIL_RECIPIENTS_MONTHLY => 0,
                PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => 25,
                PlanLimit::AUTOMATION_WORKFLOWS => 0,
                PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 0,
            ],
            'scale' => [
                PlanLimit::BRANCHES => 10,
                PlanLimit::SOCIAL_ACCOUNTS => 15,
                PlanLimit::LIVE_CHAT_WIDGETS => 5,
                PlanLimit::CHATBOT_ACCOUNTS => 10,
                PlanLimit::EMAIL_INBOX_ACCOUNTS => 3,
                PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY => 10000,
                PlanLimit::EMAIL_RECIPIENTS_MONTHLY => 0,
                PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => 200,
                PlanLimit::AUTOMATION_WORKFLOWS => 0,
                PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 0,
            ],
            'internal-unlimited' => [
                PlanLimit::BRANCHES => -1,
                PlanLimit::SOCIAL_ACCOUNTS => -1,
                PlanLimit::LIVE_CHAT_WIDGETS => -1,
                PlanLimit::CHATBOT_ACCOUNTS => -1,
                PlanLimit::EMAIL_INBOX_ACCOUNTS => -1,
                PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY => -1,
                PlanLimit::EMAIL_RECIPIENTS_MONTHLY => -1,
                PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => -1,
                PlanLimit::AUTOMATION_WORKFLOWS => -1,
                PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => -1,
            ],
        ];

        DB::table('subscription_plans')
            ->select(['id', 'code', 'limits'])
            ->orderBy('id')
            ->get()
            ->each(function ($plan) use ($defaults): void {
                $planDefaults = $defaults[$plan->code] ?? null;
                if (!$planDefaults) {
                    return;
                }

                $limits = json_decode((string) ($plan->limits ?? '{}'), true);
                $limits = is_array($limits) ? $limits : [];
                $changed = false;

                foreach ($planDefaults as $key => $value) {
                    if (!array_key_exists($key, $limits)) {
                        $limits[$key] = $value;
                        $changed = true;
                    }
                }

                if ($changed) {
                    DB::table('subscription_plans')
                        ->where('id', $plan->id)
                        ->update([
                            'limits' => json_encode($limits),
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        $keys = [
            PlanLimit::BRANCHES,
            PlanLimit::SOCIAL_ACCOUNTS,
            PlanLimit::LIVE_CHAT_WIDGETS,
            PlanLimit::CHATBOT_ACCOUNTS,
            PlanLimit::EMAIL_INBOX_ACCOUNTS,
            PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY,
            PlanLimit::EMAIL_RECIPIENTS_MONTHLY,
            PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS,
            PlanLimit::AUTOMATION_WORKFLOWS,
            PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY,
        ];

        DB::table('subscription_plans')
            ->select(['id', 'limits'])
            ->orderBy('id')
            ->get()
            ->each(function ($plan) use ($keys): void {
                $limits = json_decode((string) ($plan->limits ?? '{}'), true);
                $limits = is_array($limits) ? $limits : [];

                foreach ($keys as $key) {
                    unset($limits[$key]);
                }

                DB::table('subscription_plans')
                    ->where('id', $plan->id)
                    ->update([
                        'limits' => json_encode($limits),
                        'updated_at' => now(),
                    ]);
            });
    }
};
