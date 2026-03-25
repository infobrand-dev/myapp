<?php

namespace App\Modules\Contacts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contacts\Http\Requests\BulkDestroyContactRequest;
use App\Modules\Contacts\Http\Requests\ImportContactRequest;
use App\Modules\Contacts\Http\Requests\MergeContactRequest;
use App\Modules\Contacts\Http\Requests\StoreContactRequest;
use App\Modules\Contacts\Http\Requests\UpdateContactRequest;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Contacts\Support\ContactPhoneNormalizer;
use App\Modules\Contacts\Support\ContactScope;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use ZipArchive;
use SimpleXMLElement;

class ContactController extends Controller
{
    public function index(): View
    {
        $contacts = ContactScope::applyVisibilityScope(Contact::query())
            ->with(['parentContact', 'company', 'branch'])
            ->orderByDesc('created_at')
            ->paginate(15);

        $mergeCandidateCount = count($this->buildMergeCandidateGroups());

        return view('contacts::index', compact('contacts', 'mergeCandidateCount'));
    }

    public function mergeCandidates(): View
    {
        $groups = $this->buildMergeCandidateGroups();

        return view('contacts::merge-candidates', compact('groups'));
    }

    public function importPage(): View
    {
        return view('contacts::import');
    }

    public function downloadTemplate(string $format)
    {
        $headers = $this->importHeaders();
        $sampleRow = $this->sampleImportRow();

        if ($format === 'csv') {
            $callback = function () use ($headers, $sampleRow) {
                $stream = fopen('php://output', 'w');
                fputcsv($stream, $headers);
                fputcsv($stream, $sampleRow);
                fclose($stream);
            };

            return response()->streamDownload($callback, 'contacts-import-template.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        if ($format === 'xlsx') {
            $binary = $this->buildTemplateXlsx([$headers, $sampleRow]);

            return response($binary, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="contacts-import-template.xlsx"',
                'Content-Length' => (string) strlen($binary),
            ]);
        }

        abort(404);
    }

    public function import(ImportContactRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $file = $data['import_file'];
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $rows = $extension === 'xlsx'
            ? $this->parseXlsxFile($file->getRealPath())
            : $this->parseCsvFile($file->getRealPath());

        if (count($rows) < 2) {
            throw ValidationException::withMessages([
                'import_file' => 'File import harus berisi header dan minimal satu baris data.',
            ]);
        }

        [$headerMap, $recognizedColumns] = $this->resolveImportHeaders($rows[0]);
        if (empty($recognizedColumns) || !in_array('name', $recognizedColumns, true)) {
            throw ValidationException::withMessages([
                'import_file' => 'Header tidak dikenali. Gunakan template download atau sertakan minimal kolom "name".',
            ]);
        }

        $result = DB::transaction(function () use ($rows, $headerMap) {
            $created = 0;
            $skipped = [];

            foreach (array_slice($rows, 1) as $index => $rawRow) {
                if ($this->rowIsEmpty($rawRow)) {
                    continue;
                }

                $rowNumber = $index + 2;
                $payload = $this->mapImportedRow($rawRow, $headerMap);
                $normalized = $this->normalizeImportedContact($payload);

                if (empty($normalized['name'])) {
                    $skipped[] = "Baris {$rowNumber}: nama wajib diisi.";
                    continue;
                }

                try {
                    $companyId = $this->resolveImportedCompanyId($normalized);
                    if ($normalized['type'] === 'company') {
                        $companyId = null;
                    }

                    Contact::create([
                        'tenant_id' => $this->tenantId(),
                        'company_id' => $normalized['company_id'],
                        'branch_id' => $normalized['branch_id'],
                        'type' => $normalized['type'],
                        'parent_contact_id' => $companyId,
                        'name' => $normalized['name'],
                        'job_title' => $normalized['job_title'],
                        'email' => $normalized['email'],
                        'phone' => $normalized['phone'],
                        'mobile' => $normalized['mobile'],
                        'website' => $normalized['website'],
                        'vat' => $normalized['vat'],
                        'company_registry' => $normalized['company_registry'],
                        'industry' => $normalized['industry'],
                        'street' => $normalized['street'],
                        'street2' => $normalized['street2'],
                        'city' => $normalized['city'],
                        'state' => $normalized['state'],
                        'zip' => $normalized['zip'],
                        'country' => $normalized['country'],
                        'notes' => $normalized['notes'],
                        'is_active' => $normalized['is_active'],
                    ]);

                    $created++;
                } catch (\Throwable $e) {
                    $skipped[] = "Baris {$rowNumber}: {$e->getMessage()}";
                }
            }

            return compact('created', 'skipped');
        });

        $message = "Import selesai. {$result['created']} contact ditambahkan.";
        if (!empty($result['skipped'])) {
            $message .= ' ' . count($result['skipped']) . ' baris dilewati.';
        }

        return redirect()
            ->route('contacts.import-page')
            ->with('status', $message)
            ->with('import_skipped', $result['skipped']);
    }

