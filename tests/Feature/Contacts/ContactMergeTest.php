<?php

namespace Tests\Feature\Contacts;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContactMergeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootContactRoutes();
        $this->bootModuleMigrations();
    }

    public function test_merge_candidates_page_lists_duplicate_phone_and_email_groups(): void
    {
        $user = $this->adminUser();

        Contact::query()->create([
            'type' => 'individual',
            'name' => 'Andi Satu',
            'email' => 'andi@example.com',
            'mobile' => '08123456789',
            'is_active' => true,
        ]);

        Contact::query()->create([
            'type' => 'individual',
            'name' => 'Andi Dua',
            'email' => 'ANDI@example.com',
            'phone' => '+628123456789',
            'is_active' => true,
        ]);

        Contact::query()->create([
            'type' => 'individual',
            'name' => 'Beda',
            'email' => 'beda@example.com',
            'mobile' => '628555000111',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('contacts.merge-candidates'));

        $response->assertOk();
        $response->assertSee('andi@example.com');
        $response->assertSee('628123456789');
        $response->assertSee('Andi Satu');
        $response->assertSee('Andi Dua');
        $response->assertDontSee('beda@example.com', false);
    }

    public function test_merge_combines_contact_data_and_moves_related_records(): void
    {
        $user = $this->adminUser();

        $company = Contact::query()->create([
            'type' => 'company',
            'name' => 'PT Utama',
            'email' => 'halo@utama.test',
            'is_active' => true,
        ]);

        $primary = Contact::query()->create([
            'type' => 'individual',
            'company_id' => null,
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'phone' => null,
            'mobile' => '628111111111',
            'job_title' => null,
            'notes' => 'Catatan utama',
            'is_active' => false,
        ]);

        $duplicate = Contact::query()->create([
            'type' => 'individual',
            'company_id' => $company->id,
            'name' => 'Budi Backup',
            'email' => 'BUDI@example.com',
            'phone' => '081222333444',
            'mobile' => null,
            'job_title' => 'Sales',
            'notes' => 'Catatan duplikat',
            'is_active' => true,
        ]);

        if (Schema::hasTable('email_campaigns') && Schema::hasTable('email_campaign_recipients')) {
            $campaignId = DB::table('email_campaigns')->insertGetId([
                'name' => 'Campaign Test',
                'subject' => 'Subject Test',
                'status' => 'draft',
                'body_html' => '<p>Test</p>',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('email_campaign_recipients')->insert([
                'campaign_id' => $campaignId,
                'contact_id' => $duplicate->id,
                'recipient_name' => 'Budi Backup',
                'recipient_email' => 'budi@example.com',
                'tracking_token' => 'merge-test-token',
                'delivery_status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->actingAs($user)->post(route('contacts.merge'), [
            'primary_id' => $primary->id,
            'duplicate_ids' => [$duplicate->id],
        ]);

        $response->assertRedirect(route('contacts.merge-candidates'));

        $this->assertDatabaseMissing('contacts', ['id' => $duplicate->id]);
        $this->assertDatabaseHas('contacts', [
            'id' => $primary->id,
            'company_id' => $company->id,
            'job_title' => 'Sales',
            'phone' => '6281222333444',
            'mobile' => '628111111111',
            'is_active' => 1,
        ]);

        $primary->refresh();
        $this->assertStringContainsString('Catatan utama', (string) $primary->notes);
        $this->assertStringContainsString('Catatan duplikat', (string) $primary->notes);

        if (Schema::hasTable('email_campaign_recipients')) {
            $this->assertDatabaseHas('email_campaign_recipients', [
                'contact_id' => $primary->id,
                'tracking_token' => 'merge-test-token',
            ]);
        }
    }

    public function test_merge_company_reassigns_employee_records(): void
    {
        $user = $this->adminUser();

        $primary = Contact::query()->create([
            'type' => 'company',
            'name' => 'PT Alpha',
            'email' => 'sales@alpha.test',
            'is_active' => true,
        ]);

        $duplicate = Contact::query()->create([
            'type' => 'company',
            'name' => 'PT Alpha Duplikat',
            'email' => 'SALES@alpha.test',
            'is_active' => true,
        ]);

        $employee = Contact::query()->create([
            'type' => 'individual',
            'company_id' => $duplicate->id,
            'name' => 'Karyawan',
            'email' => 'karyawan@alpha.test',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('contacts.merge'), [
            'primary_id' => $primary->id,
            'duplicate_ids' => [$duplicate->id],
        ])->assertRedirect(route('contacts.merge-candidates'));

        $this->assertDatabaseMissing('contacts', ['id' => $duplicate->id]);
        $this->assertDatabaseHas('contacts', [
            'id' => $employee->id,
            'company_id' => $primary->id,
        ]);
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();

        Role::findOrCreate('Admin');
        $user->assignRole('Admin');

        return $user;
    }

    private function bootModuleMigrations(): void
    {
        if (!Schema::hasTable('contacts')) {
            $this->artisan('migrate', [
                '--path' => 'app/Modules/Contacts/database/migrations',
                '--realpath' => false,
            ])->run();
        }

        if (!Schema::hasTable('email_campaigns')) {
            Schema::create('email_campaigns', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('subject');
                $table->string('status')->default('draft');
                $table->longText('body_html')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('email_campaign_recipients')) {
            Schema::create('email_campaign_recipients', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('campaign_id')->constrained('email_campaigns')->cascadeOnDelete();
                $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
                $table->string('recipient_name');
                $table->string('recipient_email');
                $table->string('tracking_token')->unique();
                $table->string('delivery_status')->default('pending');
                $table->timestamps();
            });
        }
    }

    private function bootContactRoutes(): void
    {
        if (!Route::has('contacts.merge-candidates')) {
            Route::middleware(['web', 'auth', 'role:Super-admin|Admin'])
                ->prefix('contacts')
                ->name('contacts.')
                ->group(function (): void {
                    Route::get('/', [\App\Modules\Contacts\Http\Controllers\ContactController::class, 'index'])->name('index');
                    Route::get('/merge-candidates', [\App\Modules\Contacts\Http\Controllers\ContactController::class, 'mergeCandidates'])->name('merge-candidates');
                    Route::post('/merge', [\App\Modules\Contacts\Http\Controllers\ContactController::class, 'merge'])->name('merge');
                    Route::get('/{contact}', [\App\Modules\Contacts\Http\Controllers\ContactController::class, 'show'])->name('show');
                });

            $this->app['router']->getRoutes()->refreshNameLookups();
            $this->app['router']->getRoutes()->refreshActionLookups();
        }

        View::addNamespace('contacts', base_path('app/Modules/Contacts/resources/views'));
    }
}
