<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppApi\Http\Requests\StoreWAFlowRequest;
use App\Modules\WhatsAppApi\Http\Requests\UpdateWAFlowRequest;
use App\Modules\WhatsAppApi\Models\WAFlow;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WAFlowController extends Controller
{
    private const FLOW_CATEGORIES = [
        'SIGN_UP',
        'SIGN_IN',
        'APPOINTMENT_BOOKING',
        'LEAD_GENERATION',
        'CONTACT_US',
        'CUSTOMER_SUPPORT',
        'SURVEY',
        'OTHER',
    ];

    public function index(): View
    {
        $flows = WAFlow::query()
            ->where('tenant_id', $this->tenantId())
            ->with('instance:id,name,provider')
            ->orderByDesc('updated_at')
            ->paginate(15);

        return view('whatsappapi::flows.index', compact('flows'));
    }

    public function create(): View
    {
        $flow = new WAFlow([
            'categories' => ['OTHER'],
            'status' => 'draft',
            'flow_json' => $this->defaultFlowJson(),
        ]);

        return view('whatsappapi::flows.form', [
            'flow' => $flow,
            'instances' => $this->cloudInstances(),
            'categories' => self::FLOW_CATEGORIES,
            'isEdit' => false,
        ]);
    }

    public function store(StoreWAFlowRequest $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['tenant_id'] = $this->tenantId();

        $flow = WAFlow::create($data);

        return redirect()
            ->route('whatsapp-api.flows.edit', $flow)
            ->with('status', 'Flow draft disimpan.');
    }

    public function edit(WAFlow $flow): View
    {
        $flow->load('instance');

        return view('whatsappapi::flows.form', [
            'flow' => $flow,
            'instances' => $this->cloudInstances(),
            'categories' => self::FLOW_CATEGORIES,
            'isEdit' => true,
        ]);
    }

    public function update(UpdateWAFlowRequest $request, WAFlow $flow): RedirectResponse
    {
        $data = $this->validated($request);

        $flow->update($data);

        return redirect()
            ->route('whatsapp-api.flows.edit', $flow)
            ->with('status', 'Flow draft diperbarui.');
    }

    public function destroy(WAFlow $flow): RedirectResponse
    {
        $flow->delete();

        return redirect()
            ->route('whatsapp-api.flows.index')
            ->with('status', 'Flow lokal dihapus.');
    }

    public function sync(WAFlow $flow): RedirectResponse
    {
        $flow->load('instance');
        $instance = $this->assertCloudInstanceReady($flow);
        $base = $this->graphBase();

        try {
            if (!$flow->meta_flow_id) {
                $createPayload = [
                    'name' => $flow->name,
                    'categories' => json_encode($flow->categories ?: ['OTHER'], JSON_UNESCAPED_SLASHES),
                ];

                if ($flow->endpoint_uri) {
                    $createPayload['endpoint_uri'] = $flow->endpoint_uri;
                }

                $createResponse = Http::withToken($instance->cloud_token)
                    ->asMultipart()
                    ->post("{$base}/{$instance->cloud_business_account_id}/flows", $this->multipartFields($createPayload));

                if (!$createResponse->successful()) {
                    throw ValidationException::withMessages([
                        'meta' => $this->metaError($createResponse, 'Gagal membuat flow di Meta.'),
                    ]);
                }

                $flow->meta_flow_id = (string) $createResponse->json('id');
                $flow->save();
            } else {
                $updatePayload = [
                    'name' => $flow->name,
                    'categories' => json_encode($flow->categories ?: ['OTHER'], JSON_UNESCAPED_SLASHES),
                    'endpoint_uri' => $flow->endpoint_uri ?: '',
                ];

                $updateResponse = Http::withToken($instance->cloud_token)
                    ->asMultipart()
                    ->post("{$base}/{$flow->meta_flow_id}", $this->multipartFields($updatePayload));

                if (!$updateResponse->successful()) {
                    throw ValidationException::withMessages([
                        'meta' => $this->metaError($updateResponse, 'Gagal update metadata flow di Meta.'),
                    ]);
                }
            }

            $assetResponse = Http::withToken($instance->cloud_token)
                ->attach('file', $flow->flow_json ?? '{}', 'flow.json')
                ->asMultipart()
                ->post("{$base}/{$flow->meta_flow_id}/assets", [
                    ['name' => 'name', 'contents' => 'flow.json'],
                    ['name' => 'asset_type', 'contents' => 'FLOW_JSON'],
                ]);

            if (!$assetResponse->successful()) {
                throw ValidationException::withMessages([
                    'flow_json' => $this->metaError($assetResponse, 'Gagal upload Flow JSON ke Meta.'),
                ]);
            }

            $flow->update([
                'validation_errors' => $assetResponse->json('validation_errors', []),
                'last_sync_error' => null,
            ]);

            $this->refreshFromMeta($flow, false);

            return redirect()
                ->route('whatsapp-api.flows.edit', $flow)
                ->with('status', 'Flow di-sync ke Meta.');
        } catch (ValidationException $e) {
            $flow->update(['last_sync_error' => collect($e->errors())->flatten()->implode(' ')]);
            throw $e;
        } catch (\Throwable $e) {
            $flow->update(['last_sync_error' => $e->getMessage()]);
            return redirect()
                ->route('whatsapp-api.flows.edit', $flow)
                ->with('status', 'Sync flow gagal. ' . $e->getMessage());
        }
    }

    public function refresh(WAFlow $flow): RedirectResponse
    {
        try {
            $this->refreshFromMeta($flow, true);

            return redirect()
                ->route('whatsapp-api.flows.edit', $flow)
                ->with('status', 'Data flow di-refresh dari Meta.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('whatsapp-api.flows.edit', $flow)
                ->with('status', 'Refresh flow gagal. ' . $e->getMessage());
        }
    }

    public function publish(WAFlow $flow): RedirectResponse
    {
        $flow->load('instance');
        $instance = $this->assertCloudInstanceReady($flow);
        $base = $this->graphBase();

        if (!$flow->meta_flow_id) {
            return redirect()
                ->route('whatsapp-api.flows.edit', $flow)
                ->with('status', 'Flow belum pernah di-sync ke Meta.');
        }

        try {
            $response = Http::withToken($instance->cloud_token)
                ->post("{$base}/{$flow->meta_flow_id}/publish");

            if (!$response->successful()) {
                return redirect()
                    ->route('whatsapp-api.flows.edit', $flow)
                    ->with('status', $this->metaError($response, 'Publish flow gagal.'));
            }

            $this->refreshFromMeta($flow, false);

            return redirect()
                ->route('whatsapp-api.flows.edit', $flow)
                ->with('status', 'Flow dipublish di Meta.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('whatsapp-api.flows.edit', $flow)
                ->with('status', 'Publish flow gagal. ' . $e->getMessage());
        }
    }

    private function refreshFromMeta(WAFlow $flow, bool $invalidatePreview): void
    {
        $flow->load('instance');
        $instance = $this->assertCloudInstanceReady($flow);

        if (!$flow->meta_flow_id) {
            throw new \RuntimeException('Flow belum memiliki Meta Flow ID.');
        }

        $fields = [
            'id',
            'name',
            'categories',
            'status',
            'validation_errors',
            'json_version',
            'data_api_version',
            'data_channel_uri',
            'health_status',
            'preview.invalidate(' . ($invalidatePreview ? 'true' : 'false') . ')',
        ];

        $response = Http::withToken($instance->cloud_token)
            ->get($this->graphBase() . '/' . $flow->meta_flow_id, [
                'fields' => implode(',', $fields),
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException($this->metaError($response, 'Gagal mengambil data flow dari Meta.'));
        }

        $flow->update([
            'name' => (string) ($response->json('name') ?: $flow->name),
            'categories' => $response->json('categories', $flow->categories ?? []),
            'endpoint_uri' => (string) ($response->json('data_channel_uri') ?: $flow->endpoint_uri),
            'status' => strtolower((string) ($response->json('status') ?: $flow->status)),
            'json_version' => $response->json('json_version'),
            'data_api_version' => $response->json('data_api_version'),
            'validation_errors' => $response->json('validation_errors', []),
            'health_status' => $response->json('health_status'),
            'preview_url' => $response->json('preview.preview_url'),
            'preview_expires_at' => $response->json('preview.expires_at'),
            'last_sync_error' => null,
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validated();

        $allowedCategories = self::FLOW_CATEGORIES;
        foreach ($data['categories'] as $category) {
            if (!in_array($category, $allowedCategories, true)) {
                throw ValidationException::withMessages([
                    'categories' => 'Kategori flow tidak valid.',
                ]);
            }
        }

        $json = trim((string) ($data['flow_json'] ?? ''));
        if ($json === '') {
            $json = $this->defaultFlowJson();
        }

        json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages([
                'flow_json' => 'Flow JSON tidak valid: ' . json_last_error_msg(),
            ]);
        }

        $data['flow_json'] = $json;
        $data['endpoint_uri'] = $data['endpoint_uri'] ?: null;
        $data['instance_id'] = WhatsAppInstance::query()
            ->where('tenant_id', $this->tenantId())
            ->where('provider', 'cloud')
            ->findOrFail((int) $data['instance_id'])
            ->id;

        return $data;
    }

    private function cloudInstances()
    {
        return WhatsAppInstance::query()
            ->where('tenant_id', $this->tenantId())
            ->where('provider', 'cloud')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function assertCloudInstanceReady(WAFlow $flow): WhatsAppInstance
    {
        $instance = $flow->instance;

        if (!$instance || strtolower((string) $instance->provider) !== 'cloud') {
            throw new \RuntimeException('Flow hanya didukung untuk instance provider Cloud.');
        }

        if (!$instance->cloud_business_account_id || !$instance->cloud_token) {
            throw new \RuntimeException('Instance belum memiliki Cloud Business Account ID atau Cloud Access Token.');
        }

        return $instance;
    }

    private function graphBase(): string
    {
        return rtrim((string) config('services.wa_cloud.base_url', 'https://graph.facebook.com/v22.0'), '/');
    }

    private function multipartFields(array $values): array
    {
        return collect($values)
            ->map(fn ($value, $name) => ['name' => $name, 'contents' => (string) $value])
            ->values()
            ->all();
    }

    private function metaError($response, string $fallback): string
    {
        return (string) ($response->json('error.message') ?: $response->body() ?: $fallback);
    }

    private function defaultFlowJson(): string
    {
        return json_encode([
            'version' => '7.1',
            'screens' => [
                [
                    'id' => 'WELCOME_SCREEN',
                    'title' => 'Lead Form',
                    'layout' => [
                        'type' => 'SingleColumnLayout',
                        'children' => [
                            [
                                'type' => 'Form',
                                'name' => 'lead_form',
                                'children' => [
                                    [
                                        'type' => 'TextHeading',
                                        'text' => 'Isi data Anda',
                                    ],
                                    [
                                        'type' => 'TextInput',
                                        'name' => 'full_name',
                                        'label' => 'Nama Lengkap',
                                        'required' => true,
                                    ],
                                    [
                                        'type' => 'TextInput',
                                        'name' => 'email',
                                        'label' => 'Email',
                                        'input-type' => 'email',
                                        'required' => true,
                                    ],
                                    [
                                        'type' => 'Footer',
                                        'label' => 'Kirim',
                                        'on-click-action' => [
                                            'name' => 'complete',
                                            'payload' => [
                                                'full_name' => '${form.full_name}',
                                                'email' => '${form.email}',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function tenantId(): int
    {
        return TenantContext::currentId();
    }
}
