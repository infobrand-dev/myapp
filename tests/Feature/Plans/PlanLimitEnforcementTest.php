<?php

namespace Tests\Feature\Plans;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Modules\Contacts\ContactsServiceProvider;
use App\Modules\Contacts\Models\Contact;
use App\Modules\EmailMarketing\EmailMarketingServiceProvider;
use App\Modules\EmailMarketing\Models\EmailCampaign;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Modules\WhatsAppApi\WhatsAppApiServiceProvider;
use App\Support\PlanFeature;
use App\Support\PlanLimit;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

class PlanLimitEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));

        $this->app->register(ContactsServiceProvider::class);
        $this->app->register(WhatsAppApiServiceProvider::class);
        $this->app->register(EmailMarketingServiceProvider::class);

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Contacts/Database/Migrations',
            '--force' => true,
        ]);
        $this->artisan('migrate', [
            '--path' => 'app/Modules/WhatsAppApi/Database/Migrations',
            '--force' => true,
        ]);
        $this->artisan('migrate', [
            '--path' => 'app/Modules/EmailMarketing/Database/Migrations',
            '--force' => true,
        ]);

        $this->seed(SubscriptionPlanSeeder::class);
        $this->withoutMiddleware(RoleMiddleware::class);
    }

    public function test_creating_contact_over_limit_fails(): void
    {
        [$user, $tenant] = $this->makeTenantUserWithPlan([
            PlanLimit::CONTACTS => 1,
        ]);

        Contact::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'individual',
            'name' => 'Existing Contact',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post('/contacts', [
                'type' => 'individual',
                'scope' => 'company',
                'name' => 'Second Contact',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('plan');

        $this->assertSame(1, Contact::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_importing_contacts_over_remaining_capacity_fails_before_write(): void
    {
        [$user, $tenant] = $this->makeTenantUserWithPlan([
            PlanLimit::CONTACTS => 1,
        ]);

        Contact::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'individual',
            'name' => 'Existing Contact',
            'is_active' => true,
        ]);

        $csv = implode("\n", [
            'type,name,email',
            'individual,Imported Contact,imported@example.test',
        ]);

        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csv);

        $this->actingAs($user)
            ->post('/contacts/import', [
                'import_file' => $file,
            ])
            ->assertSessionHasErrors('plan');

        $this->assertSame(1, Contact::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_existing_over_limit_tenant_can_still_edit_current_contact(): void
    {
        [$user, $tenant] = $this->makeTenantUserWithPlan([
            PlanLimit::CONTACTS => 1,
        ]);

        $contact = Contact::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'individual',
            'name' => 'Editable Contact',
            'is_active' => true,
        ]);

        Contact::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'individual',
            'name' => 'Extra Contact',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->put('/contacts/' . $contact->id, [
                'type' => 'individual',
                'scope' => 'company',
                'name' => 'Renamed Contact',
                'is_active' => 1,
            ])
            ->assertRedirect(route('contacts.index'));

        $this->assertSame('Renamed Contact', $contact->fresh()->name);
        $this->assertSame(2, Contact::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_creating_whatsapp_instance_over_limit_fails(): void
    {
        [$user, $tenant] = $this->makeTenantUserWithPlan([
            PlanFeature::WHATSAPP_API => true,
            PlanLimit::WHATSAPP_INSTANCES => 1,
        ]);

        WhatsAppInstance::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Existing Instance',
            'provider' => 'cloud',
            'status' => 'disconnected',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post('/whatsapp-api/instances', [
                'name' => 'Second Instance',
                'provider' => 'cloud',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('plan');

        $this->assertSame(1, WhatsAppInstance::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_creating_email_campaign_over_limit_fails(): void
    {
        Queue::fake();

        [$user, $tenant] = $this->makeTenantUserWithPlan([
            PlanFeature::EMAIL_MARKETING => true,
            PlanLimit::EMAIL_CAMPAIGNS => 1,
            PlanLimit::EMAIL_RECIPIENTS_MONTHLY => 100,
        ]);

        EmailCampaign::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Existing Campaign',
            'subject' => 'Existing Campaign',
            'status' => 'draft',
            'body_html' => '<p>Existing</p>',
            'filter_json' => [],
        ]);

        $this->actingAs($user)
            ->post('/email-marketing', [
                'subject' => 'Second Campaign',
                'body_html' => '<p>Hello</p>',
                'action' => 'save',
                'filters' => [],
            ])
            ->assertSessionHasErrors('plan');

        $this->assertSame(1, EmailCampaign::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_sending_email_campaign_over_monthly_recipient_limit_fails(): void
    {
        Queue::fake();

        [$user, $tenant] = $this->makeTenantUserWithPlan([
            PlanFeature::EMAIL_MARKETING => true,
            PlanLimit::EMAIL_CAMPAIGNS => 10,
            PlanLimit::EMAIL_RECIPIENTS_MONTHLY => 1,
        ]);

        Contact::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'individual',
            'name' => 'First Recipient',
            'email' => 'first@example.test',
            'is_active' => true,
        ]);

        Contact::query()->create([
            'tenant_id' => $tenant->id,
            'type' => 'individual',
            'name' => 'Second Recipient',
            'email' => 'second@example.test',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post('/email-marketing', [
                'subject' => 'Recipient Cap Campaign',
                'body_html' => '<p>Hello</p>',
                'action' => 'send',
                'filters' => [],
            ])
            ->assertSessionHasErrors('plan');

        $this->assertSame(0, EmailCampaign::query()->where('tenant_id', $tenant->id)->where('status', 'running')->count());
    }

    private function makeTenantUserWithPlan(array $limitsOrFeatures): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Pricing Safety Workspace',
            'slug' => 'pricing-safety-' . Tenant::query()->count(),
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        $features = [];
        $limits = [];

        foreach ($limitsOrFeatures as $key => $value) {
            if (str_starts_with((string) $key, 'max_') || str_ends_with((string) $key, '_monthly')) {
                $limits[$key] = $value;
                continue;
            }

            if (in_array($key, [
                PlanLimit::COMPANIES,
                PlanLimit::BRANCHES,
                PlanLimit::USERS,
                PlanLimit::PRODUCTS,
                PlanLimit::CONTACTS,
                PlanLimit::WHATSAPP_INSTANCES,
                PlanLimit::SOCIAL_ACCOUNTS,
                PlanLimit::LIVE_CHAT_WIDGETS,
                PlanLimit::CHATBOT_ACCOUNTS,
                PlanLimit::EMAIL_INBOX_ACCOUNTS,
                PlanLimit::EMAIL_CAMPAIGNS,
                PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY,
                PlanLimit::EMAIL_RECIPIENTS_MONTHLY,
                PlanLimit::AI_CREDITS_MONTHLY,
                PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS,
                PlanLimit::AUTOMATION_WORKFLOWS,
                PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY,
            ], true)) {
                $limits[$key] = $value;
                continue;
            }

            $features[$key] = (bool) $value;
        }

        $plan = SubscriptionPlan::query()->create([
            'code' => 'pricing-safety-' . $tenant->id,
            'name' => 'Pricing Safety Plan ' . $tenant->id,
            'billing_interval' => 'monthly',
            'is_active' => true,
            'is_public' => false,
            'is_system' => false,
            'sort_order' => 999,
            'features' => $features,
            'limits' => $limits,
        ]);

        TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'billing_provider' => 'test',
            'billing_reference' => 'pricing-safety-' . $tenant->id,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
            'auto_renews' => false,
        ]);

        return [$user, $tenant, $plan];
    }
}
