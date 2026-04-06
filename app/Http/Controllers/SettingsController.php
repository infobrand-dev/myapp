<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\DocumentSetting;
use App\Models\PlatformInvoice;
use App\Models\TenantByoAiRequest;
use App\Services\AiCreditPricingService;
use App\Models\User;
use App\Support\ByoAiAddon;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\DocumentSettingsResolver;
use App\Support\CurrencySettingsResolver;
use App\Support\ModuleManager;
use App\Support\PlanFeature;
use App\Support\PlanLimit;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use App\Support\UserAccessManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class SettingsController extends Controller
{
    public function show(
        Request $request,
        ModuleManager $modules,
        TenantPlanManager $planManager,
        CurrencySettingsResolver $currencySettings,
        AiCreditPricingService $aiPricing,
        DocumentSettingsResolver $documentSettingsResolver,
        string $section = 'general'
    ): View
    {
        $tenantId = TenantContext::currentId();
        $tenant = TenantContext::currentTenant();
        $currentCompany = CompanyContext::currentCompany();
        $currentBranch = BranchContext::currentBranch();
        $currentCompanyId = $currentCompany ? $currentCompany->id : null;
        $currentBranchId = $currentBranch ? $currentBranch->id : null;

        $userAccessManager = app(UserAccessManager::class);
        $allowedCompanyIds = $userAccessManager->companyIdsFor($request->user());
        $allowedBranchIds = $userAccessManager->branchIdsFor($request->user(), $currentCompanyId);

        $companies = Company::query()
            ->where('tenant_id', $tenantId)
            ->when($allowedCompanyIds, fn ($query) => $query->whereIn('id', $allowedCompanyIds->all()))
            ->withCount([
                'branches',
                'branches as active_branches_count' => fn ($query) => $query->active(),
            ])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $branches = Branch::query()
            ->where('tenant_id', $tenantId)
            ->with('company:id,name')
            ->when($currentCompanyId, fn ($query) => $query->where('company_id', $currentCompanyId))
            ->when($allowedBranchIds, fn ($query) => $query->whereIn('id', $allowedBranchIds->all()))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        $roles = Role::query()
            ->when(
                config('permission.teams'),
                fn ($query) => $query->where(config('permission.column_names.team_foreign_key', 'tenant_id'), $tenantId)
            )
            ->orderBy('name')
            ->get();

        $subscription = $planManager->currentSubscription($tenantId);
        $plan = $subscription ? $subscription->plan : null;
        $billingInvoices = collect();

        if (Schema::hasTable('platform_invoices')) {
            $billingInvoices = PlatformInvoice::query()
                ->with(['plan:id,name,code,billing_interval,meta', 'payments:id,platform_invoice_id,amount,status,paid_at'])
                ->where('tenant_id', $tenantId)
                ->whereNotIn('status', ['paid', 'void'])
                ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('due_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get()
                ->map(function (PlatformInvoice $invoice) {
                    return [
                        'invoice' => $invoice,
                        'public_url' => URL::temporarySignedRoute(
                            'platform.invoices.public',
                            now()->addDays(30),
                            ['invoice' => $invoice->id]
                        ),
                    ];
                });
        }

        $allModules = collect($modules->all());
        $activeModules = $allModules->where('installed', true)->where('active', true)->values();
        $installedModules = $allModules->where('installed', true)->values();
        $featureKeys = collect(array_keys((array) ($plan?->features ?? [])))
            ->merge(array_keys((array) ($subscription?->feature_overrides ?? [])))
            ->unique()
            ->values();
        $availableFeatures = $featureKeys
            ->reject(fn ($key) => (string) $key === PlanFeature::CHATBOT_BYO_AI)
            ->map(fn ($key) => [
                'key' => $key,
                'enabled' => $planManager->hasFeature((string) $key, $tenantId),
            ])
            ->values();

        $limitDefinitions = [
            ['key' => PlanLimit::COMPANIES, 'label' => 'Companies'],
            ['key' => PlanLimit::BRANCHES, 'label' => 'Branches'],
            ['key' => PlanLimit::USERS, 'label' => 'Users'],
            ['key' => PlanLimit::TOTAL_STORAGE_BYTES, 'label' => 'Total Storage'],
            ['key' => PlanLimit::PRODUCTS, 'label' => 'Products'],
            ['key' => PlanLimit::CONTACTS, 'label' => 'Contacts'],
            ['key' => PlanLimit::WHATSAPP_INSTANCES, 'label' => 'WhatsApp Instances'],
            ['key' => PlanLimit::SOCIAL_ACCOUNTS, 'label' => 'Social Accounts'],
            ['key' => PlanLimit::LIVE_CHAT_WIDGETS, 'label' => 'Live Chat Widgets'],
            ['key' => PlanLimit::CHATBOT_ACCOUNTS, 'label' => 'Chatbot Accounts'],
            ['key' => PlanLimit::EMAIL_INBOX_ACCOUNTS, 'label' => 'Email Inbox Accounts'],
            ['key' => PlanLimit::EMAIL_CAMPAIGNS, 'label' => 'Email Campaigns'],
            ['key' => PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY, 'label' => 'WA Blast Recipients / Month'],
            ['key' => PlanLimit::EMAIL_RECIPIENTS_MONTHLY, 'label' => 'Email Recipients / Month'],
            ['key' => PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS, 'label' => 'Chatbot Knowledge Documents'],
        ];

        $limitSummaries = collect($limitDefinitions)
            ->map(function (array $definition) use ($planManager, $tenantId) {
                $state = $planManager->usageState($definition['key'], $tenantId);

                return [
                    'key' => $definition['key'],
                    'label' => $definition['label'],
                    'limit' => $state['limit'],
                    'usage' => $state['usage'],
                    'remaining' => $state['remaining'],
                    'status' => $state['status'],
                    'advice' => $planManager->limitActionAdvice($definition['key'], $state['status'], $tenantId),
                ];
            })
            ->values();

        $editingCompany = null;
        $editingBranch = null;

        if ($section === 'company' && $request->filled('edit')) {
            $editingCompany = Company::query()
                ->where('tenant_id', $tenantId)
                ->find($request->integer('edit'));
        }

        if ($section === 'branch' && $request->filled('edit') && $currentCompanyId) {
            $editingBranch = Branch::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $currentCompanyId)
                ->find($request->integer('edit'));
        }

        $companyDocumentSetting = null;
        $branchDocumentSetting = null;

        if ($currentCompanyId) {
            $companyDocumentSetting = DocumentSetting::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $currentCompanyId)
                ->whereNull('branch_id')
                ->first();
        }

        if ($currentCompanyId && $currentBranchId) {
            $branchDocumentSetting = DocumentSetting::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $currentCompanyId)
                ->where('branch_id', $currentBranchId)
                ->first();
        }

        $documentPreview = $documentSettingsResolver->previewForSettingsPage(
            $tenantId,
            $currentCompanyId,
            $currentBranchId
        );

        return view('settings.index', [
            'currentSection' => $section,
            'sections' => $this->sections(),
            'tenant' => $tenant,
            'currencySettingsLocked' => $this->currencySettingsLocked(),
            'defaultCurrency' => $currencySettings->tenantCurrency($tenantId) ?? $currencySettings->defaultCurrency($tenantId),
            'companyDefaultCurrency' => $currentCompany ? $currencySettings->companyCurrency($tenantId, $currentCompany->id) : null,
            'currencyOptions' => $currencySettings->options(),
            'currentCompany' => $currentCompany,
            'currentBranch' => $currentBranch,
            'companies' => $companies,
            'branches' => $branches,
            'users' => $users,
            'roles' => $roles,
            'subscription' => $subscription,
            'plan' => $plan,
            'billingInvoices' => $billingInvoices,
            'availableFeatures' => $availableFeatures,
            'limitSummaries' => $limitSummaries,
            'allModules' => $allModules,
            'activeModules' => $activeModules,
            'installedModules' => $installedModules,
            'editingCompany' => $editingCompany,
            'editingBranch' => $editingBranch,
            'companyDocumentSetting' => $companyDocumentSetting,
            'branchDocumentSetting' => $branchDocumentSetting,
            'documentPreview' => $documentPreview,
            'settingsStats' => $this->stats($companies, $branches, $users, $activeModules, $currentCompanyId, $currentBranchId),
            'aiCreditPricing' => $aiPricing->snapshot(),
            'byoAiEnabled' => $planManager->hasFeature(PlanFeature::CHATBOT_BYO_AI, $tenantId),
            'byoAiRequest' => TenantByoAiRequest::query()
                ->where('tenant_id', $tenantId)
                ->latest('id')
                ->first(),
            'byoAiProviders' => ByoAiAddon::providers(),
            'byoAiUsageStates' => [
                'accounts' => $planManager->usageState(PlanLimit::BYO_CHATBOT_ACCOUNTS, $tenantId),
                'requests' => $planManager->usageState(PlanLimit::BYO_AI_REQUESTS_MONTHLY, $tenantId),
                'tokens' => $planManager->usageState(PlanLimit::BYO_AI_TOKENS_MONTHLY, $tenantId),
            ],
        ]);
    }

    public function storeCompany(Request $request, TenantPlanManager $planManager): RedirectResponse
    {
        $planManager->ensureWithinLimit(PlanLimit::COMPANIES);
        $data = $this->validateCompany($request);

        Company::create([
            'tenant_id' => TenantContext::currentId(),
            'name' => $data['name'],
            'slug' => $data['slug'],
            'code' => $data['code'] ?: null,
            'is_active' => $request->boolean('is_active'),
            'meta' => [],
        ]);

        return redirect()->route('settings.company')->with('status', 'Company berhasil ditambahkan.');
    }

    public function updateCompany(Request $request, Company $company): RedirectResponse
    {
        $data = $this->validateCompany($request, $company);
        $isActive = $request->boolean('is_active');

        if (!$isActive && $company->is_active && $this->activeCompanyCount() <= 1) {
            throw ValidationException::withMessages([
                'is_active' => 'Tenant harus memiliki minimal satu company aktif.',
            ]);
        }

        $company->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'code' => $data['code'] ?: null,
            'is_active' => $isActive,
        ]);

        return redirect()->route('settings.company')->with('status', 'Company berhasil diperbarui.');
    }

    public function activateCompany(Company $company): RedirectResponse
    {
        $company->update(['is_active' => true]);

        return back()->with('status', 'Company diaktifkan.');
    }

    public function switchCompany(Request $request, Company $company): RedirectResponse
    {
        abort_unless($this->userCanAccessCompany($request->user(), $company->id), 403);

        $request->session()->put('company_id', $company->id);
        $request->session()->put('company_slug', $company->slug);
        $request->session()->forget(['branch_id', 'branch_slug']);

        return back();
    }

    public function storeBranch(Request $request): RedirectResponse
    {
        app(TenantPlanManager::class)->ensureWithinLimit(PlanLimit::BRANCHES);

        $company = $this->requireCurrentCompany();
        $data = $this->validateBranch($request, $company);

        Branch::create([
            'tenant_id' => TenantContext::currentId(),
            'company_id' => $company->id,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'code' => $data['code'] ?: null,
            'is_active' => $request->boolean('is_active'),
            'meta' => [],
        ]);

        return redirect()->route('settings.branch')->with('status', 'Branch berhasil ditambahkan.');
    }

    public function updateBranch(Request $request, Branch $branch): RedirectResponse
    {
        $company = $this->requireCurrentCompany();
        abort_unless($branch->company_id === $company->id, 404);

        $data = $this->validateBranch($request, $company, $branch);

        $branch->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'code' => $data['code'] ?: null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('settings.branch')->with('status', 'Branch berhasil diperbarui.');
    }

    public function activateBranch(Branch $branch): RedirectResponse
    {
        $company = $this->requireCurrentCompany();
        abort_unless($branch->company_id === $company->id, 404);

        $branch->update(['is_active' => true]);

        return back()->with('status', 'Branch diaktifkan.');
    }

    public function switchBranch(Request $request, Branch $branch): RedirectResponse
    {
        $company = $this->requireCurrentCompany();
        abort_unless($branch->company_id === $company->id, 404);
        abort_unless($this->userCanAccessBranch($request->user(), $branch->id, $company->id), 403);

        $request->session()->put('branch_id', $branch->id);
        $request->session()->put('branch_slug', $branch->slug);

        return back();
    }

    public function clearBranch(Request $request): RedirectResponse
    {
        $request->session()->forget(['branch_id', 'branch_slug']);

        return back();
    }

    public function saveDocuments(Request $request): RedirectResponse
    {
        $company = $this->requireCurrentCompany();
        $branch = BranchContext::currentBranch();
        $tenantId = TenantContext::currentId();

        $data = $request->validate([
            'company_invoice_prefix' => ['nullable', 'string', 'max:30'],
            'company_invoice_padding' => ['required', 'integer', 'min:1', 'max:12'],
            'company_invoice_next_number' => ['required', 'integer', 'min:1'],
            'company_invoice_reset_period' => ['nullable', Rule::in(['never', 'monthly', 'yearly'])],
            'company_document_header' => ['nullable', 'string'],
            'company_document_footer' => ['nullable', 'string'],
            'company_receipt_footer' => ['nullable', 'string'],
            'company_notes' => ['nullable', 'string'],
            'branch_invoice_prefix' => ['nullable', 'string', 'max:30'],
            'branch_invoice_padding' => ['nullable', 'integer', 'min:1', 'max:12'],
            'branch_invoice_next_number' => ['nullable', 'integer', 'min:1'],
            'branch_invoice_reset_period' => ['nullable', Rule::in(['never', 'monthly', 'yearly'])],
            'branch_document_header' => ['nullable', 'string'],
            'branch_document_footer' => ['nullable', 'string'],
            'branch_receipt_footer' => ['nullable', 'string'],
            'branch_notes' => ['nullable', 'string'],
        ]);

        DocumentSetting::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'company_id' => $company->id,
                'branch_id' => null,
            ],
            [
                'invoice_prefix' => $data['company_invoice_prefix'] ?: null,
                'invoice_padding' => $data['company_invoice_padding'],
                'invoice_next_number' => $data['company_invoice_next_number'],
                'invoice_reset_period' => $data['company_invoice_reset_period'] ?: 'never',
                'document_header' => $data['company_document_header'] ?: null,
                'document_footer' => $data['company_document_footer'] ?: null,
                'receipt_footer' => $data['company_receipt_footer'] ?: null,
                'notes' => $data['company_notes'] ?: null,
            ]
        );

        if ($branch) {
            DocumentSetting::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                ],
                [
                    'invoice_prefix' => $data['branch_invoice_prefix'] ?: null,
                    'invoice_padding' => $data['branch_invoice_padding'] ?: 5,
                    'invoice_next_number' => $data['branch_invoice_next_number'] ?: 1,
                    'invoice_reset_period' => $data['branch_invoice_reset_period'] ?: 'never',
                    'document_header' => $data['branch_document_header'] ?: null,
                    'document_footer' => $data['branch_document_footer'] ?: null,
                    'receipt_footer' => $data['branch_receipt_footer'] ?: null,
                    'notes' => $data['branch_notes'] ?: null,
                ]
            );
        }

        return redirect()->route('settings.documents')->with('status', 'Document settings berhasil disimpan.');
    }

    public function saveGeneral(Request $request): RedirectResponse
    {
        if ($this->currencySettingsLocked()) {
            return redirect()
                ->route('settings.general')
                ->with('warning', 'Mata uang default dikunci setelah setup awal agar transaksi tetap rapi. Jika benar-benar perlu diubah, lakukan lewat intervensi platform.');
        }

        $tenant = TenantContext::currentTenant();
        abort_unless($tenant, 404);

        $data = $request->validate([
            'workspace_name' => ['required', 'string', 'max:255'],
            'default_currency' => ['required', Rule::in(array_keys(app(CurrencySettingsResolver::class)->options()))],
            'company_default_currency' => ['nullable', Rule::in(array_keys(app(CurrencySettingsResolver::class)->options()))],
        ]);

        $tenant->update([
            'name' => trim((string) $data['workspace_name']),
        ]);

        $tenantMeta = $tenant->meta ?? [];
        $tenantMeta['default_currency'] = strtoupper((string) $data['default_currency']);
        $tenant->update([
            'meta' => $tenantMeta,
        ]);

        if ($company = CompanyContext::currentCompany()) {
            $companyMeta = $company->meta ?? [];
            $companyCurrency = strtoupper((string) ($data['company_default_currency'] ?? ''));

            if ($companyCurrency === '') {
                unset($companyMeta['default_currency']);
            } else {
                $companyMeta['default_currency'] = $companyCurrency;
            }

            $company->update([
                'meta' => $companyMeta,
            ]);
        }

        return redirect()->route('settings.general')->with('status', 'General settings berhasil disimpan.');
    }

    public function requestByoAi(Request $request): RedirectResponse
    {
        $tenantId = TenantContext::currentId();
        $user = $request->user();

        $data = $request->validate([
            'preferred_provider' => ['required', Rule::in(ByoAiAddon::providers())],
            'intended_volume' => ['required', 'string', 'max:100'],
            'chatbot_account_count' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'channel_count' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'technical_contact_name' => ['nullable', 'string', 'max:255'],
            'technical_contact_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $latest = TenantByoAiRequest::query()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->first();

        if ($latest && $latest->status === ByoAiAddon::REQUEST_STATUS_PENDING) {
            return redirect()->route('settings.addons')->with('status', 'Permintaan BYO AI Anda masih menunggu review platform.');
        }

        TenantByoAiRequest::query()->create([
            'tenant_id' => $tenantId,
            'requested_by' => $user?->id,
            'status' => ByoAiAddon::REQUEST_STATUS_PENDING,
            'preferred_provider' => $data['preferred_provider'],
            'intended_volume' => $data['intended_volume'],
            'chatbot_account_count' => $data['chatbot_account_count'] ?? null,
            'channel_count' => $data['channel_count'] ?? null,
            'technical_contact_name' => $data['technical_contact_name'] ?: ($user?->name ?: null),
            'technical_contact_email' => $data['technical_contact_email'] ?: ($user?->email ?: null),
            'notes' => $data['notes'] ?: null,
            'meta' => [
                'requested_from' => 'settings_addons',
            ],
        ]);

        return redirect()->route('settings.addons')->with('status', 'Permintaan add-on BYO AI berhasil dikirim. Tim kami akan meninjau kebutuhan Anda dan menghubungi Anda jika tenant memenuhi syarat aktivasi.');
    }

    private function validateCompany(Request $request, ?Company $company = null): array
    {
        $tenantId = TenantContext::currentId();
        $normalizedSlug = $this->normalizeSlug($request->input('slug'), (string) $request->input('name'));
        $request->merge(['slug' => $normalizedSlug]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('companies', 'slug')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($company ? $company->id : null),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('companies', 'code')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($company ? $company->id : null),
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return $data;
    }

    private function validateBranch(Request $request, Company $company, ?Branch $branch = null): array
    {
        $tenantId = TenantContext::currentId();
        $normalizedSlug = $this->normalizeSlug($request->input('slug'), (string) $request->input('name'));
        $request->merge(['slug' => $normalizedSlug]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('branches', 'slug')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('company_id', $company->id))
                    ->ignore($branch ? $branch->id : null),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('branches', 'code')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('company_id', $company->id))
                    ->ignore($branch ? $branch->id : null),
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return $data;
    }

    private function normalizeSlug(?string $slug, string $fallback): string
    {
        return Str::slug(trim((string) ($slug ?: $fallback)));
    }

    private function requireCurrentCompany(): Company
    {
        $company = CompanyContext::currentCompany();

        if ($company) {
            return $company;
        }

        throw ValidationException::withMessages([
            'company' => 'Pilih atau buat company aktif terlebih dahulu.',
        ]);
    }

    private function activeCompanyCount(): int
    {
        return Company::query()
            ->where('tenant_id', TenantContext::currentId())
            ->active()
            ->count();
    }

    private function userCanAccessCompany(?User $user, int $companyId): bool
    {
        $allowedCompanyIds = app(UserAccessManager::class)->companyIdsFor($user);

        return $allowedCompanyIds === null || $allowedCompanyIds->contains($companyId);
    }

    private function userCanAccessBranch(?User $user, int $branchId, int $companyId): bool
    {
        $allowedBranchIds = app(UserAccessManager::class)->branchIdsFor($user, $companyId);

        return $allowedBranchIds === null || $allowedBranchIds->contains($branchId);
    }

    private function sections(): array
    {
        return [
            'general' => [
                'label' => 'General',
                'route' => 'settings.general',
                'icon' => 'ti ti-settings',
                'description' => 'Profil workspace, konteks aktif, dan ringkasan tenant.',
            ],
            'company' => [
                'label' => 'Company',
                'route' => 'settings.company',
                'icon' => 'ti ti-building',
                'description' => 'Entitas bisnis internal di bawah tenant.',
            ],
            'branch' => [
                'label' => 'Branch',
                'route' => 'settings.branch',
                'icon' => 'ti ti-building-store',
                'description' => 'Outlet atau lokasi operasional di bawah company aktif.',
            ],
            'documents' => [
                'label' => 'Documents',
                'route' => 'settings.documents',
                'icon' => 'ti ti-file-description',
                'description' => 'Pengaturan invoice, receipt, dan numbering.',
            ],
            'subscription' => [
                'label' => 'Subscription',
                'route' => 'settings.subscription',
                'icon' => 'ti ti-credit-card',
                'description' => 'Plan aktif, fitur, dan quota tenant.',
            ],
            'addons' => [
                'label' => 'Add-ons',
                'route' => 'settings.addons',
                'icon' => 'ti ti-puzzle-2',
                'description' => 'Add-on premium, request fitur tambahan, dan status review.',
            ],
            'access' => [
                'label' => 'Users & Access',
                'route' => 'settings.access',
                'icon' => 'ti ti-shield-lock',
                'description' => 'User tenant dan role yang sedang aktif.',
            ],
            'modules' => [
                'label' => 'Modules',
                'route' => 'settings.modules',
                'icon' => 'ti ti-layout-grid',
                'description' => 'Ringkasan module aktif dan arah entitlement tenant.',
            ],
        ];
    }

    private function currencySettingsLocked(): bool
    {
        return true;
    }

    private function stats(
        Collection $companies,
        Collection $branches,
        Collection $users,
        Collection $activeModules,
        ?int $currentCompanyId,
        ?int $currentBranchId
    ): array {
        return [
            [
                'label' => 'Companies',
                'value' => $companies->count(),
                'meta' => $currentCompanyId ? 'Active company selected' : 'No active company',
            ],
            [
                'label' => 'Branches',
                'value' => $branches->count(),
                'meta' => $currentBranchId ? 'Active branch selected' : 'Branch scope optional',
            ],
            [
                'label' => 'Users',
                'value' => $users->count(),
                'meta' => 'Tenant-scoped accounts',
            ],
            [
                'label' => 'Active Modules',
                'value' => $activeModules->count(),
                'meta' => 'Installed and active',
            ],
        ];
    }
}
