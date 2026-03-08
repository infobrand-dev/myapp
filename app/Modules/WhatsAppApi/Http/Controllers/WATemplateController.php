<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppApi\Jobs\SubmitTemplateToMeta;
use App\Modules\WhatsAppApi\Models\WATemplate;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
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
        return view('whatsappapi::templates.form', compact('template', 'namespaces', 'instances'));
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
        if ($action === 'submit') {
            $data['status'] = 'pending';
        }
        $data['components'] = $this->buildComponents($request);
        $template = WATemplate::create($data);

        if ($action === 'submit') {
            SubmitTemplateToMeta::dispatch($template->id, $instance->id);
        }

        return redirect()->route('whatsapp-api.templates.index')->with('status', 'Template dibuat.');
    }

    public function edit(Request $request, WATemplate $template): View|RedirectResponse
    {
        if ($redirect = $this->ensureAnyInstanceExists($request)) {
            return $redirect;
        }

        $namespaces = WATemplate::whereNotNull('namespace')->distinct()->pluck('namespace');
        $instances = $this->connectedCloudInstances();
        return view('whatsappapi::templates.form', compact('template', 'namespaces', 'instances'));
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
        if ($action === 'submit') {
            $data['status'] = 'pending';
        }
        $data['components'] = $this->buildComponents($request);
        $template->update($data);

        if ($action === 'submit') {
            SubmitTemplateToMeta::dispatch($template->id, $instance->id);
        }
        return redirect()->route('whatsapp-api.templates.index')->with('status', 'Template diperbarui.');
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

        $instance = $this->findInstanceForNamespace($template->namespace);
        if (!$instance) {
            return back()->with('status', 'Instance cloud connected dengan namespace tersebut tidak ditemukan.');
        }
        $template->update(['status' => 'pending']);
        SubmitTemplateToMeta::dispatch($template->id, $instance->id);
        return back()->with('status', 'Template dikirim ke Meta (pending).');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
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

            // New generic Meta button rows
            'buttons' => ['nullable', 'array'],
            'buttons.*.type' => ['nullable', 'in:quick_reply,url,phone_number,copy_code'],
            'buttons.*.label' => ['nullable', 'string', 'max:25'],
            'buttons.*.url' => ['nullable', 'url', 'max:2000'],
            'buttons.*.phone_number' => ['nullable', 'regex:/^\+?[1-9]\d{6,14}$/'],
            'buttons.*.example' => ['nullable', 'string', 'max:255'],

            // Legacy form compatibility
            'qr_label' => ['array'],
            'qr_label.*' => ['nullable', 'string', 'max:25'],
            'cta_url_label' => ['nullable', 'string', 'max:25'],
            'cta_url_value' => ['nullable', 'url'],
            'cta_phone_label' => ['nullable', 'string', 'max:25'],
            'cta_phone_value' => ['nullable', 'regex:/^\+?[1-9]\d{6,14}$/'],
            'button_mode' => ['nullable', 'in:none,quick_reply,cta'],
        ]);

        $this->assertPlaceholders($data['body'], 'Body');
        if (($data['header_type'] ?? 'none') === 'text' && !empty($data['header_text'])) {
            $this->assertPlaceholders($data['header_text'], 'Header');
        }

        if (in_array($data['header_type'] ?? 'none', ['image', 'document', 'video'], true)
            && !$request->hasFile('header_media_file')
            && empty($data['header_media_url'])) {
            throw ValidationException::withMessages(['header_media_file' => 'Upload file media header wajib untuk tipe media.']);
        }

        if ($request->hasFile('header_media_file')) {
            $this->validateHeaderMediaFile($request->file('header_media_file'), (string) ($data['header_type'] ?? 'none'));
        }

        $this->validateButtons($request);

        return $data;
    }

    private function validateButtons(Request $request): void
    {
        $rows = $this->extractButtons($request);
        if (count($rows) > 10) {
            throw ValidationException::withMessages(['buttons' => 'Maksimal 10 tombol per template.']);
        }

        $quickCount = 0;
        foreach ($rows as $i => $button) {
            if (empty($button['text'])) {
                throw ValidationException::withMessages(["buttons.{$i}.label" => 'Label tombol wajib diisi.']);
            }

            if ($button['type'] === 'QUICK_REPLY') {
                $quickCount++;
            }

            if ($button['type'] === 'URL' && empty($button['url'])) {
                throw ValidationException::withMessages(["buttons.{$i}.url" => 'URL wajib diisi untuk button URL.']);
            }

            if ($button['type'] === 'PHONE_NUMBER' && empty($button['phone_number'])) {
                throw ValidationException::withMessages(["buttons.{$i}.phone_number" => 'Nomor telepon wajib diisi untuk button Phone.']);
            }
        }

        if ($quickCount > 10) {
            throw ValidationException::withMessages(['buttons' => 'Quick Reply maksimal 10 tombol.']);
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
            $mediaUrl = $this->resolveHeaderMediaUrl($request);
            if ($mediaUrl) {
                $mediaParam[] = ['type' => $headerType, 'link' => $mediaUrl];
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

            $url = trim((string) ($row['url'] ?? ''));
            $phone = trim((string) ($row['phone_number'] ?? ''));
            $example = trim((string) ($row['example'] ?? ''));

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

    private function connectedCloudInstances()
    {
        return WhatsAppInstance::where('provider', 'cloud')
            ->whereNotNull('cloud_business_account_id')
            ->where('cloud_business_account_id', '!=', '')
            ->whereNotNull('phone_number_id')
            ->where('phone_number_id', '!=', '')
            ->whereNotNull('cloud_token')
            ->where('cloud_token', '!=', '')
            ->select(['id', 'name', 'cloud_business_account_id'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
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
