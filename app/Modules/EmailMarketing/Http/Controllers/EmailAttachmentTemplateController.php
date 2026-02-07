<?php

namespace App\Modules\EmailMarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\EmailMarketing\Models\EmailAttachmentTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;

class EmailAttachmentTemplateController extends Controller
{
    public function index(): View
    {
        $templates = EmailAttachmentTemplate::orderBy('name')->get();
        return view('emailmarketing::templates.index', compact('templates'));
    }

    public function create(): View
    {
        $template = new EmailAttachmentTemplate(['filename' => 'attachment.pdf', 'mime' => 'application/pdf']);
        return view('emailmarketing::templates.form', compact('template'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'filename' => ['required', 'string', 'max:255'],
            'mime' => ['required', 'string', 'max:100'],
            'html' => ['required', 'string'],
            'paper_size' => ['required', 'in:A4,A4-landscape,Letter,Letter-landscape'],
        ]);
        $data['created_by'] = $request->user()?->id;
        EmailAttachmentTemplate::create($data);

        return redirect()->route('email-attachment-templates.index')->with('status', 'Template dibuat.');
    }

    public function edit(EmailAttachmentTemplate $emailAttachmentTemplate): View
    {
        $template = $emailAttachmentTemplate;
        return view('emailmarketing::templates.form', compact('template'));
    }

    public function update(Request $request, EmailAttachmentTemplate $emailAttachmentTemplate): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'filename' => ['required', 'string', 'max:255'],
            'mime' => ['required', 'string', 'max:100'],
            'html' => ['required', 'string'],
            'paper_size' => ['required', 'in:A4,A4-landscape,Letter,Letter-landscape'],
        ]);
        $emailAttachmentTemplate->update($data);

        return redirect()->route('email-attachment-templates.index')->with('status', 'Template diperbarui.');
    }

    public function destroy(EmailAttachmentTemplate $emailAttachmentTemplate): RedirectResponse
    {
        $emailAttachmentTemplate->delete();
        return back()->with('status', 'Template dihapus.');
    }
}
