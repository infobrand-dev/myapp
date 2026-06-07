<?php

namespace App\Modules\Crm\Support;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Crm\Models\CrmFollowUpTask;
use App\Modules\Crm\Models\CrmLead;
use App\Modules\Crm\Models\CrmPipeline;
use App\Support\TenantContext;

class CrmOnboardingService
{
    public function shouldRedirectToWizard(?User $user = null): bool
    {
        $user ??= auth()->user();
        if (!$user) {
            return false;
        }

        $tenant = Tenant::query()->find($user->tenant_id);
        if (!$tenant) {
            return false;
        }

        $meta = (array) ($tenant->meta ?? []);

        return (($meta['crm_onboarding']['status'] ?? null) === 'pending');
    }

    public function markPending(Tenant $tenant): void
    {
        $meta = (array) ($tenant->meta ?? []);
        $crmMeta = (array) ($meta['crm_onboarding'] ?? []);
        $crmMeta['status'] = 'pending';
        $crmMeta['updated_at'] = now()->toIso8601String();
        $meta['crm_onboarding'] = $crmMeta;
        $tenant->forceFill(['meta' => $meta])->save();
    }

    public function markCompleted(Tenant $tenant): void
    {
        $meta = (array) ($tenant->meta ?? []);
        $crmMeta = (array) ($meta['crm_onboarding'] ?? []);
        $crmMeta['status'] = 'completed';
        $crmMeta['completed_at'] = now()->toIso8601String();
        $meta['crm_onboarding'] = $crmMeta;
        $tenant->forceFill(['meta' => $meta])->save();
    }

    public function wizardState(?int $tenantId = null): array
    {
        $tenantId ??= TenantContext::currentId();

        $contactCount = Contact::query()->where('tenant_id', $tenantId)->count();
        $pipelineCount = CrmPipeline::query()->where('tenant_id', $tenantId)->count();
        $teamCount = User::query()->where('tenant_id', $tenantId)->count();
        $leadCount = CrmLead::query()->where('tenant_id', $tenantId)->count();
        $followUpCount = CrmFollowUpTask::query()->where('tenant_id', $tenantId)->where('status', 'pending')->count();

        $steps = [
            ['key' => 'import_contacts', 'label' => 'Import Contacts', 'done' => $contactCount >= 10, 'hint' => $contactCount > 0 ? $contactCount . ' kontak tersedia' : 'Mulai dari daftar customer/prospek yang sudah ada'],
            ['key' => 'create_pipeline', 'label' => 'Buat Pipeline', 'done' => $pipelineCount >= 1, 'hint' => $pipelineCount > 0 ? $pipelineCount . ' pipeline tersedia' : 'Pipeline default akan disiapkan, tapi Anda tetap perlu meninjaunya'],
            ['key' => 'add_sales_team', 'label' => 'Tambah Sales Team', 'done' => $teamCount >= 2, 'hint' => $teamCount > 1 ? $teamCount . ' user aktif' : 'Tambah minimal 1 user selain admin utama'],
            ['key' => 'create_first_deal', 'label' => 'Buat First Deal', 'done' => $leadCount >= 1, 'hint' => $leadCount > 0 ? $leadCount . ' deal/lead aktif' : 'Tambahkan prospek pertama Anda'],
            ['key' => 'schedule_follow_up', 'label' => 'Buat Follow-Up', 'done' => $followUpCount >= 1, 'hint' => $followUpCount > 0 ? $followUpCount . ' follow-up pending' : 'Jadwalkan follow-up pertama agar dashboard hidup'],
        ];

        return [
            'steps' => $steps,
            'done_count' => collect($steps)->where('done', true)->count(),
            'is_complete' => collect($steps)->every(fn (array $step) => $step['done']),
        ];
    }
}
