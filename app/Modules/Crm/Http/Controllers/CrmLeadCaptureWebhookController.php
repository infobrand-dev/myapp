<?php

namespace App\Modules\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Crm\Support\MetaLeadAdsPayloadMapper;
use App\Modules\Crm\Support\CrmIntegrationService;
use App\Modules\Crm\Support\CrmLeadIngestionService;
use App\Services\Webhooks\WebhookReceiptService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmLeadCaptureWebhookController extends Controller
{
    public function __construct(
        private readonly CrmIntegrationService $integrations,
        private readonly CrmLeadIngestionService $ingestion,
        private readonly MetaLeadAdsPayloadMapper $metaMapper,
        private readonly WebhookReceiptService $receipts,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = TenantContext::currentTenant();
        abort_unless($tenant, 404);

        $receipt = $this->receipts->receive('crm', 'crm.webhooks.lead-capture', $request, $request->all());
        $token = (string) ($request->header('X-Lead-Capture-Token') ?: $request->input('token', ''));
        $valid = $this->integrations->tokenMatches($tenant, $token);
        $this->receipts->markSignature($receipt, $valid);

        if (!$valid) {
            return response()->json(['message' => 'Invalid lead capture token.'], 403);
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
            'lead_source' => ['nullable', 'string', 'max:100'],
            'provider' => ['nullable', 'string', 'max:100'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'next_follow_up_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'campaign_name' => ['nullable', 'string', 'max:255'],
            'adset_name' => ['nullable', 'string', 'max:255'],
            'form_name' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($data['title'])) {
            $data['title'] = 'Lead ' . ($data['name'] ?? $data['email'] ?? $data['mobile'] ?? now()->format('YmdHis'));
        }

        try {
            $lead = $this->ingestion->ingest($data, 'external', (string) ($data['provider'] ?? 'lead_capture_webhook'));
            $this->receipts->markProcessed($receipt, ['lead_id' => $lead->id, 'contact_id' => $lead->contact_id]);

            return response()->json([
                'ok' => true,
                'lead_id' => $lead->id,
            ], 201);
        } catch (\Throwable $e) {
            $this->receipts->markFailed($receipt, $e->getMessage());
            report($e);

            return response()->json(['message' => 'Lead capture failed.'], 422);
        }
    }

    public function metaLeadAds(Request $request): JsonResponse
    {
        $tenant = TenantContext::currentTenant();
        abort_unless($tenant, 404);

        $receipt = $this->receipts->receive('crm', 'crm.webhooks.meta-leads', $request, $request->all());
        $token = (string) ($request->header('X-Lead-Capture-Token') ?: $request->input('token', ''));
        $valid = $this->integrations->tokenMatches($tenant, $token);
        $this->receipts->markSignature($receipt, $valid);

        if (!$valid) {
            return response()->json(['message' => 'Invalid lead capture token.'], 403);
        }

        try {
            $data = $this->metaMapper->map($request);
            $lead = $this->ingestion->ingest($data, 'external', 'meta_lead_ads');
            $this->receipts->markProcessed($receipt, ['lead_id' => $lead->id, 'contact_id' => $lead->contact_id]);

            return response()->json([
                'ok' => true,
                'lead_id' => $lead->id,
                'external_reference' => $data['external_reference'] ?? null,
            ], 201);
        } catch (\Throwable $e) {
            $this->receipts->markFailed($receipt, $e->getMessage());
            report($e);

            return response()->json(['message' => 'Meta lead mapping failed.'], 422);
        }
    }
}