    public function create(): View
    {
        $prefill = new Contact([
            'type' => request()->input('type', 'individual'),
            'scope' => request()->input('scope', ContactScope::LEVEL_COMPANY),
            'name' => request()->input('name', ''),
            'phone' => request()->input('phone', ''),
            'mobile' => request()->input('mobile', ''),
            'email' => request()->input('email', ''),
            'notes' => request()->input('notes', ''),
            'is_active' => true,
        ]);

        $companies = Contact::query()
            ->where('tenant_id', $this->tenantId())
            ->where('type', 'company')
            ->whereNull('parent_contact_id')
            ->tap(fn ($query) => ContactScope::applyVisibilityScope($query))
            ->orderBy('name')
            ->get();

        return view('contacts::create', [
            'companies' => $companies,
            'contact' => $prefill,
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['is_active'] = $request->boolean('is_active');
        if ($data['type'] === 'company') {
            $data['parent_contact_id'] = null;
        }
        $data = ContactScope::applyWriteScope($data, $data['scope'] ?? null);
        $data['tenant_id'] = $this->tenantId();
        unset($data['scope']);

        Contact::create($data);

        return redirect()->route('contacts.index')->with('status', 'Contact ditambahkan.');
    }

    public function show(Contact $contact): View
    {
        $contact->load(['parentContact', 'employees', 'company', 'branch']);

        return view('contacts::show', compact('contact'));
    }

    public function edit(Contact $contact): View
    {
        $companies = Contact::query()
            ->where('tenant_id', $this->tenantId())
            ->where('type', 'company')
            ->whereNull('parent_contact_id')
            ->where('id', '!=', $contact->id)
            ->tap(fn ($query) => ContactScope::applyVisibilityScope($query))
            ->orderBy('name')
            ->get();

        return view('contacts::edit', compact('contact', 'companies'));
    }

    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['is_active'] = $request->boolean('is_active');
        if ($data['type'] === 'company') {
            $data['parent_contact_id'] = null;
        }
        $data = ContactScope::applyWriteScope($data, $data['scope'] ?? null);
        unset($data['scope']);

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

    public function bulkDestroy(BulkDestroyContactRequest $request): RedirectResponse
    {
        $ids = $request->validated()['ids'];

        $deleted = 0;
        foreach ($ids as $id) {
            $contact = ContactScope::applyVisibilityScope(Contact::query())->find($id);
            if ($contact && !$contact->employees()->exists()) {
                $contact->delete();
                $deleted++;
            }
        }

        return redirect()->route('contacts.index')->with('status', "{$deleted} contact berhasil dihapus.");
    }

    public function merge(MergeContactRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (!ContactScope::applyVisibilityScope(Contact::query())->find($data['primary_id'])) {
            throw ValidationException::withMessages([
                'primary_id' => 'Contact utama tidak tersedia untuk tenant aktif.',
            ]);
        }

        foreach ((array) $data['duplicate_ids'] as $index => $duplicateId) {
            if (!ContactScope::applyVisibilityScope(Contact::query())->find($duplicateId)) {
                throw ValidationException::withMessages([
                    "duplicate_ids.$index" => 'Contact duplikat tidak tersedia untuk tenant aktif.',
                ]);
            }
        }

        $primaryId = (int) $data['primary_id'];
        $duplicateIds = collect($data['duplicate_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id !== $primaryId)
            ->values();

        if ($duplicateIds->isEmpty()) {
            throw ValidationException::withMessages([
                'duplicate_ids' => 'Pilih minimal satu contact lain untuk digabungkan.',
            ]);
        }

        $allIds = $duplicateIds
            ->concat([$primaryId])
            ->unique()
            ->values();

        $contacts = ContactScope::applyVisibilityScope(Contact::query())
            ->whereIn('id', $allIds->all())
            ->get()
            ->keyBy('id');

        $primary = $contacts->get($primaryId);
        if (!$primary) {
            throw ValidationException::withMessages([
                'primary_id' => 'Contact utama tidak ditemukan.',
            ]);
        }

        $duplicates = $duplicateIds
            ->map(fn ($id) => $contacts->get($id))
            ->filter();

        if ($duplicates->count() !== $duplicateIds->count()) {
            throw ValidationException::withMessages([
                'duplicate_ids' => 'Salah satu contact duplikat tidak ditemukan.',
            ]);
        }

        $types = $duplicates->pluck('type')->push($primary->type)->unique()->values();
        if ($types->count() > 1) {
            throw ValidationException::withMessages([
                'duplicate_ids' => 'Merge hanya bisa dilakukan untuk contact dengan tipe yang sama.',
            ]);
        }

        DB::transaction(function () use ($primary, $duplicates): void {
            $this->mergeContactsIntoPrimary($primary, $duplicates);
        });

        return redirect()
            ->route('contacts.merge-candidates')
            ->with('status', 'Contact berhasil digabungkan.');
    }

    private function validatedData(Request $request): array
    {
        // Validation is already performed by StoreContactRequest / UpdateContactRequest
        $data = $request->validated();

        $data = $this->normalizeValidatedContactData($data);

        if (!empty($data['parent_contact_id'])) {
            ContactScope::applyVisibilityScope(
                Contact::query()->where('type', 'company')
            )->findOrFail((int) $data['parent_contact_id']);
        }

        return $data;
    }

    private function importHeaders(): array
    {
        return [
            'type',
            'scope',
            'name',
            'company_name',
            'job_title',
            'email',
            'phone',
            'mobile',
            'website',
            'vat',
            'company_registry',
            'industry',
            'street',
            'street2',
            'city',
            'state',
            'zip',
            'country',
            'notes',
            'is_active',
        ];
    }

    private function sampleImportRow(): array
    {
        return [
            'individual',
            'company',
            'Budi Santoso',
            'PT Contoh Sukses',
            'Event Coordinator',
            'budi@example.com',
            '0215551234',
            '628123456789',
            'https://contoh.co.id',
            '',
            '',
            'Event Organizer',
            'Jl. Sudirman No. 10',
            'Lantai 5',
            'Jakarta',
            'DKI Jakarta',
            '10220',
            'Indonesia',
            'Prospek event 2026',
            '1',
        ];
    }

    private function parseCsvFile(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'import_file' => 'File CSV tidak bisa dibaca.',
            ]);
        }

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);
        }

