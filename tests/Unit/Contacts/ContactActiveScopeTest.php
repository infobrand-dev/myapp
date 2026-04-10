<?php

namespace Tests\Unit\Contacts;

use App\Modules\Contacts\Models\Contact;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContactActiveScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('type')->default('individual');
            $table->unsignedBigInteger('parent_contact_id')->nullable();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('contacts');

        parent::tearDown();
    }

    public function test_active_scope_returns_only_active_contacts(): void
    {
        Contact::query()->create([
            'tenant_id' => 1,
            'type' => 'individual',
            'name' => 'Active Contact',
            'is_active' => true,
        ]);

        Contact::query()->create([
            'tenant_id' => 1,
            'type' => 'individual',
            'name' => 'Inactive Contact',
            'is_active' => false,
        ]);

        $names = Contact::query()
            ->active()
            ->orderBy('name')
            ->pluck('name')
            ->all();

        $this->assertSame(['Active Contact'], $names);
    }
}
