<?php

namespace App\Modules\Crm\Support;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Crm\Models\CrmLead;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\CurrencySettingsResolver;
use App\Support\TenantContext;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrmLeadIngestionService
{
    public function __construct(
        private readonly CrmPipelineProvisioner $pipelines,
        private readonly CrmFollowUpTaskManager $followUpTasks,
        private readonly CrmTimelinePublisher $timeline,
        private readonly CrmIntegrationService $integrations,
        private readonly CrmOwnerRouter $ownerRouter,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function ingest(array $payload, string $sourceSuite = 'external', string $sourceModule = 'lead_capture'): CrmLead
    {
        return DB::transaction(function () use ($payload, $sourceSuite, $sourceModule): CrmLead {
            $tenantId = TenantContext::currentId();
            $contact = $this->resolveContact($payload, $tenantId);
            $existing = $this->resolveExistingLead($payload, $tenantId);

            $pipeline = $this->pipelines->ensureDefaultPipeline($tenantId, CompanyContext::currentId(), BranchContext::currentId());
            $stageCode = (string) ($payload['stage'] ?? CrmStageCatalog::NEW_LEAD);
            $stage = $pipeline->stages->firstWhere('code', $stageCode) ?: $pipeline->stages->sortBy('position')->first();

            $ownerId = !empty($payload['owner_user_id']) ? (int) $payload['owner_user_id'] : null;
            if ($ownerId) {
                $ownerId = User::query()->where('tenant_id', $tenantId)->whereKey($ownerId)->value('id');
            }
            if (!$ownerId) {
                $ownerId = $this->ownerRouter->resolveOwnerId($payload, $this->integrations->current());
            }

            $attributes = [
                'tenant_id' => $tenantId,
                'company_id' => $contact?->company_id ?? CompanyContext::currentId(),
                'branch_id' => $contact?->branch_id ?? BranchContext::currentId(),
                'contact_id' => $contact?->id,
                'pipeline_id' => $pipeline->id,
                'stage_id' => $stage?->id,
                'owner_user_id' => $ownerId,
                'title' => (string) ($payload['title'] ?? ('Lead ' . ($contact?->name ?? now()->format('YmdHis')))),
                'stage' => $stage?->code ?? CrmStageCatalog::NEW_LEAD,
                'priority' => (string) ($payload['priority'] ?? 'medium'),
                'lead_source' => (string) ($payload['lead_source'] ?? $sourceModule),
                'qualification_status' => $payload['qualification_status'] ?? null,
                'lead_score' => isset($payload['lead_score']) ? (int) $payload['lead_score'] : null,
                'estimated_value' => isset($payload['estimated_value']) ? (float) $payload['estimated_value'] : null,
                'currency' => (string) ($payload['currency'] ?? app(CurrencySettingsResolver::class)->defaultCurrency()),
                'probability' => isset($payload['probability']) ? (int) $payload['probability'] : ($stage?->probability_default ?? null),
                'expected_close_date' => $payload['expected_close_date'] ?? null,
                'next_follow_up_at' => $payload['next_follow_up_at'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'labels' => array_values(array_filter((array) ($payload['labels'] ?? []))),
                'visibility_scope' => (string) ($payload['visibility_scope'] ?? 'team'),
                'position' => $existing?->position ?? (((int) CrmLead::query()->where('tenant_id', $tenantId)->where('stage', $stage?->code)->max('position')) + 1),
                'meta' => array_merge((array) ($existing?->meta ?? []), [
                    'integration' => [
                        'external_reference' => $payload['external_reference'] ?? null,
                        'provider' => $payload['provider'] ?? $sourceModule,
                        'captured_via' => $sourceModule,
                    ],
                    'payload_excerpt' => Arr::only($payload, ['campaign_name', 'adset_name', 'form_name', 'provider']),
                ]),
            ];

            if ($existing) {
                $existing->update($attributes);
                $lead = $existing->fresh();
            } else {
                $lead = CrmLead::query()->create($attributes)->fresh();
            }
            $this->followUpTasks->syncPrimaryFollowUp($lead);
            $this->timeline->publish(
                $lead,
                'external_lead_captured',
                'Lead masuk dari integrasi',
                ($payload['provider'] ?? $sourceModule) . ' berhasil membuat/merge lead CRM.',
                [
                    'external_reference' => $payload['external_reference'] ?? null,
                    'provider' => $payload['provider'] ?? $sourceModule,
                    'source_payload' => Arr::only($payload, ['campaign_name', 'adset_name', 'form_name']),
                    'owner_user_id' => $lead->owner_user_id,
                ],
                $sourceSuite,
                $sourceModule
            );

            return $lead;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveContact(array $payload, int $tenantId): ?Contact
    {
        $email = trim((string) ($payload['email'] ?? ''));
        $mobile = trim((string) ($payload['mobile'] ?? ($payload['phone'] ?? '')));

        $contact = Contact::query()
            ->where('tenant_id', $tenantId)
            ->when($email !== '' || $mobile !== '', function ($query) use ($email, $mobile): void {
                $query->where(function ($lookup) use ($email, $mobile): void {
                    if ($email !== '') {
                        $lookup->orWhere('email', $email);
                    }

                    if ($mobile !== '') {
                        $lookup->orWhere('mobile', $mobile)
                            ->orWhere('phone', $mobile);
                    }
                });
            })
            ->first();

        if ($contact) {
            $contact->forceFill([
                'name' => $contact->name ?: (string) ($payload['name'] ?? $contact->name),
                'email' => $contact->email ?: ($email !== '' ? $email : null),
                'mobile' => $contact->mobile ?: ($mobile !== '' ? $mobile : null),
                'notes' => $contact->notes ?: ($payload['notes'] ?? null),
            ])->save();

            return $contact->fresh();
        }

        if (empty($payload['name']) && $email === '' && $mobile === '') {
            return null;
        }

        return Contact::query()->create([
            'tenant_id' => $tenantId,
            'company_id' => CompanyContext::currentId(),
            'branch_id' => BranchContext::currentId(),
            'type' => 'customer',
            'name' => (string) ($payload['name'] ?? ($email !== '' ? $email : $mobile)),
            'email' => $email !== '' ? $email : null,
            'mobile' => $mobile !== '' ? $mobile : null,
            'notes' => $payload['notes'] ?? null,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveExistingLead(array $payload, int $tenantId): ?CrmLead
    {
        $externalReference = trim((string) ($payload['external_reference'] ?? ''));

        if ($externalReference !== '') {
            $lead = CrmLead::query()
                ->where('tenant_id', $tenantId)
                ->where('meta->integration->external_reference', $externalReference)
                ->latest('id')
                ->first();

            if ($lead) {
                return $lead;
            }
        }

        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            return null;
        }

        return CrmLead::query()
            ->where('tenant_id', $tenantId)
            ->where('title', $title)
            ->where('created_at', '>=', now()->subDay())
            ->latest('id')
            ->first();
    }
}
