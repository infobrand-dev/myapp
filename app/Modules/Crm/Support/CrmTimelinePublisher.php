<?php

namespace App\Modules\Crm\Support;

use App\Modules\Contacts\Models\Contact;
use App\Modules\Crm\Models\CrmActivity;
use App\Modules\Crm\Models\CrmLead;

class CrmTimelinePublisher
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function publish(
        CrmLead $lead,
        string $activityType,
        string $title,
        ?string $description = null,
        ?array $payload = null,
        string $sourceSuite = 'crm',
        string $sourceModule = 'crm'
    ): CrmActivity {
        return $this->publishForContact(
            $lead->contact,
            $activityType,
            $title,
            $description,
            $payload,
            $sourceSuite,
            $sourceModule,
            $lead,
            $lead->owner_user_id
        );
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function publishForContact(
        ?Contact $contact,
        string $activityType,
        string $title,
        ?string $description = null,
        ?array $payload = null,
        string $sourceSuite = 'crm',
        string $sourceModule = 'crm',
        ?CrmLead $lead = null,
        ?int $ownerUserId = null
    ): CrmActivity {
        return CrmActivity::query()->create([
            'tenant_id' => $lead?->tenant_id ?? $contact?->tenant_id ?? \App\Support\TenantContext::currentId(),
            'company_id' => $lead?->company_id ?? $contact?->company_id ?? \App\Support\CompanyContext::currentId(),
            'branch_id' => $lead?->branch_id ?? $contact?->branch_id ?? \App\Support\BranchContext::currentId(),
            'contact_id' => $lead?->contact_id ?? $contact?->id,
            'lead_id' => $lead?->id,
            'owner_user_id' => $ownerUserId ?? $lead?->owner_user_id,
            'activity_type' => $activityType,
            'source_suite' => $sourceSuite,
            'source_module' => $sourceModule,
            'title' => $title,
            'description' => $description,
            'payload' => $payload,
            'occurred_at' => now(),
        ]);
    }
}
