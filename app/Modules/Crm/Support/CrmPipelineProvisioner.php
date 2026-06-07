<?php

namespace App\Modules\Crm\Support;

use App\Modules\Crm\Models\CrmLead;
use App\Modules\Crm\Models\CrmPipeline;
use App\Modules\Crm\Models\CrmPipelineStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrmPipelineProvisioner
{
    public function ensureDefaultPipeline(int $tenantId, ?int $companyId = null, ?int $branchId = null): CrmPipeline
    {
        return DB::transaction(function () use ($tenantId, $companyId, $branchId): CrmPipeline {
            $pipeline = CrmPipeline::query()
                ->where('tenant_id', $tenantId)
                ->where('is_default', true)
                ->where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->first();

            if (!$pipeline) {
                $pipeline = CrmPipeline::query()->create([
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'name' => 'Pipeline Penjualan',
                    'code' => 'default-sales',
                    'is_default' => true,
                    'is_active' => true,
                    'meta' => [
                        'created_from' => 'crm_default_provisioner',
                    ],
                ]);
            }

            $existingCodes = $pipeline->stages()->pluck('code')->all();

            foreach ($this->defaultStages() as $stage) {
                if (in_array($stage['code'], $existingCodes, true)) {
                    continue;
                }

                CrmPipelineStage::query()->create([
                    'pipeline_id' => $pipeline->id,
                    'tenant_id' => $tenantId,
                    'name' => $stage['name'],
                    'code' => $stage['code'],
                    'position' => $stage['position'],
                    'probability_default' => $stage['probability_default'],
                    'stage_type' => $stage['stage_type'],
                    'color_token' => $stage['color_token'],
                ]);
            }

            $this->syncLeadsWithoutStage($tenantId, $pipeline);

            return $pipeline->fresh(['stages']);
        });
    }

    public function ensureLeadPlacement(CrmLead $lead): CrmLead
    {
        $pipeline = $this->ensureDefaultPipeline(
            (int) $lead->tenant_id,
            $lead->company_id ? (int) $lead->company_id : null,
            $lead->branch_id ? (int) $lead->branch_id : null
        );

        $stageCode = $lead->stage ?: CrmStageCatalog::NEW_LEAD;
        $stage = $pipeline->stages->firstWhere('code', $stageCode)
            ?: $pipeline->stages->sortBy('position')->first();

        $payload = [];

        if (!$lead->pipeline_id) {
            $payload['pipeline_id'] = $pipeline->id;
        }

        if (!$lead->stage_id && $stage) {
            $payload['stage_id'] = $stage->id;
            $payload['probability'] = $lead->probability ?? $stage->probability_default;
        }

        if ($payload !== []) {
            $lead->forceFill($payload)->save();
        }

        return $lead->fresh(['pipeline', 'stageModel']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function defaultStages(): array
    {
        return [
            ['code' => CrmStageCatalog::NEW_LEAD, 'name' => 'New Lead', 'position' => 1, 'probability_default' => 10, 'stage_type' => 'open', 'color_token' => 'secondary'],
            ['code' => CrmStageCatalog::QUALIFIED, 'name' => 'Qualified', 'position' => 2, 'probability_default' => 30, 'stage_type' => 'open', 'color_token' => 'azure'],
            ['code' => CrmStageCatalog::PROPOSAL, 'name' => 'Proposal', 'position' => 3, 'probability_default' => 60, 'stage_type' => 'open', 'color_token' => 'primary'],
            ['code' => CrmStageCatalog::NEGOTIATION, 'name' => 'Negotiation', 'position' => 4, 'probability_default' => 80, 'stage_type' => 'open', 'color_token' => 'orange'],
            ['code' => CrmStageCatalog::WON, 'name' => 'Won', 'position' => 5, 'probability_default' => 100, 'stage_type' => 'won', 'color_token' => 'green'],
            ['code' => CrmStageCatalog::LOST, 'name' => 'Lost', 'position' => 6, 'probability_default' => 0, 'stage_type' => 'lost', 'color_token' => 'red'],
        ];
    }

    private function syncLeadsWithoutStage(int $tenantId, CrmPipeline $pipeline): void
    {
        $stageMap = $pipeline->stages()->get()->keyBy('code');

        CrmLead::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($query): void {
                $query->whereNull('pipeline_id')
                    ->orWhereNull('stage_id');
            })
            ->chunkById(100, function ($leads) use ($pipeline, $stageMap): void {
                foreach ($leads as $lead) {
                    $stageCode = $lead->stage ?: CrmStageCatalog::NEW_LEAD;
                    $stage = $stageMap->get($stageCode) ?: $stageMap->first();

                    $lead->forceFill([
                        'pipeline_id' => $lead->pipeline_id ?: $pipeline->id,
                        'stage_id' => $stage?->id,
                        'probability' => $lead->probability ?? $stage?->probability_default,
                    ])->save();
                }
            });
    }
}
