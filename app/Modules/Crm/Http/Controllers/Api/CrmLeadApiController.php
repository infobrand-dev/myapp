<?php

namespace App\Modules\Crm\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Support\CrmLeadIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmLeadApiController extends Controller
{
    public function __construct(
        private readonly CrmLeadIngestionService $ingestion,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'lead_source' => ['nullable', 'string', 'max:100'],
            'provider' => ['nullable', 'string', 'max:100'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'next_follow_up_at' => ['nullable', 'date'],
            'expected_close_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'labels' => ['nullable', 'array'],
            'labels.*' => ['string', 'max:100'],
            'campaign_name' => ['nullable', 'string', 'max:255'],
            'adset_name' => ['nullable', 'string', 'max:255'],
            'form_name' => ['nullable', 'string', 'max:255'],
        ]);

        $lead = $this->ingestion->ingest($data, 'api', 'crm_api');

        return response()->json([
            'ok' => true,
            'lead_id' => $lead->id,
            'contact_id' => $lead->contact_id,
            'stage' => $lead->stage,
        ], 201);
    }
}
