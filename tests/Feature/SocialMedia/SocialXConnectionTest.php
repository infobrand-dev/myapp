<?php

namespace Tests\Feature\SocialMedia;

use App\Models\User;
use App\Modules\SocialMedia\Models\SocialAccount;
use App\Modules\SocialMedia\SocialMediaServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Tests\TestCase;

class SocialXConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(SocialMediaServiceProvider::class);

        $this->artisan('migrate', [
            '--path' => 'app/Modules/SocialMedia/database/migrations',
            '--force' => true,
        ]);

        $this->withoutMiddleware(PermissionMiddleware::class);
    }

    public function test_x_connection_test_marks_account_as_ok(): void
    {
        Http::fake([
            'https://api.x.com/2/users/me*' => Http::response([
                'data' => [
                    'id' => 'x-user-123',
                    'username' => 'meetrax',
                    'name' => 'Meetra X',
                ],
            ], 200),
        ]);

        $user = User::factory()->create([
            'tenant_id' => 1,
        ]);

        $account = SocialAccount::query()->create([
            'tenant_id' => 1,
            'platform' => 'x',
            'name' => 'X Account',
            'access_token' => 'x-token',
            'status' => 'active',
            'metadata' => [
                'x_user_id' => 'x-user-123',
            ],
        ]);

        $this->actingAs($user)
            ->post(route('social-media.accounts.test-connection', $account))
            ->assertRedirect();

        $account->refresh();
        $this->assertSame('ok', data_get($account->metadata, 'last_connection_test_status'));
        $this->assertSame('Terhubung sebagai @meetrax', data_get($account->metadata, 'last_connection_test_message'));
    }
}
