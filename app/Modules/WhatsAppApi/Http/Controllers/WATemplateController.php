<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppApi\Models\WATemplate;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Modules\WhatsAppApi\Jobs\SubmitTemplateToMeta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

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

        $template = new WATemplate(['language' => 'en', 'status' => 'active', 'category' => 'utility']);
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
            'status' => ['required', 'in:draft,pending,active,inactive'],
            'header_type' => ['nullable', 'in:none,text,image,document,video'],
            'header_text' => ['nullable', 'string', 'max:60'],
            'header_media_url' => ['nullable', 'url', 'max:255'],

            // quick reply buttons
            'qr_label' => ['array'],
            'qr_label.*' => ['nullable', 'string', 'max:25'],

            // CTA buttons
            'cta_url_label' => ['nullable', 'string', 'max:25'],
            'cta_url_value' => ['nullable', 'url'],
            'cta_phone_label' => ['nullable', 'string', 'max:25'],
            'cta_phone_value' => ['nullable', 'regex:/^\\+?[1-9]\\d{6,14}$/'],

            'button_mode' => ['nullable', 'in:none,quick_reply,cta'],
        ]);

        // Meta rules: placeholders sequential, header/text pairing, CTA URL whitelist (optional)
        $this->assertPlaceholders($data['body'], 'Body');
        if (($data['header_type'] ?? 'none') === 'text' && !empty($data['header_text'])) {
            $this->assertPlaceholders($data['header_text'], 'Header');
        }
        if (!empty($data['namespace']) && strlen($data['namespace']) < 3) {
            throw ValidationException::withMessages(['namespace' => 'Namespace terlalu pendek.']);
        }

        // Header media must have url if format media
        if (in_array($data['header_type'] ?? 'none', ['image','document','video'], true) && empty($data['header_media_url'])) {
            throw ValidationException::withMessages(['header_media_url' => 'URL media header wajib diisi untuk tipe media.']);
        }

        return $data;
    }

    private function buildComponents(Request $request): ?array
    {
        $components = [];
        $headerType = $request->input('header_type', 'none');
        if ($headerType === 'text' && $request->filled('header_text')) {
            $components[] = [
                'type' => 'header',
                'format' => 'TEXT',
                'parameters' => [['type' => 'text', 'text' => $request->input('header_text')]],
            ];
        } elseif (in_array($headerType, ['image','document','video'], true)) {
            $mediaParam = [];
            if ($request->filled('header_media_url')) {
                $mediaParam[] = ['type' => $headerType, 'link' => $request->input('header_media_url')];
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
        $mode = $request->input('button_mode', 'none');
        if ($mode === 'quick_reply') {
            $labels = $request->input('qr_label', []);
            $added = 0;
            foreach ($labels as $idx => $label) {
                if ($added >= 3) break;
                if (!trim($label)) continue;
                $components[] = [
                    'type' => 'button',
                    'sub_type' => 'quick_reply',
                    'index' => (string)$added,
                    'parameters' => [
                        ['type' => 'text', 'text' => $label],
                    ],
                ];
                $added++;
            }
        } elseif ($mode === 'cta') {
            $cta = [];
            $urlLabel = $request->input('cta_url_label');
            $urlValue = $request->input('cta_url_value');
            if (trim($urlLabel) && trim($urlValue)) {
                $cta[] = [
                    'type' => 'button',
                    'sub_type' => 'url',
                    'index' => '0',
                    'parameters' => [['type' => 'text', 'text' => $urlLabel]],
                    'url' => $urlValue,
                ];
            }
            $phoneLabel = $request->input('cta_phone_label');
            $phoneValue = $request->input('cta_phone_value');
            if (trim($phoneLabel) && trim($phoneValue)) {
                $cta[] = [
                    'type' => 'button',
                    'sub_type' => 'phone_number',
                    'index' => '1',
                    'parameters' => [['type' => 'text', 'text' => $phoneLabel]],
                    'phone_number' => $phoneValue,
                ];
            }
            $components = array_merge($components, array_slice($cta, 0, 2));
        }

        return $components ?: null;
    }

    private function assertPlaceholders(string $text, string $label): void
    {
        preg_match_all('/\\{\\{(\\d+)\\}\\}/', $text, $matches);
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
            ->where('status', 'connected')
            ->select(['id', 'name', 'cloud_business_account_id'])
            ->orderBy('name')
            ->get();
    }

    private function findInstanceForNamespace(?string $namespace): ?WhatsAppInstance
    {
        if (!$namespace) {
            return null;
        }
        return WhatsAppInstance::where('provider', 'cloud')
            ->where('status', 'connected')
            ->where('cloud_business_account_id', $namespace)
            ->first();
    }

    private function requireInstance(Request $request): WhatsAppInstance
    {
        $instance = WhatsAppInstance::where('id', $request->integer('instance_id'))
            ->where('provider', 'cloud')
            ->where('status', 'connected')
            ->first();

        if (!$instance) {
            throw ValidationException::withMessages([
                'instance_id' => 'Pilih instance Cloud yang connected.',
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
