<?php

namespace Tests\Feature\Core;

use App\Models\Company;
use App\Models\CoreNotification;
use App\Models\NotificationDelivery;
use App\Models\NotificationPreference;
use App\Models\NotificationPushSubscription;
use App\Models\NotificationRecipient;
use App\Models\Tenant;
use App\Models\User;
use App\Http\Middleware\EnsurePlatformAdminAccess;
use App\Http\Middleware\EnsureTwoFactorAuthenticated;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\Notifications\NotificationCenter;
use App\Support\Notifications\NotificationMessage;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Tenant::query()->firstOrCreate([
            'id' => 1,
        ], [
            'name' => 'Tenant One',
            'slug' => 'tenant-one',
            'is_active' => true,
        ]);

        Company::query()->firstOrCreate([
            'id' => 1,
        ], [
            'tenant_id' => 1,
            'name' => 'Default Company',
            'slug' => 'default-company',
            'code' => 'CMP',
            'is_active' => true,
        ]);

        TenantContext::setCurrentId(1);
        CompanyContext::setCurrentId(1);
        BranchContext::forget();

        app(PermissionRegistrar::class)->setPermissionsTeamId(1);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('notifications.view', 'web');
        Role::findOrCreate('Finance Staff', 'web')->givePermissionTo('notifications.view');
        Role::findOrCreate('Admin', 'web')->givePermissionTo('notifications.view');
        Role::findOrCreate('Sales', 'web')->givePermissionTo('notifications.view');
    }

    public function test_notification_center_dedupes_and_resolves_recipients_by_role(): void
    {
        Queue::fake();

        $financeUser = $this->userWithRole('Finance Staff');
        $adminUser = $this->userWithRole('Admin');
        $salesUser = $this->userWithRole('Sales');

        app(NotificationCenter::class)->publish(new NotificationMessage(
            module: 'finance',
            type: 'finance.approval_request_pending',
            title: 'Approval pending',
            body: 'Request approval baru menunggu persetujuan.',
            tenantId: 1,
            companyId: 1,
            dedupeKey: 'approval:demo:1',
            actions: [
                ['label' => 'Buka Approval', 'url' => '/finance/approvals'],
            ],
        ));

        app(NotificationCenter::class)->publish(new NotificationMessage(
            module: 'finance',
            type: 'finance.approval_request_pending',
            title: 'Approval pending',
            body: 'Request approval yang sama muncul lagi.',
            tenantId: 1,
            companyId: 1,
            dedupeKey: 'approval:demo:1',
            actions: [
                ['label' => 'Buka Approval', 'url' => '/finance/approvals'],
            ],
        ));

        $notification = CoreNotification::query()->firstOrFail();

        $this->assertSame(1, CoreNotification::query()->count());
        $this->assertSame(2, (int) $notification->occurrence_count);
        $this->assertSame('active', $notification->status);
        $this->assertCount(2, NotificationRecipient::query()->pluck('user_id'));
        $this->assertTrue(NotificationRecipient::query()->where('user_id', $financeUser->id)->exists());
        $this->assertTrue(NotificationRecipient::query()->where('user_id', $adminUser->id)->exists());
        $this->assertFalse(NotificationRecipient::query()->where('user_id', $salesUser->id)->exists());
        $this->assertSame(4, NotificationDelivery::query()->where('channel', 'web_push')->count());
        $this->assertSame(4, NotificationDelivery::query()->where('status', NotificationDelivery::STATUS_SKIPPED)->count());
    }

    public function test_preference_can_disable_web_push_while_in_app_remains_created(): void
    {
        Queue::fake();

        $financeUser = $this->userWithRole('Finance Staff');

        NotificationPreference::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'user_id' => $financeUser->id,
            'notification_type' => 'finance.approval_request_pending',
            'channel' => 'web_push',
            'is_enabled' => false,
        ]);

        app(NotificationCenter::class)->publish(new NotificationMessage(
            module: 'finance',
            type: 'finance.approval_request_pending',
            title: 'Approval pending',
            body: 'Push harus diblok oleh preference.',
            tenantId: 1,
            companyId: 1,
            dedupeKey: 'approval:pref:1',
        ));

        $recipient = NotificationRecipient::query()->firstOrFail();
        $delivery = NotificationDelivery::query()->firstOrFail();

        $this->assertFalse($recipient->is_read);
        $this->assertSame('web_push', $delivery->channel);
        $this->assertSame(NotificationDelivery::STATUS_SKIPPED, $delivery->status);
        $this->assertSame('preference_disabled', data_get($delivery->meta, 'reason'));
    }

    public function test_user_can_view_notification_inbox_mark_read_and_register_push_subscription(): void
    {
        $user = $this->userWithRole('Finance Staff');

        $notification = app(NotificationCenter::class)->publish(new NotificationMessage(
            module: 'payments',
            type: 'payments.payment_posted',
            title: 'Payment posted',
            body: 'Pembayaran posted untuk invoice demo.',
            tenantId: 1,
            companyId: 1,
            dedupeKey: 'payment:demo:1',
            actions: [
                ['label' => 'Buka Payment', 'url' => '/payments'],
            ],
        ));

        $recipient = $notification->recipients->firstWhere('user_id', $user->id);
        $this->assertNotNull($recipient);

        $this->withoutMiddleware([
                EnsureTwoFactorAuthenticated::class,
                EnsurePlatformAdminAccess::class,
            ])
            ->actingAs($user)
            ->withSession([
                'company_id' => 1,
                'company_slug' => 'default-company',
            ])
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Payment posted');

        $this->withoutMiddleware([
                EnsureTwoFactorAuthenticated::class,
                EnsurePlatformAdminAccess::class,
            ])
            ->actingAs($user)
            ->post(route('notifications.read', $recipient->id))
            ->assertRedirect();

        $this->assertTrue((bool) NotificationRecipient::query()->whereKey($recipient->id)->value('is_read'));

        $subscriptionPayload = [
            'endpoint' => 'https://push.example.test/subscriptions/123',
            'keys' => [
                'p256dh' => 'demo-public-key',
                'auth' => 'demo-auth',
            ],
            'contentEncoding' => 'aes128gcm',
        ];

        $this->withoutMiddleware([
                EnsureTwoFactorAuthenticated::class,
                EnsurePlatformAdminAccess::class,
            ])
            ->actingAs($user)
            ->postJson(route('notifications.push-subscriptions.store'), $subscriptionPayload)
            ->assertOk()
            ->assertJsonPath('message', 'Web push aktif.');

        $this->assertTrue(NotificationPushSubscription::query()->where('user_id', $user->id)->where('endpoint', $subscriptionPayload['endpoint'])->exists());
    }

    private function userWithRole(string $roleName): User
    {
        $user = User::factory()->create([
            'tenant_id' => 1,
        ]);

        $user->assignRole($roleName);

        return $user;
    }
}