        fclose($handle);

        return $rows;
    }

    private function parseXlsxFile(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages([
                'import_file' => 'File XLSX tidak bisa dibuka.',
            ]);
        }

        $sharedStrings = $this->readXlsxSharedStrings($zip);
        $sheetPath = $this->resolveFirstWorksheetPath($zip);
        $sheetXml = $zip->getFromName($sheetPath);
        $zip->close();

        if ($sheetXml === false) {
            throw ValidationException::withMessages([
                'import_file' => 'Worksheet XLSX tidak ditemukan.',
            ]);
        }

        $xml = simplexml_load_string($sheetXml);
        if (!$xml instanceof SimpleXMLElement) {
            throw ValidationException::withMessages([
                'import_file' => 'Worksheet XLSX tidak valid.',
            ]);
        }

        $xml->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = [];

        foreach ($xml->xpath('//main:sheetData/main:row') ?: [] as $rowNode) {
            $row = [];
            foreach ($rowNode->c as $cell) {
                $ref = (string) $cell['r'];
                $columnLetters = preg_replace('/\d+/', '', $ref);
                $columnIndex = $this->columnLettersToIndex($columnLetters);
                $row[$columnIndex] = $this->extractXlsxCellValue($cell, $sharedStrings);
            }

            if (!empty($row)) {
                ksort($row);
                $maxIndex = max(array_keys($row));
                $normalizedRow = [];
                for ($i = 0; $i <= $maxIndex; $i++) {
                    $normalizedRow[] = $row[$i] ?? '';
                }
                $rows[] = $normalizedRow;
            }
        }

        return $rows;
    }

    private function readXlsxSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $shared = simplexml_load_string($xml);
        if (!$shared instanceof SimpleXMLElement) {
            return [];
        }

        $shared->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        return collect($shared->xpath('//main:si') ?: [])
            ->map(function ($item) {
                $parts = $item->xpath('.//main:t') ?: [];
                return collect($parts)->map(fn ($node) => (string) $node)->implode('');
            })
            ->all();
    }

    private function resolveFirstWorksheetPath(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = simplexml_load_string($workbookXml);
        $rels = simplexml_load_string($relsXml);

        if (!$workbook instanceof SimpleXMLElement || !$rels instanceof SimpleXMLElement) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $rels->registerXPathNamespace('rel', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $sheet = ($workbook->xpath('//main:sheets/main:sheet[1]') ?: [])[0] ?? null;
        if (!$sheet) {
            return 'xl/worksheets/sheet1.xml';
        }

        $relationshipId = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
        foreach ($rels->xpath('//rel:Relationship') ?: [] as $relationship) {
            if ((string) $relationship['Id'] === $relationshipId) {
                return 'xl/' . ltrim((string) $relationship['Target'], '/');
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function extractXlsxCellValue(SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];
        if ($type === 'inlineStr') {
            return trim((string) ($cell->is->t ?? ''));
        }

        $value = trim((string) ($cell->v ?? ''));
        if ($type === 's') {
            return trim((string) ($sharedStrings[(int) $value] ?? ''));
        }

        if ($type === 'b') {
            return $value === '1' ? '1' : '0';
        }

        return $value;
    }

    private function columnLettersToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return max(0, $index - 1);
    }

    private function resolveImportHeaders(array $headerRow): array
    {
        $aliases = $this->headerAliases();
        $headerMap = [];
        $recognized = [];

        foreach ($headerRow as $index => $header) {
            $normalized = $this->normalizeHeader((string) $header);
            $canonical = $aliases[$normalized] ?? null;
            if ($canonical) {
                $headerMap[$index] = $canonical;
                $recognized[] = $canonical;
            }
        }

        return [$headerMap, array_values(array_unique($recognized))];
    }

    private function headerAliases(): array
    {
        return [
            'type' => 'type',
            'jenis' => 'type',
            'contacttype' => 'type',
            'scope' => 'scope',
            'cakupan' => 'scope',
            'scopelevel' => 'scope',
            'name' => 'name',
            'nama' => 'name',
            'fullname' => 'name',
            'company' => 'company_name',
            'companyname' => 'company_name',
            'namaperusahaan' => 'company_name',
            'companynama' => 'company_name',
            'jobtitle' => 'job_title',
            'jabatan' => 'job_title',
            'email' => 'email',
            'phone' => 'phone',
            'telepon' => 'phone',
            'telephone' => 'phone',
            'mobile' => 'mobile',
            'hp' => 'mobile',
            'nohp' => 'mobile',
            'nomorhp' => 'mobile',
            'website' => 'website',
            'vat' => 'vat',
            'companyregistry' => 'company_registry',
            'nib' => 'company_registry',
            'industry' => 'industry',
            'industri' => 'industry',
            'street' => 'street',
            'address' => 'street',
            'alamat' => 'street',
            'street2' => 'street2',
            'address2' => 'street2',
            'alamat2' => 'street2',
            'city' => 'city',
            'kota' => 'city',
            'state' => 'state',
            'provinsi' => 'state',
            'zip' => 'zip',
            'zipcode' => 'zip',
            'postalcode' => 'zip',
            'kodepos' => 'zip',
            'country' => 'country',
            'negara' => 'country',
            'notes' => 'notes',
            'catatan' => 'notes',
            'isactive' => 'is_active',
            'active' => 'is_active',
            'aktif' => 'is_active',
        ];
    }

    private function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));

        return (string) preg_replace('/[^a-z0-9]+/', '', $header);
    }

    private function mapImportedRow(array $row, array $headerMap): array
    {
        $payload = [];
        foreach ($row as $index => $value) {
            $field = $headerMap[$index] ?? null;
            if ($field === null) {
                continue;
            }
            $payload[$field] = is_string($value) ? trim($value) : $value;
        }

        return $payload;
    }

    private function normalizeImportedContact(array $payload): array
    {
        $type = strtolower(trim((string) ($payload['type'] ?? 'individual')));
        if (!in_array($type, ['company', 'individual'], true)) {
            $type = 'individual';
        }

        return $this->normalizeValidatedContactData([
            'type' => $type,
            'scope' => strtolower(trim((string) ($payload['scope'] ?? ContactScope::LEVEL_COMPANY))),
            'name' => trim((string) ($payload['name'] ?? '')),
            'company_name' => trim((string) ($payload['company_name'] ?? '')),
            'job_title' => $this->nullableString($payload['job_title'] ?? null),
            'email' => $this->nullableString($payload['email'] ?? null),
            'phone' => $this->nullableString($payload['phone'] ?? null),
            'mobile' => $this->nullableString($payload['mobile'] ?? null),
            'website' => $this->nullableString($payload['website'] ?? null),
            'vat' => $this->nullableString($payload['vat'] ?? null),
            'company_registry' => $this->nullableString($payload['company_registry'] ?? null),
            'industry' => $this->nullableString($payload['industry'] ?? null),
            'street' => $this->nullableString($payload['street'] ?? null),
            'street2' => $this->nullableString($payload['street2'] ?? null),
            'city' => $this->nullableString($payload['city'] ?? null),
            'state' => $this->nullableString($payload['state'] ?? null),
            'zip' => $this->nullableString($payload['zip'] ?? null),
            'country' => $this->nullableString($payload['country'] ?? null),
            'notes' => $this->nullableString($payload['notes'] ?? null),
            'is_active' => $this->normalizeBoolean($payload['is_active'] ?? '1'),
        ], false);
    }

    private function resolveImportedCompanyId(array $normalized): ?int
    {
        $companyName = $normalized['company_name'] ?? null;
        if (!$companyName) {
            return null;
        }

        $company = Contact::query()
            ->where('tenant_id', $this->tenantId())
            ->where('type', 'company')
            ->whereNull('parent_contact_id')
            ->tap(fn ($query) => ContactScope::applyVisibilityScope($query))
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($companyName)])
            ->first();

        if ($company) {
            return (int) $company->id;
        }

        $company = Contact::create([
            'tenant_id' => $this->tenantId(),
            'company_id' => $normalized['company_id'] ?? CompanyContext::currentId(),
            'branch_id' => $normalized['branch_id'] ?? BranchContext::currentId(),
            'type' => 'company',
            'parent_contact_id' => null,
            'name' => $companyName,
            'is_active' => true,
        ]);

        return (int) $company->id;
    }

    private function rowIsEmpty(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeBoolean($value): bool
    {
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'yes', 'y', 'aktif', 'active'], true);
    }

    private function normalizeValidatedContactData(array $data, bool $strict = true): array
    {
        $data = ContactScope::applyWriteScope($data, $data['scope'] ?? null);

        foreach (['phone', 'mobile'] as $field) {
            $original = $this->nullableString($data[$field] ?? null);
            $normalized = ContactPhoneNormalizer::normalize($original);

            if ($strict && $original !== null && $normalized === null) {
                throw ValidationException::withMessages([
                    $field => 'Nomor harus valid untuk telepon/WhatsApp. Gunakan format internasional seperti 628123456789 atau +628123456789.',
                ]);
            }

            $data[$field] = $normalized;
        }

        return $data;
    }

    private function buildMergeCandidateGroups(): array
    {
        $contacts = ContactScope::applyVisibilityScope(Contact::query())
            ->with('parentContact')
            ->orderBy('name')
            ->get();

        $signatureMap = [];

        foreach ($contacts as $contact) {
            foreach ($this->contactMatchSignatures($contact) as $signature) {
                $key = $signature['type'] . ':' . $signature['value'];
                if (!isset($signatureMap[$key])) {
                    $signatureMap[$key] = [
                        'match_type' => $signature['type'],
                        'match_label' => $signature['label'],
                        'match_value' => $signature['value'],
                        'contact_ids' => [],
                    ];
                }

                $signatureMap[$key]['contact_ids'][] = $contact->id;
            }
        }

        $groups = collect($signatureMap)
            ->map(function (array $group) use ($contacts): ?array {
                $contactIds = collect($group['contact_ids'])
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($contactIds->count() < 2) {
                    return null;
                }

                $matchedContacts = $contacts
                    ->whereIn('id', $contactIds->all())
                    ->sortBy('id')
                    ->values();

                return [
                    'match_type' => $group['match_type'],
                    'match_label' => $group['match_label'],
                    'match_value' => $group['match_value'],
                    'contacts' => $matchedContacts,
                    'default_primary_id' => (int) $matchedContacts->first()->id,
                ];
            })
            ->filter()
            ->sortBy(fn (array $group) => $group['match_type'] . '|' . $group['match_value'])
            ->values()
            ->all();

        return $groups;
    }

    private function contactMatchSignatures(Contact $contact): array
    {
        $signatures = [];

        foreach (collect([$contact->phone, $contact->mobile])->filter()->unique()->values() as $phone) {
            $signatures[] = [
                'type' => 'phone',
                'label' => 'Nomor',
                'value' => (string) $phone,
            ];
        }

        $email = mb_strtolower(trim((string) ($contact->email ?? '')));
        if ($email !== '') {
            $signatures[] = [
                'type' => 'email',
                'label' => 'Email',
                'value' => $email,
            ];
        }

        return $signatures;
    }

    private function mergeContactsIntoPrimary(Contact $primary, Collection $duplicates): void
    {
        $duplicates = $duplicates
            ->filter(fn ($contact) => $contact instanceof Contact && (int) $contact->id !== (int) $primary->id)
            ->sortBy('id')
            ->values();

        if ($duplicates->isEmpty()) {
            return;
        }

        $fillableFields = [
            'company_id',
            'branch_id',
            'parent_contact_id',
            'job_title',
            'email',
            'phone',
            'mobile',
            'website',
            'vat',
            'company_registry',
            'industry',
            'street',
            'street2',
            'city',
            'state',
            'zip',
            'country',
        ];

        foreach ($fillableFields as $field) {
            if (!blank($primary->{$field})) {
                continue;
            }

            foreach ($duplicates as $duplicate) {
                if (blank($duplicate->{$field})) {
                    continue;
                }

                $value = $duplicate->{$field};
                if ($field === 'parent_contact_id' && (int) $value === (int) $primary->id) {
                    continue;
                }

                $primary->{$field} = $value;
                break;
            }
        }

        $notes = collect([$primary->notes])
            ->merge($duplicates->pluck('notes'))
            ->filter(fn ($note) => !blank($note))
            ->map(fn ($note) => trim((string) $note))
            ->unique()
            ->values();

        $primary->notes = $notes->isEmpty() ? null : $notes->implode("\n\n---\n\n");
        $primary->is_active = $primary->is_active || $duplicates->contains(fn (Contact $contact) => (bool) $contact->is_active);

        $duplicateIds = $duplicates->pluck('id')->map(fn ($id) => (int) $id)->all();

        Contact::query()
            ->where('tenant_id', $this->tenantId())
            ->whereIn('parent_contact_id', $duplicateIds)
            ->where('id', '!=', $primary->id)
            ->update(['parent_contact_id' => $primary->id]);

        if ((int) ($primary->parent_contact_id ?? 0) !== 0 && in_array((int) $primary->parent_contact_id, $duplicateIds, true)) {
            $primary->parent_contact_id = null;
        }

        if (Schema::hasTable('email_campaign_recipients')) {
            DB::table('email_campaign_recipients')
                ->whereIn('contact_id', $duplicateIds)
                ->update(['contact_id' => $primary->id]);
        }

        $primary->save();

        Contact::query()
            ->where('tenant_id', $this->tenantId())
            ->whereIn('id', $duplicateIds)
            ->delete();
    }

    private function tenantId(): int
    {
        return TenantContext::currentId();
    }

    private function buildTemplateXlsx(array $rows): string
    {
        $sheetXml = $this->buildXlsxWorksheetXml($rows);
        $tempPath = tempnam(sys_get_temp_dir(), 'contacts_tpl_');
        $zip = new ZipArchive();
        $zip->open($tempPath, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypesXml());
        $zip->addFromString('_rels/.rels', $this->xlsxRootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->xlsxStylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        $binary = file_get_contents($tempPath) ?: '';
        @unlink($tempPath);

        return $binary;
    }

    private function buildXlsxWorksheetXml(array $rows): string
    {
        $xmlRows = [];
        foreach ($rows as $rowIndex => $row) {
            $cells = [];
            foreach (array_values($row) as $colIndex => $value) {
                $cellRef = $this->columnIndexToLetters($colIndex) . ($rowIndex + 1);
                $safeValue = htmlspecialchars((string) $value, ENT_XML1);
                $cells[] = "<c r=\"{$cellRef}\" t=\"inlineStr\"><is><t>{$safeValue}</t></is></c>";
            }
            $rowNumber = $rowIndex + 1;
            $xmlRows[] = "<row r=\"{$rowNumber}\">" . implode('', $cells) . '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . implode('', $xmlRows) . '</sheetData>'
            . '</worksheet>';
    }

    private function columnIndexToLetters(int $index): string
    {
        $index++;
        $letters = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $index = intdiv($index - 1, 26);
        }

        return $letters;
    }

    private function xlsxContentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private function xlsxRootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function xlsxWorkbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Contacts" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function xlsxWorkbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function xlsxStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }
}
