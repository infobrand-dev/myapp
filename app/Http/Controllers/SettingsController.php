<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\DocumentNumberingRule;
use App\Models\DocumentSetting;
use App\Models\DocumentWorkflowRule;
use App\Models\PlatformInvoice;
use App\Models\TenantPaymentGateway;
use App\Models\TenantShippingProvider;
use App\Models\TenantTransactionalMailSetting;
use App\Models\TenantByoAiRequest;
use App\Services\AiCreditPricingService;
use App\Services\AccountingTransactionalMailService;
use App\Services\TenantTransactionalMailConfigResolver;
use App\Services\TenantTransactionalMailerFactory;
use App\Mail\TenantTransactionalTestMail;
use App\Models\User;
use App\Services\StoredFileService;
use App\Services\WorkspaceMediaStorageService;
use App\Support\ByoAiAddon;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\DocumentNumberingService;
use App\Support\DocumentSettingsResolver;
use App\Support\DocumentWorkflowService;
use App\Support\CurrencySettingsResolver;
use App\Support\ModuleManager;
use App\Support\Payments\PaymentGatewayManager;
use App\Support\PlanFeature;
use App\Support\PlanLimit;
use App\Support\Shipping\ShippingProviderManager;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use App\Support\UserAccessManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
        PaymentGatewayManager $paymentGateways,
        ShippingProviderManager $shippingProviders,
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
        $featureKeys = collect(array_keys((array) ($plan ? $plan->features : [])))
            ->merge(array_keys((array) ($subscription ? $subscription->feature_overrides : [])))
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
        $transactionalMailSetting = TenantTransactionalMailSetting::query()
            ->where('tenant_id', $tenantId)
            ->first();

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
            'transactionalMailSetting' => $transactionalMailSetting,
            'transactionalMailLogs' => app(AccountingTransactionalMailService::class)->recentLogsForTenant($tenantId),
            'transactionalMailCapabilities' => [
                'managed' => $planManager->hasFeature(PlanFeature::TRANSACTIONAL_EMAIL_MANAGED, $tenantId),
                'custom_smtp' => $planManager->hasFeature(PlanFeature::TRANSACTIONAL_EMAIL_CUSTOM_SMTP, $tenantId),
            ],
            'transactionalMailManagedQuota' => $planManager->usageState(PlanLimit::TRANSACTIONAL_EMAILS_MONTHLY, $tenantId),
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
            'paymentGatewayProviders' => $paymentGateways->providers(),
            'activePaymentGateway' => $paymentGateways->activeGatewayRecord(),
            'activePaymentGatewayLabel' => $paymentGateways->activeProviderLabel(),
            'shippingProviderDrivers' => $shippingProviders->providers(),
            'activeShippingProvider' => $shippingProviders->activeProviderRecord(),
            'activeShippingProviderLabel' => $shippingProviders->activeProviderLabel(),
        ]);
    }

    public function storeCompany(Request $request, TenantPlanManager $planManager): RedirectResponse
    {
        $planManager->ensureWithinLimit(PlanLimit::COMPANIES);
        $data = $this->validateCompany($request);

        $company = Company::create([
            'tenant_id' => TenantContext::currentId(),
            'name' => $data['name'],
            'slug' => $data['slug'],
            'code' => $data['code'] ?: null,
            'is_active' => $request->boolean('is_active'),
            'meta' => [],
        ]);

        $this->grantCreatorAccessToCompany($request->user(), $company);

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
        $request->session()->put('branch_all', true);

        return back();
    }

    public function storeBranch(Request $request): RedirectResponse
    {
        app(TenantPlanManager::class)->ensureWithinLimit(PlanLimit::BRANCHES);

        $company = $this->requireCurrentCompany();
        $data = $this->validateBranch($request, $company);

        $branch = Branch::create([
            'tenant_id' => TenantContext::currentId(),
            'company_id' => $company->id,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'code' => $data['code'] ?: null,
            'is_active' => $request->boolean('is_active'),
            'meta' => [],
        ]);

        $this->grantCreatorAccessToBranch($request->user(), $company, $branch);

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
        $request->session()->forget('branch_all');

        return back();
    }

    public function clearBranch(Request $request): RedirectResponse
    {
        $request->session()->forget(['branch_id', 'branch_slug']);
        $request->session()->put('branch_all', true);

        return back();
    }

    public function saveDocuments(Request $request): RedirectResponse
    {
        $company = $this->requireCurrentCompany();
        $branch = BranchContext::currentBranch();
        $tenantId = TenantContext::currentId();
        $documentNumbering = app(DocumentNumberingService::class);
        $documentWorkflow = app(DocumentWorkflowService::class);

        $data = $request->validate([
            'company_numbering' => ['nullable', 'array'],
            'company_numbering.*' => ['nullable', 'array'],
            'company_numbering.*.prefix' => ['nullable', 'string', 'max:30'],
            'company_numbering.*.number_format' => ['nullable', 'string', 'max:120'],
            'company_numbering.*.padding' => ['nullable', 'integer', 'min:1', 'max:12'],
            'company_numbering.*.next_number' => ['nullable', 'integer', 'min:1'],
            'company_numbering.*.reset_period' => ['nullable', Rule::in(['never', 'monthly', 'yearly'])],
            'company_document_header' => ['nullable', 'string'],
            'company_document_footer' => ['nullable', 'string'],
            'company_receipt_footer' => ['nullable', 'string'],
            'company_notes' => ['nullable', 'string'],
            'branch_numbering' => ['nullable', 'array'],
            'branch_numbering.*' => ['nullable', 'array'],
            'branch_numbering.*.prefix' => ['nullable', 'string', 'max:30'],
            'branch_numbering.*.number_format' => ['nullable', 'string', 'max:120'],
            'branch_numbering.*.padding' => ['nullable', 'integer', 'min:1', 'max:12'],
            'branch_numbering.*.next_number' => ['nullable', 'integer', 'min:1'],
            'branch_numbering.*.reset_period' => ['nullable', Rule::in(['never', 'monthly', 'yearly'])],
            'company_workflow' => ['nullable', 'array'],
            'company_workflow.*' => ['nullable', 'array'],
            'company_workflow.*.requires_approval_before_conversion' => ['nullable', 'boolean'],
            'company_workflow.*.requires_approval_before_finalize' => ['nullable', 'boolean'],
            'branch_workflow' => ['nullable', 'array'],
            'branch_workflow.*' => ['nullable', 'array'],
            'branch_workflow.*.requires_approval_before_conversion' => ['nullable', 'boolean'],
            'branch_workflow.*.requires_approval_before_finalize' => ['nullable', 'boolean'],
            'branch_document_header' => ['nullable', 'string'],
            'branch_document_footer' => ['nullable', 'string'],
            'branch_receipt_footer' => ['nullable', 'string'],
            'branch_notes' => ['nullable', 'string'],
        ]);

        $this->assertValidDocumentTypes($data['company_numbering'] ?? []);
        $this->assertValidDocumentTypes($data['branch_numbering'] ?? []);
        $this->assertValidWorkflowTypes($data['company_workflow'] ?? []);
        $this->assertValidWorkflowTypes($data['branch_workflow'] ?? []);

        DocumentSetting::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'company_id' => $company->id,
                'branch_id' => null,
            ],
            [
                'document_header' => $data['company_document_header'] ?: null,
                'document_footer' => $data['company_document_footer'] ?: null,
                'receipt_footer' => $data['company_receipt_footer'] ?: null,
                'notes' => $data['company_notes'] ?: null,
            ]
        );

        foreach (DocumentNumberingRule::supportedDocumentTypes() as $documentType) {
            $rule = $documentNumbering->upsertRule(
                $tenantId,
                $company->id,
                null,
                $documentType,
                $data['company_numbering'][$documentType] ?? []
            );

            if ($documentType === 'sale') {
                DocumentSetting::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'company_id' => $company->id,
                        'branch_id' => null,
                    ],
                    [
                        'invoice_prefix' => $rule->prefix,
                        'invoice_padding' => $rule->padding,
                        'invoice_next_number' => $rule->next_number,
                        'invoice_last_period' => $rule->last_period,
                        'invoice_reset_period' => $rule->reset_period,
                    ]
                );
            }
        }

        foreach (DocumentWorkflowRule::supportedDocumentTypes() as $documentType) {
            $documentWorkflow->upsertRule(
                $tenantId,
                $company->id,
                null,
                $documentType,
                $data['company_workflow'][$documentType] ?? []
            );
        }

        if ($branch) {
            DocumentSetting::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'company_id' => $company->id,
                    'branch_id' => $branch->id,
                ],
                [
                    'document_header' => $data['branch_document_header'] ?: null,
                    'document_footer' => $data['branch_document_footer'] ?: null,
                    'receipt_footer' => $data['branch_receipt_footer'] ?: null,
                    'notes' => $data['branch_notes'] ?: null,
                ]
            );

            foreach (DocumentNumberingRule::supportedDocumentTypes() as $documentType) {
                $rule = $documentNumbering->upsertRule(
                    $tenantId,
                    $company->id,
                    $branch->id,
                    $documentType,
                    $data['branch_numbering'][$documentType] ?? []
                );

                if ($documentType === 'sale') {
                    DocumentSetting::query()->updateOrCreate(
                        [
                            'tenant_id' => $tenantId,
                            'company_id' => $company->id,
                            'branch_id' => $branch->id,
                        ],
                        [
                            'invoice_prefix' => $rule->prefix,
                            'invoice_padding' => $rule->padding,
                            'invoice_next_number' => $rule->next_number,
                            'invoice_last_period' => $rule->last_period,
                            'invoice_reset_period' => $rule->reset_period,
                        ]
                    );
                }
            }

            foreach (DocumentWorkflowRule::supportedDocumentTypes() as $documentType) {
                $documentWorkflow->upsertRule(
                    $tenantId,
                    $company->id,
                    $branch->id,
                    $documentType,
                    $data['branch_workflow'][$documentType] ?? []
                );
            }
        }

        return redirect()->route('settings.documents')->with('status', 'Document settings berhasil disimpan.');
    }

    public function saveGeneral(Request $request): RedirectResponse
    {
        $tenant = TenantContext::currentTenant();
        abort_unless($tenant, 404);
        $publicDisk = (string) config('workspace-files.public_disk', 'public');

        $rules = [
            'workspace_name' => ['required', 'string', 'max:255'],
            'public_storefront_enabled' => ['nullable', 'boolean'],
            'default_public_company_id' => ['nullable', 'integer'],
            'company_shipping_origin_postal_code' => ['nullable', 'string', 'max:20'],
            'company_shipping_origin_area_id' => ['nullable', 'string', 'max:100'],
            'public_brand_name' => ['nullable', 'string', 'max:255'],
            'public_brand_description' => ['nullable', 'string', 'max:1000'],
            'public_brand_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        if (!$this->currencySettingsLocked()) {
            $rules['default_currency'] = ['required', Rule::in(array_keys(app(CurrencySettingsResolver::class)->options()))];
            $rules['company_default_currency'] = ['nullable', Rule::in(array_keys(app(CurrencySettingsResolver::class)->options()))];
        }

        $data = $request->validate($rules);

        $tenantMeta = $tenant->meta ?? [];

        $tenant->update([
            'name' => trim((string) $data['workspace_name']),
        ]);

        $currentCompanyIds = Company::query()
            ->where('tenant_id', (int) $tenant->id)
            ->active()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $defaultPublicCompanyId = (int) ($data['default_public_company_id'] ?? 0);
        if ($defaultPublicCompanyId > 0 && !in_array($defaultPublicCompanyId, $currentCompanyIds, true)) {
            throw ValidationException::withMessages([
                'default_public_company_id' => 'Company publik default tidak valid untuk tenant ini.',
            ]);
        }

        $tenantMeta['public_storefront_enabled'] = $request->boolean('public_storefront_enabled', true);

        if ($defaultPublicCompanyId > 0) {
            $tenantMeta['default_public_company_id'] = $defaultPublicCompanyId;
        } else {
            unset($tenantMeta['default_public_company_id']);
        }

        $publicBrandName = trim((string) ($data['public_brand_name'] ?? ''));
        $publicBrandDescription = trim((string) ($data['public_brand_description'] ?? ''));

        if ($publicBrandName === '') {
            unset($tenantMeta['public_brand_name']);
        } else {
            $tenantMeta['public_brand_name'] = $publicBrandName;
        }

        if ($publicBrandDescription === '') {
            unset($tenantMeta['public_brand_description']);
        } else {
            $tenantMeta['public_brand_description'] = $publicBrandDescription;
        }

        if ($request->hasFile('public_brand_logo')) {
            $oldPath = (string) ($tenantMeta['public_brand_logo_path'] ?? '');
            $stored = app(WorkspaceMediaStorageService::class)->storeUploadedFile(
                $request->file('public_brand_logo'),
                'tenant-brand/' . $tenant->id,
                $publicDisk
            );
            $newPath = $stored['path'];

            $tenantMeta['public_brand_logo_path'] = $newPath;

            if ($oldPath !== '' && $oldPath !== $newPath) {
                app(StoredFileService::class)->deletePublicAssetByPath($oldPath, $publicDisk);
            }
        }

        $tenant->update([
            'meta' => $tenantMeta,
        ]);

        if ($company = CompanyContext::currentCompany()) {
            $companyMeta = $company->meta ?? [];
            $originPostalCode = trim((string) ($data['company_shipping_origin_postal_code'] ?? ''));
            $originAreaId = trim((string) ($data['company_shipping_origin_area_id'] ?? ''));

            if ($originPostalCode === '') {
                unset($companyMeta['shipping_origin_postal_code']);
            } else {
                $companyMeta['shipping_origin_postal_code'] = $originPostalCode;
            }

            if ($originAreaId === '') {
                unset($companyMeta['shipping_origin_area_id']);
            } else {
                $companyMeta['shipping_origin_area_id'] = $originAreaId;
            }

            $company->update([
                'meta' => $companyMeta,
            ]);
        }

        if (!$this->currencySettingsLocked()) {
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
        }

        return redirect()->route('settings.general')->with('status', 'General settings berhasil disimpan.');
    }

    public function saveTransactionalEmail(Request $request): RedirectResponse
    {
        $tenantId = TenantContext::currentId();
        $setting = TenantTransactionalMailSetting::query()->firstOrNew([
            'tenant_id' => $tenantId,
        ]);

        $data = $request->validate([
            'is_enabled' => ['nullable', 'boolean'],
            'delivery_mode' => ['required', Rule::in([
                TenantTransactionalMailSetting::DELIVERY_MODE_MANAGED,
                TenantTransactionalMailSetting::DELIVERY_MODE_CUSTOM_SMTP,
            ])],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_encryption' => ['nullable', Rule::in(['tls', 'ssl', 'none'])],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'from_email' => ['nullable', 'email', 'max:255'],
            'reply_to' => ['nullable', 'email', 'max:255'],
        ]);

        $planManager = app(TenantPlanManager::class);

        if ($data['delivery_mode'] === TenantTransactionalMailSetting::DELIVERY_MODE_MANAGED
            && !$planManager->hasFeature(PlanFeature::TRANSACTIONAL_EMAIL_MANAGED, $tenantId)) {
            throw ValidationException::withMessages([
                'delivery_mode' => 'Plan tenant saat ini belum mendukung Email Terkelola.',
            ]);
        }

        if ($data['delivery_mode'] === TenantTransactionalMailSetting::DELIVERY_MODE_CUSTOM_SMTP
            && !$planManager->hasFeature(PlanFeature::TRANSACTIONAL_EMAIL_CUSTOM_SMTP, $tenantId)) {
            throw ValidationException::withMessages([
                'delivery_mode' => 'Plan tenant saat ini belum mendukung SMTP Sendiri.',
            ]);
        }

        if (empty($data['smtp_password']) && $setting->exists) {
            unset($data['smtp_password']);
        }

        if ($data['delivery_mode'] === TenantTransactionalMailSetting::DELIVERY_MODE_CUSTOM_SMTP) {
            foreach ([
                'smtp_host' => 'SMTP host wajib diisi.',
                'smtp_username' => 'SMTP username wajib diisi.',
                'from_email' => 'From email wajib diisi.',
            ] as $field => $message) {
                if (trim((string) ($data[$field] ?? '')) === '') {
                    throw ValidationException::withMessages([$field => $message]);
                }
            }

            if (!$setting->exists && empty($data['smtp_password'])) {
                throw ValidationException::withMessages([
                    'smtp_password' => 'SMTP password wajib diisi.',
                ]);
            }
        }

        $attributes = [
            'tenant_id' => $tenantId,
            'is_enabled' => $request->boolean('is_enabled'),
            'delivery_mode' => $data['delivery_mode'],
            'from_name' => trim((string) ($data['from_name'] ?? '')) ?: null,
            'reply_to' => trim((string) ($data['reply_to'] ?? '')) ?: null,
            'updated_by' => $request->user()?->id,
        ];

        if ($data['delivery_mode'] === TenantTransactionalMailSetting::DELIVERY_MODE_CUSTOM_SMTP) {
            $attributes['smtp_host'] = trim((string) ($data['smtp_host'] ?? '')) ?: null;
            $attributes['smtp_port'] = $data['smtp_port'] ?? null;
            $attributes['smtp_encryption'] = (($data['smtp_encryption'] ?? 'tls') === 'none') ? null : ($data['smtp_encryption'] ?? 'tls');
            $attributes['smtp_username'] = trim((string) ($data['smtp_username'] ?? '')) ?: null;
            $attributes['from_email'] = trim((string) ($data['from_email'] ?? '')) ?: null;
        }

        $setting->fill($attributes);

        if (array_key_exists('smtp_password', $data)) {
            $setting->smtp_password = $data['smtp_password'] ?: null;
        }

        if (!$setting->exists) {
            $setting->created_by = $request->user()?->id;
        }

        $setting->save();

        return redirect()->route('settings.transactional-email')->with('status', 'Transactional email settings berhasil disimpan.');
    }

    public function sendTransactionalEmailTest(
        Request $request,
        TenantTransactionalMailConfigResolver $configResolver,
        TenantTransactionalMailerFactory $mailerFactory,
    ): RedirectResponse {
        $tenant = TenantContext::currentTenant();
        abort_unless($tenant, 404);

        $data = $request->validate([
            'test_email' => ['required', 'email', 'max:255'],
        ]);

        $setting = $configResolver->requireEnabled((int) $tenant->id);
        $mailer = $mailerFactory->configure('tenant_transactional_test_' . $tenant->id, $setting);
        $identity = $configResolver->senderIdentity($setting);

        try {
            Mail::mailer($mailer)
                ->to($data['test_email'])
                ->send(new TenantTransactionalTestMail(
                    workspaceName: (string) $tenant->name,
                    fromEmail: $identity['from_email'],
                    fromName: $identity['from_name'],
                    replyToEmail: $identity['reply_to_email'],
                ));

            $setting->update([
                'last_tested_at' => now(),
                'last_test_status' => 'success',
                'last_test_error' => null,
                'updated_by' => $request->user()?->id,
            ]);

            return redirect()->route('settings.transactional-email')->with('status', 'Test email berhasil dikirim.');
        } catch (\Throwable $e) {
            $setting->update([
                'last_tested_at' => now(),
                'last_test_status' => 'failed',
                'last_test_error' => $e->getMessage(),
                'updated_by' => $request->user()?->id,
            ]);

            return redirect()->route('settings.transactional-email')->withErrors([
                'test_email' => 'Gagal mengirim test email: ' . $e->getMessage(),
            ]);
        }
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
            'requested_by' => $user ? $user->id : null,
            'status' => ByoAiAddon::REQUEST_STATUS_PENDING,
            'preferred_provider' => $data['preferred_provider'],
            'intended_volume' => $data['intended_volume'],
            'chatbot_account_count' => $data['chatbot_account_count'] ?? null,
            'channel_count' => $data['channel_count'] ?? null,
            'technical_contact_name' => $data['technical_contact_name'] ?: ($user ? $user->name : null),
            'technical_contact_email' => $data['technical_contact_email'] ?: ($user ? $user->email : null),
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

    public function requireCurrentCompany(): Company
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
                'description' => 'Pengaturan template dokumen dan numbering per jenis dokumen.',
            ],
            'access' => [
                'label' => 'Users & Access',
                'route' => 'settings.access',
                'icon' => 'ti ti-shield-lock',
                'description' => 'User tenant dan role yang sedang aktif.',
            ],
            'transactional-email' => [
                'label' => 'Transactional Email',
                'route' => 'settings.transactional-email',
                'icon' => 'ti ti-mail-cog',
                'description' => 'SMTP tenant untuk email invoice, reminder, dan receipt customer.',
            ],
            'payment-gateway' => [
                'label' => 'Payment Gateway',
                'route' => 'settings.payment-gateway',
                'icon' => 'ti ti-credit-card-pay',
                'description' => 'Pilih provider checkout aktif untuk commerce tenant.',
            ],
            'shipping-provider' => [
                'label' => 'Shipping Provider',
                'route' => 'settings.shipping-provider',
                'icon' => 'ti ti-truck-delivery',
                'description' => 'Pilih provider ongkir aktif untuk commerce tenant.',
            ],
        ];
    }

    private function currencySettingsLocked(): bool
    {
        return true;
    }

    private function assertValidDocumentTypes(array $rows): void
    {
        $supported = DocumentNumberingRule::supportedDocumentTypes();

        foreach (array_keys($rows) as $documentType) {
            if (!in_array($documentType, $supported, true)) {
                throw ValidationException::withMessages([
                    'document_type' => 'Document type `'.$documentType.'` tidak didukung.',
                ]);
            }
        }
    }

    private function assertValidWorkflowTypes(array $rows): void
    {
        $supported = DocumentWorkflowRule::supportedDocumentTypes();

        foreach (array_keys($rows) as $documentType) {
            if (!in_array($documentType, $supported, true)) {
                throw ValidationException::withMessages([
                    'document_type' => 'Workflow type `'.$documentType.'` tidak didukung.',
                ]);
            }
        }
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

    private function grantCreatorAccessToCompany(?User $user, Company $company): void
    {
        if (!$user || !Schema::hasTable('user_companies')) {
            return;
        }

        $access = app(UserAccessManager::class);
        $companyIds = $access->companyIdsFor($user);

        if ($companyIds === null) {
            return;
        }

        $defaultCompanyId = $access->defaultCompanyIdFor($user) ?: $company->id;
        $branchIds = $access->branchIdsFor($user)?->all() ?? [];

        $access->sync(
            $user,
            $companyIds->push($company->id)->unique()->values()->all(),
            $branchIds,
            $defaultCompanyId,
            $access->defaultBranchIdFor($user, $defaultCompanyId)
        );
    }

    private function grantCreatorAccessToBranch(?User $user, Company $company, Branch $branch): void
    {
        if (!$user || !Schema::hasTable('user_branches')) {
            return;
        }

        $access = app(UserAccessManager::class);
        $companyIds = $access->companyIdsFor($user);
        $branchIds = $access->branchIdsFor($user)?->all();

        if ($companyIds === null || $branchIds === null) {
            return;
        }

        $defaultCompanyId = $access->defaultCompanyIdFor($user) ?: $company->id;
        $defaultBranchId = $access->defaultBranchIdFor($user, $defaultCompanyId) ?: $branch->id;

        $access->sync(
            $user,
            $companyIds->push($company->id)->unique()->values()->all(),
            collect($branchIds)->push($branch->id)->unique()->values()->all(),
            $defaultCompanyId,
            $defaultBranchId
        );
    }
}
