<?php

namespace App\Modules\Crm\Support;

use App\Modules\Crm\Models\CrmFollowUpTask;
use App\Modules\Crm\Models\CrmLead;

class CrmFollowUpTaskManager
{
    public function syncPrimaryFollowUp(CrmLead $lead): ?CrmFollowUpTask
    {
        $task = CrmFollowUpTask::query()
            ->where('tenant_id', $lead->tenant_id)
            ->where('lead_id', $lead->id)
            ->where('meta->kind', 'lead_primary_follow_up')
            ->latest('id')
            ->first();

        if (!$lead->next_follow_up_at) {
            if ($task && $task->status === 'pending') {
                $task->update([
                    'status' => 'cancelled',
                    'meta' => array_merge((array) $task->meta, ['cancelled_from_lead' => true]),
                ]);
            }

            return $task;
        }

        $payload = [
            'tenant_id' => $lead->tenant_id,
            'company_id' => $lead->company_id,
            'branch_id' => $lead->branch_id,
            'contact_id' => $lead->contact_id,
            'lead_id' => $lead->id,
            'owner_user_id' => $lead->owner_user_id,
            'subject' => 'Follow up: ' . $lead->title,
            'description' => $lead->notes,
            'due_at' => $lead->next_follow_up_at,
            'status' => 'pending',
            'priority' => $lead->priority ?: 'medium',
            'sequence_no' => $task?->sequence_no ?: 1,
            'meta' => array_merge((array) ($task?->meta ?? []), [
                'kind' => 'lead_primary_follow_up',
            ]),
        ];

        if ($task) {
            $task->update($payload);

            return $task->fresh();
        }

        return CrmFollowUpTask::query()->create($payload);
    }

    public function complete(CrmFollowUpTask $task): CrmFollowUpTask
    {
        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $lead = $task->lead;
        if ($lead && $lead->next_follow_up_at && $task->due_at && $lead->next_follow_up_at->equalTo($task->due_at)) {
            $lead->forceFill(['next_follow_up_at' => null])->save();
        }

        return $task->fresh(['lead', 'contact', 'owner']);
    }
}
