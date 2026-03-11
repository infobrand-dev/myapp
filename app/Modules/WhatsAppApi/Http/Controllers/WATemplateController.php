<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppApi\Jobs\SubmitTemplateToMeta;
use App\Modules\WhatsAppApi\Models\WATemplate;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Modules\WhatsAppApi\Support\TemplateVariableResolver;
use Illuminate\Support\Arr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WATemplateController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureAnyInstanceExists($request)) {
            return $redirect;
        }

        $templates = WATemplate::orderBy('name')->paginate(20);
        return view('whatsappapi::templates.index', compact('templates'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureAnyInstanceExists($request)) {
            return $redirect;
        }

        $template = new WATemplate(['language' => 'en', 'status' => 'draft', 'category' => 'utility']);
        $namespaces = WATemplate::whereNotNull('namespace')->distinct()->pluck('namespace');
        $instances = $this->connectedCloudInstances();
        $contactFieldOptions = TemplateVariableResolver::contactFieldOptions();
        $senderFieldOptions = TemplateVariableResolver::senderFieldOptions();
        $senderPreviewContext = TemplateVariableResolver::contextFromSender($request->user());
        return view('whatsappapi::templates.form', compact('template', 'namespaces', 'instances', 'contactFieldOptions', 'senderFieldOptions', 'senderPreviewContext'));
    }

    public function store(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureAnyInstanceExists($request)) {
            return $redirect;
        }

        $data = $this->validated($request);
        $instance = $this->requireInstance($request);
        $data['namespace'] = $instance->cloud_business_account_id;
        $action = $request->input('action');
        $data['components'] = $this->buildComponents($request);
        $template = WATemplate::create($data);

        if ($action === 'submit') {
            SubmitTemplateToMeta::dispatch($template->id, $instance->id);
        }

        return redirect()->route('whatsapp-api.templates.index')->with('status', $action === 'submit'
            ? 'Template dibuat dan masuk antrean submit. Status akan berubah pending setelah Meta menerima request.'
            : 'Template dibuat.');
    }

    public function edit(Request $request, WATemplate $template): View|RedirectResponse
    {
        if ($redirect = $this->ensureAnyInstanceExists($request)) {
            return $redirect;
        }

        $namespaces = WATemplate::whereNotNull('namespace')->distinct()->pluck('namespace');
        $instances = $this->connectedCloudInstances();
        $contactFieldOptions = TemplateVariableResolver::contactFieldOptions();
        $senderFieldOptions = TemplateVariableResolver::senderFieldOptions();
        $senderPreviewContext = TemplateVariableResolver::contextFromSender($request->user());
        return view('whatsappapi::templates.form', compact('template', 'namespaces', 'instances', 'contactFieldOptions', 'senderFieldOptions', 'senderPreviewContext'));
    }

    public function update(Request $request, WATemplate $template): RedirectResponse
    {
        if ($redirect = $this->ensureAnyInstanceExists($request)) {
            return $redirect;
        }

        $data = $this->validated($request);
        $instance = $this->requireInstance($request);
        $data['namespace'] = $instance->cloud_business_account_id;
        $action = $request->input('action');
        $data['components'] = $this->buildComponents($request);
        $template->update($data);

        if ($action === 'submit') {
            if ($this->isMetaManagedTemplate($template)) {
                return redirect()
                    ->route('whatsapp-api.templates.edit', $template)
                    ->with('status', 'Template ini sudah terdaftar di Meta dan tidak bisa di-submit ulang sebagai template yang sama. Simpan perubahan sebagai draft lokal atau buat template baru dengan Meta Name baru.');
            }
            SubmitTemplateToMeta::dispatch($template->id, $instance->id);
        }
        return redirect()->route('whatsapp-api.templates.index')->with('status', $action === 'submit'
            ? 'Template diperbarui dan masuk antrean submit. Status akan berubah pending setelah Meta menerima request.'
            : 'Template diperbarui.');
    }

    public function destroy(Request $request, WATemplate $template): RedirectResponse
    {
        if ($redirect = $this->ensureAnyInstanceExists($request)) {
            return $redirect;
        }

        $template->delete();
        return back()->with('status', 'Template dihapus.');
    }

    public function submit(Request $request, WATemplate $template): RedirectResponse
    {
        if ($redirect = $this->ensureAnyInstanceExists($request)) {
            return $redirect;
        }

        if ($this->isMetaManagedTemplate($template)) {
            return back()->with('status', 'Template ini sudah ada di Meta. Untuk perubahan konten, buat template baru dengan Meta Name baru lalu submit template baru tersebut.');
        }

        $instance = $this->findInstanceForNamespace($template->namespace);
        if (!$instance) {
            return back()->with('status', 'Instance cloud connected dengan namespace tersebut tidak ditemukan.');
        }
        SubmitTemplateToMeta::dispatch($template->id, $instance->id);
        return back()->with('status', 'Template masuk antrean submit. Status akan berubah pending setelah Meta menerima request.');
    }

    public function refreshStatuses(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureAnyInstanceExists($request)) {
            return $redirect;
        }

        $instances = $this->connectedCloudInstances();
        if ($instances->isEmpty()) {
            return back()->with('status', 'Tidak ada instance Cloud dengan kredensial lengkap untuk sync template.');
        }

        $created = 0;
        $updated = 0;
        $fetched = 0;
        $errors = [];

        foreach ($instances as $instance) {
            $result = $this->syncTemplatesForInstance($instance);
            if (!($result['ok'] ?? false)) {
                $label = $instance->name ?: ('Instance #' . $instance->id);
                $errors[] = $label . ': ' . ($result['error'] ?? $result['message'] ?? 'Gagal sync');
                continue;
            }

            $fetched += (int) data_get($result, 'data.fetched', 0);
            $created += (int) data_get($result, 'data.created', 0);
            $updated += (int) data_get($result, 'data.updated', 0);
        }

        $message = "Status template disegarkan. Fetched: {$fetched}, Created: {$created}, Updated: {$updated}.";
        if ($errors) {
            $message .= ' Error: ' . implode(' | ', $errors);
        }

        return redirect()->route('whatsapp-api.templates.index')->with('status', $message);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'meta_name' => ['nullable', 'string', 'max:150', 'regex:/^[a-z_]+$/'],
            'language' => ['required', 'regex:/^[a-z]{2}(?:_[A-Z]{2})?$/'],
            'category' => ['required', 'in:utility,marketing,authentication'],
            'instance_id' => ['required', 'integer'],
            'body' => ['required', 'string', 'max:1024'],
            'status' => ['required', 'in:draft,pending,approved,rejected'],
            'header_type' => ['nullable', 'in:none,text,image,document,video'],
            'header_text' => ['nullable', 'string', 'max:60'],
            'header_media_url' => ['nullable', 'string', 'max:2048'],
            'header_media_file' => ['nullable', 'file', 'max:20480'],
            'footer_text' => ['nullable', 'string', 'max:60'],
            'variable_mappings' => ['nullable', 'array'],
            'variable_mappings.*.source_type' => ['nullable', 'in:text,contact_field,sender_field'],
            'variable_mappings.*.text_value' => ['nullable', 'string', 'max:500'],
            'variable_mappings.*.contact_field' => ['nullable', 'string', 'max:50'],
            'variable_mappings.*.sender_field' => ['nullable', 'string', 'max:50'],
            'variable_mappings.*.fallback_value' => ['nullable', 'string', 'max:500'],

            // New generic Meta button rows
            'buttons' => ['nullable', 'array'],
            'buttons.*.type' => ['nullable', 'in:quick_reply,url,phone_number,copy_code'],
            'buttons.*.label' => ['nullable', 'string', 'max:25'],
            'buttons.*.url' => ['nullable', 'string', 'max:2000'],
            'buttons.*.phone_number' => ['nullable', 'regex:/^\+?[1-9]\d{6,14}$/'],
            'buttons.*.example' => ['nullable', 'string', 'max:255'],

            // Legacy form compatibility
            'qr_label' => ['array'],
            'qr_label.*' => ['nullable', 'string', 'max:25'],
            'cta_url_label' => ['nullable', 'string', 'max:25'],
            'cta_url_value' => ['nullable', 'string', 'max:2000'],
            'cta_phone_label' => ['nullable', 'string', 'max:25'],
            'cta_phone_value' => ['nullable', 'regex:/^\+?[1-9]\d{6,14}$/'],
            'button_mode' => ['nullable', 'in:none,quick_reply,cta'],
        ]);

        $this->assertPlaceholders($data['body'], 'Body');
        $headerText = null;
        if (($data['header_type'] ?? 'none') === 'text' && !empty($data['header_text'])) {
            $this->assertPlaceholders($data['header_text'], 'Header');
            $this->assertHeaderTextPlaceholderRules((string) $data['header_text']);
            $headerText = (string) $data['header_text'];
        }

        $normalizedMetaName = $this->normalizeMetaName(
            (string) ($data['meta_name'] ?? ''),
            (string) $data['name']
        );
        $data['meta_name'] = $normalizedMetaName;

        if ((string) $request->input('action') === 'submit' && $normalizedMetaName === '') {
            throw ValidationException::withMessages([
                'meta_name' => 'Meta Name wajib diisi atau bisa digenerate dari nama internal sebelum submit.',
            ]);
        }

        $placeholders = TemplateVariableResolver::placeholderIndexes($data['body'], $headerText);
        $data['variable_mappings'] = TemplateVariableResolver::normalizeMappings(
            (array) $request->input('variable_mappings', []),
            $placeholders
        );

        if (in_array($data['header_type'] ?? 'none', ['image', 'document', 'video'], true)
            && !$request->hasFile('header_media_file')
            && empty($data['header_media_url'])) {
            throw ValidationException::withMessages(['header_media_file' => 'Upload file media header wajib untuk tipe media.']);
        }

        if ($request->hasFile('header_media_file')) {
            $this->validateHeaderMediaFile($request->file('header_media_file'), (string) ($data['header_type'] ?? 'none'));
        }

        $this->validateCategorySpecificRules($data, $request);
        $this->validateButtons($request);

        return $data;
    }

    private function normalizeMetaName(string $metaName, string $displayName): string
    {
        $source = trim($metaName) !== '' ? trim($metaName) : trim($displayName);
        if ($source === '') {
            return '';
        }

        $normalized = strtolower($source);
        $normalized = preg_replace('/[^a-z]+/', '_', $normalized) ?? '';
        $normalized = trim($normalized, '_');
        $normalized = preg_replace('/_+/', '_', $normalized) ?? '';

        return mb_substr($normalized, 0, 150);
    }

    private function validateButtons(Request $request): void
    {
        $rows = $this->extractButtons($request);
        if (count($rows) > 10) {
            throw ValidationException::withMessages(['buttons' => 'Maksimal 10 tombol per template.']);
        }

        $quickCount = 0;
        $urlCount = 0;
        $phoneCount = 0;
        $copyCodeCount = 0;
        $typePattern = [];

        foreach ($rows as $i => $button) {
            if (empty($button['text'])) {
                throw ValidationException::withMessages(["buttons.{$i}.label" => 'Label tombol wajib diisi.']);
            }

            if ($button['type'] === 'QUICK_REPLY') {
                $quickCount++;
            }

             $typePattern[] = $button['type'] === 'QUICK_REPLY' ? 'Q' : 'N';

            if ($button['type'] === 'URL' && empty($button['url'])) {
                throw ValidationException::withMessages(["buttons.{$i}.url" => 'URL wajib diisi untuk button URL.']);
            }

            if ($button['type'] === 'PHONE_NUMBER' && empty($button['phone_number'])) {
                throw ValidationException::withMessages(["buttons.{$i}.phone_number" => 'Nomor telepon wajib diisi untuk button Phone.']);
            }

            if ($button['type'] === 'URL') {
                $urlCount++;
                $this->assertValidMetaUrlButton((string) ($button['url'] ?? ''), "buttons.{$i}.url");
                preg_match_all('/\{\{(\d+)\}\}/', (string) ($button['url'] ?? ''), $matches);
                $urlVars = array_values(array_unique($matches[1] ?? []));
                if (count($urlVars) > 1) {
                    throw ValidationException::withMessages([
                        "buttons.{$i}.url" => 'Button URL Meta hanya mendukung maksimal 1 placeholder.',
                    ]);
                }

                if (!empty($urlVars) && empty($button['example'])) {
                    throw ValidationException::withMessages([
                        "buttons.{$i}.example" => 'Button URL yang memakai placeholder wajib punya sample/example URL.',
                    ]);
                }
            }

            if ($button['type'] === 'PHONE_NUMBER') {
                $phoneCount++;
            }

            if ($button['type'] === 'COPY_CODE') {
                $copyCodeCount++;
                if (empty($button['example'])) {
                    throw ValidationException::withMessages([
                        "buttons.{$i}.example" => 'Button Copy Code wajib punya sample/example code.',
                    ]);
                }
                if (mb_strlen((string) $button['example']) > 15) {
                    throw ValidationException::withMessages([
                        "buttons.{$i}.example" => 'Sample Copy Code maksimal 15 karakter sesuai batas Meta.',
                    ]);
                }
            }
        }

        if ($quickCount > 10) {
            throw ValidationException::withMessages(['buttons' => 'Quick Reply maksimal 10 tombol.']);
        }

        if ($urlCount > 2) {
            throw ValidationException::withMessages(['buttons' => 'Template Meta dibatasi maksimal 2 tombol URL.']);
        }

        if ($phoneCount > 1) {
            throw ValidationException::withMessages(['buttons' => 'Template Meta dibatasi maksimal 1 tombol Phone Number.']);
        }

        if ($copyCodeCount > 1) {
            throw ValidationException::withMessages(['buttons' => 'Template Meta dibatasi maksimal 1 tombol Copy Code.']);
        }

        if ($this->hasInterleavedQuickReplyButtons($typePattern)) {
            throw ValidationException::withMessages([
                'buttons' => 'Quick Reply harus dikelompokkan berurutan di awal atau di akhir, tidak boleh diselingi tombol lain.',
            ]);
        }
    }

    private function validateCategorySpecificRules(array $data, Request $request): void
    {
        $category = strtolower((string) ($data['category'] ?? 'utility'));
        if ($category !== 'authentication') {
            return;
        }

        $headerType = strtolower((string) ($data['header_type'] ?? 'none'));
        if (in_array($headerType, ['image', 'document', 'video'], true)) {
            throw ValidationException::withMessages([
                'header_type' => 'Template authentication yang didukung modul ini tidak mendukung media header.',
            ]);
        }

        $buttons = $this->extractButtons($request);
        if (count($buttons) > 1) {
            throw ValidationException::withMessages([
                'buttons' => 'Template authentication yang didukung modul ini maksimal 1 tombol.',
            ]);
        }

        foreach ($buttons as $index => $button) {
            if (($button['type'] ?? '') !== 'COPY_CODE') {
                throw ValidationException::withMessages([
                    "buttons.{$index}.type" => 'Template authentication pada modul ini saat ini hanya mendukung tombol Copy Code.',
                ]);
            }
        }
    }

    private function buildComponents(Request $request): ?array
    {
        $components = [];

        $headerType = $request->input('header_type', 'none');
        if ($headerType === 'text' && $request->filled('header_text')) {
            $components[] = [
                'type' => 'header',
                'format' => 'TEXT',
                'text' => $request->input('header_text'),
                'parameters' => [['type' => 'text', 'text' => $request->input('header_text')]],
            ];
        } elseif (in_array($headerType, ['image', 'document', 'video'], true)) {
            $mediaParam = [];
            $storedPath = $this->resolveStoredHeaderMediaPath($request);
            $mediaUrl = $storedPath ? asset('storage/' . ltrim($storedPath, '/')) : $this->resolveHeaderMediaUrl($request);
            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $request->hasFile('header_media_file') ? $request->file('header_media_file') : null;
            if ($mediaUrl) {
                $param = ['type' => $headerType, 'link' => $mediaUrl];
                if ($storedPath) {
                    $param['storage_disk'] = 'public';
                    $param['storage_path'] = $storedPath;
                }
                if ($uploadedFile) {
                    $param['original_name'] = $uploadedFile->getClientOriginalName();
                    $param['mime_type'] = $uploadedFile->getMimeType();
                    $param['size'] = $uploadedFile->getSize();
                }
                $mediaParam[] = $param;
            }
            $components[] = [
                'type' => 'header',
                'format' => strtoupper($headerType),
                'parameters' => $mediaParam ?: null,
            ];
        }

        if ($request->filled('footer_text')) {
            $components[] = [
                'type' => 'footer',
                'text' => $request->input('footer_text'),
            ];
        }

        $buttons = $this->extractButtons($request);
        if (!empty($buttons)) {
            $components[] = [
                'type' => 'buttons',
                'buttons' => $buttons,
            ];
        }

        return $components ?: null;
    }

    private function resolveHeaderMediaUrl(Request $request): ?string
    {
        if ($request->hasFile('header_media_file')) {
            /** @var UploadedFile $file */
            $file = $request->file('header_media_file');
            $path = $file->store('wa_templates/headers/' . now()->format('Y/m'), 'public');
            return asset('storage/' . ltrim($path, '/'));
        }

        $existingUrl = trim((string) $request->input('header_media_url', ''));
        return $existingUrl !== '' ? $existingUrl : null;
    }

    private function resolveStoredHeaderMediaPath(Request $request): ?string
    {
        if (!$request->hasFile('header_media_file')) {
            return null;
        }

        /** @var UploadedFile $file */
        $file = $request->file('header_media_file');
        $path = $file->store('wa_templates/headers/' . now()->format('Y/m'), 'public');

        return $path !== '' ? ltrim(str_replace('\\', '/', $path), '/') : null;
    }

    private function validateHeaderMediaFile(UploadedFile $file, string $headerType): void
    {
        $mime = strtolower((string) ($file->getMimeType() ?? ''));
        if ($mime === '') {
            throw ValidationException::withMessages([
                'header_media_file' => 'Mime type file tidak dikenali.',
            ]);
        }

        if ($headerType === 'image' && !str_starts_with($mime, 'image/')) {
            throw ValidationException::withMessages([
                'header_media_file' => 'File header harus bertipe image.',
            ]);
        }

        if ($headerType === 'video' && !str_starts_with($mime, 'video/')) {
            throw ValidationException::withMessages([
                'header_media_file' => 'File header harus bertipe video.',
            ]);
        }

        if ($headerType === 'document' && (str_starts_with($mime, 'image/') || str_starts_with($mime, 'video/'))) {
            throw ValidationException::withMessages([
                'header_media_file' => 'Header document tidak boleh image/video.',
            ]);
        }
    }

    private function extractButtons(Request $request): array
    {
        $rows = [];

        // New format first
        foreach ((array) $request->input('buttons', []) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $typeRaw = strtolower(trim((string) ($row['type'] ?? '')));
            $label = trim((string) ($row['label'] ?? ''));
            $url = trim((string) ($row['url'] ?? ''));
            $phone = trim((string) ($row['phone_number'] ?? ''));
            $example = trim((string) ($row['example'] ?? ''));

            if ($label === '' && $url === '' && $phone === '' && $example === '') {
                continue;
            }

            if ($typeRaw === '' && $label === '') {
                continue;
            }

            $mappedType = match ($typeRaw) {
                'quick_reply' => 'QUICK_REPLY',
                'url' => 'URL',
                'phone_number' => 'PHONE_NUMBER',
                'copy_code' => 'COPY_CODE',
                default => null,
            };
            if (!$mappedType) {
                continue;
            }

            $item = [
                'type' => $mappedType,
                'text' => $label,
            ];

            if ($mappedType === 'URL' && $url !== '') {
                $item['url'] = $url;
            }
            if ($mappedType === 'PHONE_NUMBER' && $phone !== '') {
                $item['phone_number'] = $phone;
            }
            if (in_array($mappedType, ['URL', 'COPY_CODE'], true) && $example !== '') {
                $item['example'] = $example;
            }

            $rows[] = $item;
        }

        if (!empty($rows)) {
            return array_values($rows);
        }

        // Legacy compatibility
        $mode = $request->input('button_mode', 'none');
        if ($mode === 'quick_reply') {
            $labels = $request->input('qr_label', []);
            foreach ($labels as $label) {
                $label = trim((string) $label);
                if ($label === '') {
                    continue;
                }
                $rows[] = [
                    'type' => 'QUICK_REPLY',
                    'text' => $label,
                ];
            }
        } elseif ($mode === 'cta') {
            $urlLabel = trim((string) $request->input('cta_url_label'));
            $urlValue = trim((string) $request->input('cta_url_value'));
            if ($urlLabel !== '' && $urlValue !== '') {
                $rows[] = [
                    'type' => 'URL',
                    'text' => $urlLabel,
                    'url' => $urlValue,
                ];
            }
            $phoneLabel = trim((string) $request->input('cta_phone_label'));
            $phoneValue = trim((string) $request->input('cta_phone_value'));
            if ($phoneLabel !== '' && $phoneValue !== '') {
                $rows[] = [
                    'type' => 'PHONE_NUMBER',
                    'text' => $phoneLabel,
                    'phone_number' => $phoneValue,
                ];
            }
        }

        return array_values($rows);
    }

    private function assertPlaceholders(string $text, string $label): void
    {
        preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);
        $indexes = array_map('intval', $matches[1] ?? []);
        if (empty($indexes)) {
            return;
        }
        $unique = array_values(array_unique($indexes));
        sort($unique);
        $expected = range(1, count($unique));
        if ($unique !== $expected) {
            throw ValidationException::withMessages([
                'body' => "{$label}: placeholder harus urut {{1}}, {{2}}, ... tanpa lompat.",
            ]);
        }
        if (count($unique) > 60) {
            throw ValidationException::withMessages([
                'body' => "{$label}: maksimal 60 placeholder sesuai batas Meta.",
            ]);
        }
    }

    private function assertHeaderTextPlaceholderRules(?string $text): void
    {
        preg_match_all('/\{\{(\d+)\}\}/', (string) $text, $matches);
        $indexes = array_values(array_unique(array_map('intval', $matches[1] ?? [])));
        if (count($indexes) > 1) {
            throw ValidationException::withMessages([
                'header_text' => 'Header text Meta hanya mendukung maksimal 1 placeholder.',
            ]);
        }
    }

    private function assertValidMetaUrlButton(string $url, string $field): void
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return;
        }

        $sanitized = preg_replace('/\{\{\d+\}\}/', 'placeholder', $trimmed);
        if (!is_string($sanitized) || $sanitized === '') {
            throw ValidationException::withMessages([
                $field => 'URL tombol tidak valid.',
            ]);
        }

        if (filter_var($sanitized, FILTER_VALIDATE_URL) === false) {
            throw ValidationException::withMessages([
                $field => 'URL tombol harus berupa URL absolut yang valid. Placeholder boleh dipakai maksimal 1 kali.',
            ]);
        }
    }

    private function hasInterleavedQuickReplyButtons(array $typePattern): bool
    {
        if (count($typePattern) < 3) {
            return false;
        }

        $transitions = 0;
        $previous = $typePattern[0];
        foreach (array_slice($typePattern, 1) as $current) {
            if ($current !== $previous) {
                $transitions++;
                $previous = $current;
            }
        }

        return $transitions > 1;
    }

    private function connectedCloudInstances()
    {
        return WhatsAppInstance::where('provider', 'cloud')
            ->whereNotNull('cloud_business_account_id')
            ->where('cloud_business_account_id', '!=', '')
            ->whereNotNull('phone_number_id')
            ->where('phone_number_id', '!=', '')
            ->whereNotNull('cloud_token')
            ->where('cloud_token', '!=', '')
            ->select(['id', 'name', 'phone_number_id', 'cloud_business_account_id', 'cloud_token'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    private function isMetaManagedTemplate(WATemplate $template): bool
    {
        return trim((string) $template->meta_template_id) !== '';
    }

    private function syncTemplatesForInstance(WhatsAppInstance $instance): array
    {
        $businessId = trim((string) $instance->cloud_business_account_id);
        $cloudToken = trim((string) $instance->cloud_token);

        if ($businessId === '' || $cloudToken === '') {
            return [
                'ok' => false,
                'message' => 'Kredensial Cloud belum lengkap.',
                'status' => 422,
            ];
        }

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
                    return [
                        'ok' => false,
                        'message' => 'Gagal sync template dari WhatsApp Cloud API.',
                        'error' => (string) ($response->json('error.message') ?: $response->body() ?: 'Unknown error'),
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
                        'approved', 'active' => 'approved',
                        'pending', 'in_appeal', 'paused' => 'pending',
                        'rejected', 'disabled', 'deleted' => 'rejected',
                        default => 'rejected',
                    };

                    if ($metaId !== '') {
                        $model = WATemplate::firstOrNew(['meta_template_id' => $metaId]);
                    } else {
                        $model = WATemplate::query()
                            ->where('language', $language)
                            ->where('namespace', $businessId)
                            ->where(function ($query) use ($name) {
                                $query->where('meta_name', $name)
                                    ->orWhere(function ($fallbackQuery) use ($name) {
                                        $fallbackQuery->whereNull('meta_name')
                                            ->where('name', $name);
                                    });
                            })
                            ->first() ?? new WATemplate([
                                'language' => $language,
                                'namespace' => $businessId,
                            ]);
                    }

                    $isNew = !$model->exists;
                    $model->fill([
                        'name' => $model->name ?: ($name ?: 'unnamed_template'),
                        'meta_name' => $name ?: ($model->meta_name ?: null),
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
                'message' => 'Sync template berhasil.',
                'data' => compact('fetched', 'created', 'updated'),
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

    private function findInstanceForNamespace(?string $namespace): ?WhatsAppInstance
    {
        if (!$namespace) {
            return null;
        }
        return WhatsAppInstance::where('provider', 'cloud')
            ->whereNotNull('cloud_business_account_id')
            ->where('cloud_business_account_id', '!=', '')
            ->where('cloud_business_account_id', $namespace)
            ->first();
    }

    private function requireInstance(Request $request): WhatsAppInstance
    {
        $instance = WhatsAppInstance::where('id', $request->integer('instance_id'))
            ->where('provider', 'cloud')
            ->whereNotNull('cloud_business_account_id')
            ->where('cloud_business_account_id', '!=', '')
            ->whereNotNull('phone_number_id')
            ->where('phone_number_id', '!=', '')
            ->whereNotNull('cloud_token')
            ->where('cloud_token', '!=', '')
            ->first();

        if (!$instance) {
            throw ValidationException::withMessages([
                'instance_id' => 'Pilih instance Cloud yang kredensialnya lengkap.',
            ]);
        }

        return $instance;
    }

    private function ensureAnyInstanceExists(Request $request): ?RedirectResponse
    {
        if (WhatsAppInstance::query()->exists()) {
            return null;
        }

        return redirect()
            ->route('whatsapp-api.instances.create')
            ->with('status', 'Buat WA Instance terlebih dahulu sebelum mengakses WA Templates.');
    }
}
