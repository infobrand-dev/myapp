<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppApi\Models\WATemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WATemplateController extends Controller
{
    public function index(): View
    {
        $templates = WATemplate::orderBy('name')->paginate(20);
        return view('whatsappapi::templates.index', compact('templates'));
    }

    public function create(): View
    {
        $template = new WATemplate(['language' => 'en', 'status' => 'active', 'category' => 'utility']);
        return view('whatsappapi::templates.form', compact('template'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['components'] = $this->buildComponents($request);
        WATemplate::create($data);
        return redirect()->route('whatsapp-api.templates.index')->with('status', 'Template dibuat.');
    }

    public function edit(WATemplate $template): View
    {
        return view('whatsappapi::templates.form', compact('template'));
    }

    public function update(Request $request, WATemplate $template): RedirectResponse
    {
        $data = $this->validated($request);
        $data['components'] = $this->buildComponents($request);
        $template->update($data);
        return redirect()->route('whatsapp-api.templates.index')->with('status', 'Template diperbarui.');
    }

    public function destroy(WATemplate $template): RedirectResponse
    {
        $template->delete();
        return back()->with('status', 'Template dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'language' => ['required', 'regex:/^[a-z]{2}(?:_[A-Z]{2})?$/'],
            'category' => ['required', 'in:utility,marketing,authentication'],
            'namespace' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1024'],
            'status' => ['required', 'in:active,inactive'],
            'header_type' => ['nullable', 'in:none,text,image,document,video'],
            'header_text' => ['nullable', 'string', 'max:60'],

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
            $components[] = [
                'type' => 'header',
                'format' => strtoupper($headerType),
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
}
