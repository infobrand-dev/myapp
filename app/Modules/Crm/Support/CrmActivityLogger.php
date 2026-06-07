<?php

namespace App\Modules\Crm\Support;

use App\Modules\Crm\Models\CrmActivity;
use App\Modules\Crm\Models\CrmLead;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CrmActivityLogger
{
    public function logLeadCreated(CrmLead $lead): void
    {
        $this->createActivity($lead, [
            'activity_type' => 'lead_created',
            'title' => 'Lead dibuat',
            'description' => $lead->title,
            'payload' => [
                'stage' => $lead->stage,
                'lead_source' => $lead->lead_source,
                'estimated_value' => $lead->estimated_value,
            ],
        ]);

        if ($lead->next_follow_up_at) {
            $this->logFollowUpScheduled($lead, null, $lead->next_follow_up_at);
        }
    }

    public function logLeadUpdated(CrmLead $lead, array $before): void
    {
        $changes = $this->detectChanges($lead, $before);
        if ($changes === []) {
            return;
        }

        if (Arr::has($changes, 'stage')) {
            $this->logStageChanged($lead, $before['stage'] ?? null, $lead->stage);
            unset($changes['stage']);
        }

        if (Arr::has($changes, 'next_follow_up_at')) {
            $this->logFollowUpScheduled(
                $lead,
                $before['next_follow_up_at'] ?? null,
                $lead->next_follow_up_at
            );
            unset($changes['next_follow_up_at']);
        }

        if ($changes === []) {
            return;
        }

        $labels = collect($changes)
            ->map(fn (array $change, string $field) => $this->changeLabel($field, $change))
            ->values()
            ->all();

        $this->createActivity($lead, [
            'activity_type' => 'lead_updated',
            'title' => 'Lead diperbarui',
            'description' => implode(', ', $labels),
            'payload' => [
                'changes' => $changes,
            ],
        ]);
    }

    public function logStageChanged(CrmLead $lead, ?string $fromStage, string $toStage): void
    {
        $fromLabel = $fromStage ? (CrmStageCatalog::options()[$fromStage] ?? Str::headline($fromStage)) : 'Baru';
        $toLabel = CrmStageCatalog::options()[$toStage] ?? Str::headline($toStage);

        $this->createActivity($lead, [
            'activity_type' => 'deal_stage_changed',
            'title' => 'Stage berubah',
            'description' => $fromLabel . ' -> ' . $toLabel,
            'payload' => [
                'from_stage' => $fromStage,
                'to_stage' => $toStage,
            ],
        ]);
    }

    public function logFollowUpScheduled(CrmLead $lead, $from, $to): void
    {
        $fromAt = $this->normalizeDateTime($from);
        $toAt = $this->normalizeDateTime($to);

        $description = $toAt
            ? 'Follow-up ' . ($fromAt ? 'diubah ke ' : 'dijadwalkan untuk ') . $toAt->translatedFormat('d M Y H:i')
            : 'Jadwal follow-up dihapus';

        $this->createActivity($lead, [
            'activity_type' => $toAt ? 'task_created' : 'follow_up_cleared',
            'title' => $toAt ? 'Follow-up dijadwalkan' : 'Follow-up dihapus',
            'description' => $description,
            'payload' => [
                'from' => $fromAt ? $fromAt->toAtomString() : null,
                'to' => $toAt ? $toAt->toAtomString() : null,
            ],
        ]);
    }

    private function createActivity(CrmLead $lead, array $payload): void
    {
        CrmActivity::query()->create([
            'tenant_id' => $lead->tenant_id,
            'company_id' => $lead->company_id,
            'branch_id' => $lead->branch_id,
            'contact_id' => $lead->contact_id,
            'lead_id' => $lead->id,
            'owner_user_id' => $lead->owner_user_id,
            'activity_type' => $payload['activity_type'],
            'source_suite' => $payload['source_suite'] ?? 'crm',
            'source_module' => $payload['source_module'] ?? 'crm',
            'title' => $payload['title'],
            'description' => $payload['description'] ?? null,
            'payload' => $payload['payload'] ?? null,
            'occurred_at' => $payload['occurred_at'] ?? now(),
        ]);
    }

    private function detectChanges(CrmLead $lead, array $before): array
    {
        $fields = [
            'title',
            'owner_user_id',
            'stage',
            'priority',
            'lead_source',
            'qualification_status',
            'lead_score',
            'estimated_value',
            'probability',
            'expected_close_date',
            'next_follow_up_at',
            'last_contacted_at',
            'is_archived',
        ];

        $changes = [];
        foreach ($fields as $field) {
            $beforeValue = $before[$field] ?? null;
            $afterValue = $lead->{$field};

            if ($this->valuesMatch($beforeValue, $afterValue)) {
                continue;
            }

            $changes[$field] = [
                'from' => $this->serializeValue($beforeValue),
                'to' => $this->serializeValue($afterValue),
            ];
        }

        return $changes;
    }

    private function valuesMatch($before, $after): bool
    {
        return $this->serializeValue($before) === $this->serializeValue($after);
    }

    private function serializeValue($value)
    {
        if ($value instanceof Carbon) {
            return $value->toAtomString();
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toAtomString();
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value) && !is_string($value)) {
            return (string) $value;
        }

        return $value;
    }

    private function normalizeDateTime($value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value);
        }

        return null;
    }

    private function changeLabel(string $field, array $change): string
    {
        switch ($field) {
            case 'owner_user_id':
                $label = 'owner';
                break;
            case 'lead_source':
                $label = 'source';
                break;
            case 'qualification_status':
                $label = 'qualification';
                break;
            case 'lead_score':
                $label = 'score';
                break;
            case 'estimated_value':
                $label = 'nilai';
                break;
            case 'probability':
                $label = 'probability';
                break;
            case 'expected_close_date':
                $label = 'target closing';
                break;
            case 'last_contacted_at':
                $label = 'last contact';
                break;
            case 'is_archived':
                $label = 'status arsip';
                break;
            default:
                $label = Str::headline($field);
                break;
        }

        return $label . ' diperbarui';
    }
}
