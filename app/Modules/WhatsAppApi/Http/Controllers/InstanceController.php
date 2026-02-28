<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppApi\Models\WATemplate;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Modules\Chatbot\Models\ChatbotAccount;
use Illuminate\Support\Arr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
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
        $chatbotAccounts = ChatbotAccount::where('status', 'active')->orderBy('name')->get();

        return view('whatsappapi::instances.form', compact('instance', 'chatbotAccounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);
        $data['is_active'] = $request->boolean('is_active');
        $data['auto_reply'] = $request->boolean('auto_reply');
        $data['created_by'] = $request->user() ? $request->user()->id : null;
        $data['updated_by'] = $request->user() ? $request->user()->id : null;

        WhatsAppInstance::create($data);

        return redirect()->route('whatsapp-api.instances.index')->with('status', 'Instance dibuat.');
    }

    public function edit(WhatsAppInstance $instance): View
    {
        $chatbotAccounts = ChatbotAccount::where('status', 'active')->orderBy('name')->get();
        return view('whatsappapi::instances.form', compact('instance', 'chatbotAccounts'));
    }

    public function update(Request $request, WhatsAppInstance $instance): RedirectResponse
    {
        $data = $this->validated($request, $instance);
        $data['is_active'] = $request->boolean('is_active');
        $data['auto_reply'] = $request->boolean('auto_reply');
        $data['updated_by'] = $request->user() ? $request->user()->id : null;

        $instance->update($data);

        return redirect()->route('whatsapp-api.instances.index')->with('status', 'Instance diperbarui.');
    }

    public function saveAndTest(Request $request, WhatsAppInstance $instance): RedirectResponse
    {
        $data = $this->validated($request, $instance);
        $data['is_active'] = $request->boolean('is_active');
        $data['auto_reply'] = $request->boolean('auto_reply');
        $data['updated_by'] = $request->user()?->id;

        $instance->update($data);
        $instance->refresh();

        $result = $this->runStoredCloudCredentialTest($instance);

        if ($result['ok']) {
            return redirect()
                ->route('whatsapp-api.instances.edit', $instance)
                ->with('status', $result['message']);
        }

        return redirect()
            ->route('whatsapp-api.instances.edit', $instance)
            ->with('status', ($result['message'] ?? 'Gagal test credentials.') . (!empty($result['error']) ? ' ' . $result['error'] : ''));
    }

    public function saveAndSyncTemplates(Request $request, WhatsAppInstance $instance): RedirectResponse
    {
        $data = $this->validated($request, $instance);
        $data['is_active'] = $request->boolean('is_active');
        $data['auto_reply'] = $request->boolean('auto_reply');
        $data['updated_by'] = $request->user()?->id;

        $instance->update($data);
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
        if ($cloudToken === '') {
            $missing[] = 'Cloud Access Token';
        }
        if ($missing) {
            return response()->json([
                'ok' => false,
                'message' => 'Lengkapi field wajib terlebih dahulu: ' . implode(', ', $missing) . '.',
            ], 422);
        }

        $result = $this->executeCloudCredentialTest($phoneNumberId, $cloudToken);
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
        $cloudToken = trim((string) $instance->cloud_token);
        $missing = [];
        if ($phoneNumberId === '') {
            $missing[] = 'Phone Number ID';
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

        return $this->executeCloudCredentialTest($phoneNumberId, $cloudToken);
    }

    private function executeCloudCredentialTest(string $phoneNumberId, string $cloudToken): array
    {
        $base = rtrim((string) config('services.wa_cloud.base_url', 'https://graph.facebook.com/v20.0'), '/');
        $url = "{$base}/{$phoneNumberId}";

        try {
            $response = Http::timeout(15)
                ->withToken($cloudToken)
                ->get($url, ['fields' => 'id,display_phone_number,verified_name']);

            if ($response->successful()) {
                return [
                    'ok' => true,
                    'message' => 'Koneksi ke WhatsApp Cloud API berhasil.',
                    'data' => [
                        'id' => $response->json('id'),
                        'display_phone_number' => $response->json('display_phone_number'),
                        'verified_name' => $response->json('verified_name'),
                    ],
                ];
            }

            $errorMessage = (string) ($response->json('error.message') ?: $response->body() ?: 'Unknown error');
            return [
                'ok' => false,
                'message' => 'Gagal konek ke WhatsApp Cloud API.',
                'error' => $errorMessage,
                'status' => 422,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Gagal konek ke WhatsApp Cloud API.',
                'error' => $e->getMessage(),
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
        $base = rtrim((string) config('services.wa_cloud.base_url', 'https://graph.facebook.com/v20.0'), '/');
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
            'chatbot_account_id' => ['nullable', 'exists:chatbot_accounts,id'],
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
