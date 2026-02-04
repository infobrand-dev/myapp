<?php

namespace App\Modules\Contacts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contacts\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        $contacts = Contact::with('company')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('contacts::index', compact('contacts'));
    }

    public function create(): View
    {
        $companies = Contact::query()
            ->where('type', 'company')
            ->orderBy('name')
            ->get();

        return view('contacts::create', compact('companies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['is_active'] = $request->boolean('is_active');
        if ($data['type'] === 'company') {
            $data['company_id'] = null;
        }

        Contact::create($data);

        return redirect()->route('contacts.index')->with('status', 'Contact ditambahkan.');
    }

    public function show(Contact $contact): View
    {
        $contact->load('company', 'employees');

        return view('contacts::show', compact('contact'));
    }

    public function edit(Contact $contact): View
    {
        $companies = Contact::query()
            ->where('type', 'company')
            ->where('id', '!=', $contact->id)
            ->orderBy('name')
            ->get();

        return view('contacts::edit', compact('contact', 'companies'));
    }

    public function update(Request $request, Contact $contact): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['is_active'] = $request->boolean('is_active');
        if ($data['type'] === 'company') {
            $data['company_id'] = null;
        }

        $contact->update($data);

        return redirect()->route('contacts.index')->with('status', 'Contact diperbarui.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        if ($contact->employees()->exists()) {
            return back()->with('status', 'Tidak bisa menghapus perusahaan yang masih memiliki individu.');
        }

        $contact->delete();

        return back()->with('status', 'Contact dihapus.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(['company', 'individual'])],
            'company_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'name' => ['required', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'vat' => ['nullable', 'string', 'max:100'],
            'company_registry' => ['nullable', 'string', 'max:100'],
            'industry' => ['nullable', 'string', 'max:150'],
            'street' => ['nullable', 'string', 'max:255'],
            'street2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:150'],
            'state' => ['nullable', 'string', 'max:150'],
            'zip' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
