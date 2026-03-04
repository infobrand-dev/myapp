<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppApi\Models\WATemplate;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Modules\WhatsAppApi\Models\WhatsAppInstanceChatbotIntegration;
use Illuminate\Support\Arr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Modules\WhatsAppApi\ViewModels\InstanceHealthViewModel;

class InstanceController extends Controller
{
    public function index(): View
    {
        $instances = WhatsAppInstance::orderByDesc('created_at')->paginate(15);
        $summary = InstanceHealthViewModel::summary();

        return view('whatsappapi::instances.index', compact('instances', 'summary'));
    }

    public function create(): View
    {
        $instance = new WhatsAppInstance([
            'status' => 'disconnected',
            'is_active' => true,
            'provider' => 'cloud',
            'settings' => [
                'wa_cloud_verify_token' => $this->generateVerifyToken(),
            ],
        ]);
        $chatbotAccounts = $this->chatbotAccounts();
        $integration = null;
        $chatbotEnabled = $this->isChatbotModuleReady();

        return view('whatsappapi::instances.form', compact('instance', 'chatbotAccounts', 'integration', 'chatbotEnabled'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);
        $data['is_active'] = $request->boolean('is_active');
        $data['created_by'] = $request->user() ? $request->user()->id : null;
        $data['updated_by'] = $request->user() ? $request->user()->id : null;

        $instance = WhatsAppInstance::create($data);
        $this->persistChatbotIntegration($request, $instance);

        return redirect()->route('whatsapp-api.instances.index')->with('status', 'Instance dibuat.');
    }

    public function edit(WhatsAppInstance $instance): View
    {
        $chatbotAccounts = $this->chatbotAccounts();
        $integration = $instance->chatbotIntegration()->first();
        $chatbotEnabled = $this->isChatbotModuleReady();
        return view('whatsappapi::instances.form', compact('instance', 'chatbotAccounts', 'integration', 'chatbotEnabled'));
    }

    public function update(Request $request, WhatsAppInstance $instance): RedirectResponse
    {
        $data = $this->validated($request, $instance);
        $data['is_active'] = $request->boolean('is_active');
        $data['updated_by'] = $request->user() ? $request->user()->id : null;

        $instance->update($data);
        $this->persistChatbotIntegration($request, $instance);

        return redirect()->route('whatsapp-api.instances.index')->with('status', 'Instance diperbarui.');
    }

    public function saveAndTest(Request $request, WhatsAppInstance $instance): RedirectResponse
    {
        $data = $this->validated($request, $instance);
        $data['is_active'] = $request->boolean('is_active');
        $data['updated_by'] = $request->user() ? $request->user()->id : null;

        $instance->update($data);
        $this->persistChatbotIntegration($request, $instance);
        $instance->refresh();

        $result = $this->runStoredCloudCredentialTest($instance);

        if ($result['ok']) {
            return redirect()
                ->route('whatsapp-api.instances.edit', $instance)
                ->with('status', $result['message'])
                ->with('credentials_test_ok', true)
                ->with('credentials_test_steps', $result['steps'] ?? []);
        }

        return redirect()
            ->route('whatsapp-api.instances.edit', $instance)
            ->with('status', ($result['message'] ?? 'Gagal test credentials.') . (!empty($result['error']) ? ' ' . $result['error'] : ''))
            ->with('credentials_test_ok', false)
            ->with('credentials_test_steps', $result['steps'] ?? []);
    }

    public function saveAndSyncTemplates(Request $request, WhatsAppInstance $instance): RedirectResponse
    {
        $data = $this->validated($request, $instance);
        $data['is_active'] = $request->boolean('is_active');
        $data['updated_by'] = $request->user() ? $request->user()->id : null;

        $instance->update($data);
        $this->persistChatbotIntegration($request, $instance);
        $instance->refresh();

        $result = $this->runStoredCloudTemplateSync($instance);
        $message = $result['message'] ?? 'Gagal sync templates.';
        if (!empty($result['error'])) {
            $message .= ' ' . $result['error'];
        } elseif (!empty($result['data']) && is_array($result['data'])) {
            $fetched = (int) ($result['data']['fetched'] ?? 0);
            $created = (int) ($result['data']['created'] ?? 0);
            $updated = (int) ($result['data']['updated'] ?? 0);
            $message .= " Fetched: {$fetched}, Created: {$created}, Updated: {$updated}.";
        }

        return redirect()
            ->route('whatsapp-api.instances.edit', $instance)
            ->with('status', $message);
    }

    public function destroy(WhatsAppInstance $instance): RedirectResponse
    {
        if ($instance->conversations()->exists()) {
            return back()->with('status', 'Tidak bisa menghapus: masih ada percakapan.');
        }

        $instance->delete();

        return redirect()->route('whatsapp-api.instances.index')->with('status', 'Instance dihapus.');
    }

    public function testCredentials(Request $request): JsonResponse
    {
        [$provider, $phoneNumberId, $businessId, $cloudToken, $verifyToken, $appSecret] = $this->resolveCloudCredentials($request);

        if ($provider !== 'cloud') {
            return response()->json([
                'ok' => false,
                'message' => 'Test credentials hanya tersedia untuk provider Cloud API.',
            ], 422);
        }

        $missing = [];
        if ($phoneNumberId === '') {
            $missing[] = 'Phone Number ID';
        }
        if ($businessId === '') {
            $missing[] = 'Cloud Business Account ID';
        }
        if ($cloudToken === '') {
            $missing[] = 'Cloud Access Token';
        }
        if ($missing) {
            return response()->json([
                'ok' => false,
                'message' => 'Lengkapi field wajib terlebih dahulu: ' . implode(', ', $missing) . '.',
            ], 422);
        }

        $result = $this->executeCloudCredentialTest($phoneNumberId, $businessId, $cloudToken);
        if ($result['ok']) {
            return response()->json($result);
        }

        return response()->json($result, $result['status'] ?? 422);
    }

    private function runStoredCloudCredentialTest(WhatsAppInstance $instance): array
    {
        $provider = strtolower((string) ($instance->provider ?? ''));
        if ($provider !== 'cloud') {
            return [
                'ok' => false,
                'message' => 'Test credentials hanya tersedia untuk provider Cloud API.',
                'status' => 422,
            ];
        }

        $phoneNumberId = trim((string) $instance->phone_number_id);
        $businessId = trim((string) $instance->cloud_business_account_id);
        $cloudToken = trim((string) $instance->cloud_token);
        $missing = [];
        if ($phoneNumberId === '') {
            $missing[] = 'Phone Number ID';
        }
        if ($businessId === '') {
            $missing[] = 'Cloud Business Account ID';
        }
        if ($cloudToken === '') {
            $missing[] = 'Cloud Access Token';
        }
        if ($missing) {
            return [
                'ok' => false,
                'message' => 'Lengkapi field wajib terlebih dahulu: ' . implode(', ', $missing) . '.',
                'status' => 422,
            ];
        }

        return $this->executeCloudCredentialTest($phoneNumberId, $businessId, $cloudToken);
    }

    private function executeCloudCredentialTest(string $phoneNumberId, string $businessId, string $cloudToken): array
    {
        $base = rtrim((string) config('services.wa_cloud.base_url', 'https://graph.facebook.com/v22.0'), '/');
        $steps = [];

        // Step 1: token validity
        try {
            $meResponse = Http::timeout(15)
                ->withToken($cloudToken)
                ->get("{$base}/me", ['fields' => 'id,name']);

            if (!$meResponse->successful()) {
                $errorMessage = (string) ($meResponse->json('error.message') ?: $meResponse->body() ?: 'Unknown error');
                $steps[] = [
                    'step' => 'Token',
                    'ok' => false,
                    'message' => 'Cloud Access Token tidak valid atau tidak punya izin.',
                    'error' => $errorMessage,
                ];
                return [
                    'ok' => false,
                    'message' => 'Gagal validasi credentials.',
                    'error' => $errorMessage,
                    'steps' => $steps,
                    'status' => 422,
                ];
            }

            $steps[] = [
                'step' => 'Token',
                'ok' => true,
                'message' => 'Cloud Access Token valid.',
                'data' => [
                    'id' => $meResponse->json('id'),
                    'name' => $meResponse->json('name'),
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Gagal validasi credentials.',
                'error' => $e->getMessage(),
                'steps' => [
                    [
                        'step' => 'Token',
                        'ok' => false,
                        'message' => 'Cloud Access Token tidak bisa divalidasi.',
                        'error' => $e->getMessage(),
                    ],
                ],
                'status' => 500,
            ];
        }

        try {
            // Step 2: Phone Number ID check
            $phoneResponse = Http::timeout(15)
                ->withToken($cloudToken)
                ->get("{$base}/{$phoneNumberId}", ['fields' => 'id,display_phone_number,verified_name']);

            if (!$phoneResponse->successful()) {
                $errorMessage = (string) ($phoneResponse->json('error.message') ?: $phoneResponse->body() ?: 'Unknown error');
                $steps[] = [
                    'step' => 'Phone Number ID',
                    'ok' => false,
                    'message' => 'Phone Number ID tidak valid / tidak bisa diakses token.',
                    'error' => $errorMessage,
                ];
                return [
                    'ok' => false,
                    'message' => 'Gagal validasi credentials.',
                    'error' => $errorMessage,
                    'steps' => $steps,
                    'status' => 422,
                ];
            }

            $steps[] = [
                'step' => 'Phone Number ID',
                'ok' => true,
                'message' => 'Phone Number ID valid.',
                'data' => [
                    'id' => $phoneResponse->json('id'),
                    'display_phone_number' => $phoneResponse->json('display_phone_number'),
                    'verified_name' => $phoneResponse->json('verified_name'),
                ],
            ];

            // Step 3: WABA ID check
            $wabaResponse = Http::timeout(15)
                ->withToken($cloudToken)
                ->get("{$base}/{$businessId}", ['fields' => 'id,name']);

            if (!$wabaResponse->successful()) {
                $errorMessage = (string) ($wabaResponse->json('error.message') ?: $wabaResponse->body() ?: 'Unknown error');
                $steps[] = [
                    'step' => 'Cloud Business Account ID',
                    'ok' => false,
                    'message' => 'Cloud Business Account ID tidak valid / tidak bisa diakses token.',
                    'error' => $errorMessage,
                ];
                return [
                    'ok' => false,
                    'message' => 'Gagal validasi credentials.',
                    'error' => $errorMessage,
                    'steps' => $steps,
                    'status' => 422,
                ];
            }

            $steps[] = [
                'step' => 'Cloud Business Account ID',
                'ok' => true,
                'message' => 'Cloud Business Account ID valid.',
                'data' => [
                    'id' => $wabaResponse->json('id'),
                    'name' => $wabaResponse->json('name'),
                ],
            ];

            // Step 4: Relationship check (phone belongs to WABA)
            $relationResponse = Http::timeout(15)
                ->withToken($cloudToken)
                ->get("{$base}/{$businessId}/phone_numbers", ['fields' => 'id,display_phone_number,verified_name', 'limit' => 200]);

            if (!$relationResponse->successful()) {
                $errorMessage = (string) ($relationResponse->json('error.message') ?: $relationResponse->body() ?: 'Unknown error');
                $steps[] = [
                    'step' => 'Relasi WABA -> Phone Number',
                    'ok' => false,
                    'message' => 'Tidak bisa memverifikasi relasi Phone Number ke WABA.',
                    'error' => $errorMessage,
                ];
                return [
                    'ok' => false,
                    'message' => 'Gagal validasi credentials.',
                    'error' => $errorMessage,
                    'steps' => $steps,
                    'status' => 422,
                ];
            }

            $phoneIds = collect((array) $relationResponse->json('data', []))
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all();

            if (!in_array((string) $phoneNumberId, $phoneIds, true)) {
                $steps[] = [
                    'step' => 'Relasi WABA -> Phone Number',
                    'ok' => false,
                    'message' => 'Phone Number ID tidak terdaftar di Cloud Business Account ID ini.',
                    'error' => 'Pastikan Phone Number ID dan WABA ID berasal dari akun yang sama.',
                ];

                return [
                    'ok' => false,
                    'message' => 'Gagal validasi credentials.',
                    'error' => 'Phone Number ID tidak terhubung dengan WABA ID.',
                    'steps' => $steps,
                    'status' => 422,
                ];
            }

            $steps[] = [
                'step' => 'Relasi WABA -> Phone Number',
                'ok' => true,
                'message' => 'Phone Number ID terhubung dengan WABA ID.',
            ];

            return [
                'ok' => true,
                'message' => 'Semua credential Cloud API valid.',
                'steps' => $steps,
                'data' => [
                    'phone_number_id' => $phoneNumberId,
                    'cloud_business_account_id' => $businessId,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Gagal validasi credentials.',
                'error' => $e->getMessage(),
                'steps' => $steps,
                'status' => 500,
            ];
        }
    }

    private function runStoredCloudTemplateSync(WhatsAppInstance $instance): array
    {
        $provider = strtolower((string) ($instance->provider ?? ''));
        if ($provider !== 'cloud') {
            return [
                'ok' => false,
                'message' => 'Sync templates hanya tersedia untuk provider Cloud API.',
                'status' => 422,
            ];
        }

        $businessId = trim((string) $instance->cloud_business_account_id);
        $cloudToken = trim((string) $instance->cloud_token);
        $missing = [];
        if ($businessId === '') {
            $missing[] = 'Cloud Business Account ID';
        }
        if ($cloudToken === '') {
            $missing[] = 'Cloud Access Token';
        }
        if ($missing) {
            return [
                'ok' => false,
                'message' => 'Lengkapi field wajib terlebih dahulu: ' . implode(', ', $missing) . '.',
                'status' => 422,
            ];
        }

        return $this->executeCloudTemplateSync($businessId, $cloudToken);
    }

    private function executeCloudTemplateSync(string $businessId, string $cloudToken): array
    {
        $base = rtrim((string) config('services.wa_cloud.base_url', 'https://graph.facebook.com/v22.0'), '/');
        $url = "{$base}/{$businessId}/message_templates";

        $created = 0;
        $updated = 0;
        $fetched = 0;
        $nextAfter = null;
        $loops = 0;

        try {
            do {
                $query = [
                    'limit' => 100,
                    'fields' => 'id,name,status,category,language,components',
                ];
                if ($nextAfter) {
                    $query['after'] = $nextAfter;
                }

                $response = Http::timeout(20)
                    ->withToken($cloudToken)
                    ->get($url, $query);

                if (!$response->successful()) {
                    $errorMessage = (string) ($response->json('error.message') ?: $response->body() ?: 'Unknown error');
                    return [
                        'ok' => false,
                        'message' => 'Gagal sync template dari WhatsApp Cloud API.',
                        'error' => $errorMessage,
                        'status' => 422,
                    ];
                }

                $items = (array) $response->json('data', []);
                $fetched += count($items);

                foreach ($items as $item) {
                    $metaId = trim((string) Arr::get($item, 'id', ''));
                    $name = trim((string) Arr::get($item, 'name', ''));
                    $language = trim((string) Arr::get($item, 'language', 'en'));
                    $category = strtolower((string) Arr::get($item, 'category', 'utility'));
                    $rawStatus = strtolower((string) Arr::get($item, 'status', ''));
                    $components = Arr::get($item, 'components');
                    $bodyText = '';
                    foreach ((array) $components as $component) {
                        if (strtolower((string) Arr::get($component, 'type', '')) === 'body') {
                            $bodyText = (string) Arr::get($component, 'text', '');
                            break;
                        }
                    }

                    $status = match ($rawStatus) {
                        'approved', 'active' => 'active',
                        'pending', 'in_appeal', 'paused' => 'pending',
                        default => 'inactive',
                    };

                    if ($metaId !== '') {
                        $model = WATemplate::firstOrNew(['meta_template_id' => $metaId]);
                    } else {
                        $model = WATemplate::firstOrNew([
                            'name' => $name,
                            'language' => $language,
                            'namespace' => $businessId,
                        ]);
                    }

                    $isNew = !$model->exists;
                    $model->fill([
                        'name' => $name ?: ($model->name ?: 'unnamed_template'),
                        'language' => $language ?: 'en',
                        'category' => $category ?: 'utility',
                        'namespace' => $businessId,
                        'meta_template_id' => $metaId !== '' ? $metaId : $model->meta_template_id,
                        'body' => $bodyText !== '' ? $bodyText : ($model->body ?: '-'),
                        'components' => is_array($components) ? $components : null,
                        'status' => $status,
                        'last_submit_error' => null,
                    ]);
                    $model->save();

                    if ($isNew) {
                        $created++;
                    } else {
                        $updated++;
                    }
                }

                $nextAfter = $response->json('paging.cursors.after');
                $loops++;
            } while ($nextAfter && $loops < 10);

            return [
                'ok' => true,
                'message' => 'Sync templates berhasil.',
                'data' => [
                    'fetched' => $fetched,
                    'created' => $created,
                    'updated' => $updated,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Gagal sync template dari WhatsApp Cloud API.',
                'error' => $e->getMessage(),
                'status' => 500,
            ];
        }
    }

    public function syncTemplates(Request $request): JsonResponse
    {
        [$provider, $phoneNumberId, $businessId, $cloudToken, $verifyToken, $appSecret] = $this->resolveCloudCredentials($request);

        if ($provider !== 'cloud') {
            return response()->json([
                'ok' => false,
                'message' => 'Sync templates hanya tersedia untuk provider Cloud API.',
            ], 422);
        }

        $missing = [];
        if ($businessId === '') {
            $missing[] = 'Cloud Business Account ID';
        }
        if ($cloudToken === '') {
            $missing[] = 'Cloud Access Token';
        }
        if ($missing) {
            return response()->json([
                'ok' => false,
                'message' => 'Lengkapi field wajib terlebih dahulu: ' . implode(', ', $missing) . '.',
            ], 422);
        }

        $result = $this->executeCloudTemplateSync($businessId, $cloudToken);
        if ($result['ok']) {
            return response()->json($result);
        }

        return response()->json($result, $result['status'] ?? 422);
    }

    private function validated(Request $request, ?WhatsAppInstance $current): array
    {
        $chatbotRule = ['nullable'];
        if ($this->isChatbotModuleReady()) {
            $chatbotRule[] = 'exists:chatbot_accounts,id';
        } else {
            $chatbotRule[] = 'integer';
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'provider' => ['required', 'string', 'max:50'],
            'api_base_url' => ['nullable', 'url', 'max:255'],
            'api_token' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'settings' => ['nullable'],
            'wa_cloud_verify_token' => ['nullable', 'string', 'max:255'],
            'wa_cloud_app_secret' => ['nullable', 'string', 'max:255'],
            'auto_reply' => ['sometimes', 'boolean'],
            'chatbot_account_id' => $chatbotRule,
            'phone_number_id' => ['nullable', 'string', 'max:100'],
            'cloud_business_account_id' => ['nullable', 'string', 'max:100'],
            'cloud_token' => ['nullable', 'string'],
        ]);

        $existingSettings = is_array($current?->settings) ? $current->settings : [];
        $settings = $existingSettings;
        if (isset($data['settings']) && is_string($data['settings']) && trim($data['settings']) !== '') {
            $decoded = json_decode($data['settings'], true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                throw ValidationException::withMessages([
                    'settings' => 'JSON settings tidak valid.',
                ]);
            }
            $settings = $decoded;
        }
        if ($request->filled('wa_cloud_verify_token')) {
            $settings['wa_cloud_verify_token'] = trim((string) $request->input('wa_cloud_verify_token'));
        }
        if ($request->filled('wa_cloud_app_secret')) {
            $settings['wa_cloud_app_secret'] = trim((string) $request->input('wa_cloud_app_secret'));
        }
        $data['settings'] = $settings ?: null;
        unset($data['wa_cloud_verify_token'], $data['wa_cloud_app_secret']);

        $provider = strtolower((string) ($data['provider'] ?? ''));
        $isCloud = $provider === 'cloud';

        if (!$request->filled('api_token') && $current) {
            unset($data['api_token']);
        }
        if (!$request->filled('cloud_token') && $current) {
            unset($data['cloud_token']);
        }

        // Stored in dedicated integration table.
        unset($data['auto_reply'], $data['chatbot_account_id']);

        $effectiveApiBase = trim((string) ($data['api_base_url'] ?? $current?->api_base_url));
        $effectiveApiToken = trim((string) ($data['api_token'] ?? $current?->api_token));
        $effectivePhoneId = trim((string) ($data['phone_number_id'] ?? $current?->phone_number_id));
        $effectiveWaba = trim((string) ($data['cloud_business_account_id'] ?? $current?->cloud_business_account_id));
        $effectiveCloudToken = trim((string) ($data['cloud_token'] ?? $current?->cloud_token));
        $effectiveVerifyToken = trim((string) Arr::get($settings, 'wa_cloud_verify_token', ''));
        $effectiveAppSecret = trim((string) Arr::get($settings, 'wa_cloud_app_secret', ''));

        if ($isCloud && $effectiveVerifyToken === '') {
            $effectiveVerifyToken = $this->generateVerifyToken();
            $settings['wa_cloud_verify_token'] = $effectiveVerifyToken;
            $data['settings'] = $settings;
        }

        if ($isCloud) {
            $errors = [];
            if ($effectivePhoneId === '') {
                $errors['phone_number_id'] = 'Phone Number ID wajib diisi untuk provider cloud.';
            }
            if ($effectiveWaba === '') {
                $errors['cloud_business_account_id'] = 'Cloud Business Account ID wajib diisi untuk provider cloud.';
            }
            if ($effectiveCloudToken === '') {
                $errors['cloud_token'] = 'Cloud Access Token wajib diisi untuk provider cloud.';
            }
            if ($effectiveAppSecret === '') {
                $errors['wa_cloud_app_secret'] = 'App Secret wajib diisi untuk validasi signature webhook Cloud.';
            }
            if ($errors) {
                throw ValidationException::withMessages($errors);
            }
        } else {
            $errors = [];
            if ($effectiveApiBase === '') {
                $errors['api_base_url'] = 'API Base URL wajib diisi untuk provider non-cloud.';
            }
            if ($effectiveApiToken === '') {
                $errors['api_token'] = 'API Token wajib diisi untuk provider non-cloud.';
            }
            if ($errors) {
                throw ValidationException::withMessages($errors);
            }
        }

        return $data;
    }

    private function persistChatbotIntegration(Request $request, WhatsAppInstance $instance): void
    {
        if (!Schema::hasTable('whatsapp_instance_chatbot_integrations')) {
            return;
        }

        $autoReply = $request->boolean('auto_reply');
        $chatbotAccountId = $request->filled('chatbot_account_id')
            ? (int) $request->input('chatbot_account_id')
            : null;

        if (!$this->isChatbotModuleReady()) {
            $chatbotAccountId = null;
            $autoReply = false;
        }

        if (!$autoReply && !$chatbotAccountId) {
            WhatsAppInstanceChatbotIntegration::query()
                ->where('instance_id', $instance->id)
                ->delete();
            return;
        }

        WhatsAppInstanceChatbotIntegration::query()->updateOrCreate(
            ['instance_id' => $instance->id],
            [
                'auto_reply' => $autoReply,
                'chatbot_account_id' => $chatbotAccountId,
            ]
        );
    }

    private function isChatbotModuleReady(): bool
    {
        return class_exists(\App\Modules\Chatbot\Models\ChatbotAccount::class)
            && Schema::hasTable('chatbot_accounts');
    }

    private function chatbotAccounts()
    {
        if (!$this->isChatbotModuleReady()) {
            return collect();
        }

        $chatbotClass = \App\Modules\Chatbot\Models\ChatbotAccount::class;
        return $chatbotClass::query()->where('status', 'active')->orderBy('name')->get();
    }

    private function resolveCloudCredentials(Request $request): array
    {
        $editingInstance = null;
        $instanceId = (int) $request->input('instance_id', 0);
        if ($instanceId > 0) {
            $editingInstance = WhatsAppInstance::find($instanceId);
        }

        $provider = strtolower((string) $request->input('provider', 'cloud'));
        if ($provider === '' && $editingInstance) {
            $provider = strtolower((string) $editingInstance->provider);
        }

        $settingsInput = $request->input('settings');
        $settings = [];
        if (is_string($settingsInput) && trim($settingsInput) !== '') {
            $decoded = json_decode($settingsInput, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $settings = $decoded;
            }
        } elseif (is_array($settingsInput)) {
            $settings = $settingsInput;
        }

        $phoneNumberId = trim((string) $request->input('phone_number_id', $editingInstance?->phone_number_id));
        $businessId = trim((string) $request->input('cloud_business_account_id', $editingInstance?->cloud_business_account_id));
        $cloudToken = trim((string) $request->input('cloud_token', ''));
        $verifyToken = trim((string) $request->input('wa_cloud_verify_token', Arr::get($settings, 'wa_cloud_verify_token', '')));
        $appSecret = trim((string) $request->input('wa_cloud_app_secret', Arr::get($settings, 'wa_cloud_app_secret', '')));

        if ($phoneNumberId === '' && $editingInstance) {
            $phoneNumberId = trim((string) $editingInstance->phone_number_id);
        }
        if ($businessId === '' && $editingInstance) {
            $businessId = trim((string) $editingInstance->cloud_business_account_id);
        }
        if ($cloudToken === '' && $editingInstance) {
            $cloudToken = trim((string) $editingInstance->cloud_token);
        }
        if ($verifyToken === '') {
            $verifyToken = trim((string) Arr::get($editingInstance?->settings ?? [], 'wa_cloud_verify_token', ''));
        }
        if ($appSecret === '') {
            $appSecret = trim((string) Arr::get($editingInstance?->settings ?? [], 'wa_cloud_app_secret', ''));
        }

        return [$provider, $phoneNumberId, $businessId, $cloudToken, $verifyToken, $appSecret];
    }

    private function generateVerifyToken(): string
    {
        return 'wa_verify_' . bin2hex(random_bytes(12));
    }
}
