<?php

namespace App\Support\Payments;

use App\Models\TenantPaymentGateway;
use App\Support\BooleanQuery;
use App\Support\CompanyContext;
use App\Support\Payments\Contracts\PaymentGatewayDriver;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use RuntimeException;

class PaymentGatewayManager
{
    /**
     * @param  iterable<PaymentGatewayDriver>  $drivers
     */
    public function __construct(
        iterable $drivers,
    ) {
        $this->drivers = collect($drivers)
            ->keyBy(fn (PaymentGatewayDriver $driver) => $driver->provider());
    }

    private Collection $drivers;

    /**
     * @return array<int, array{
     *   provider:string,
     *   label:string,
     *   configured:bool,
     *   ready:bool,
     *   required_config_fields:array<int, string>,
     *   capabilities:array<string, bool|int|string>,
     *   settings_route:?string,
     *   transactions_route:?string
     * }>
     */
    public function providers(): array
    {
        return $this->drivers
            ->map(fn (PaymentGatewayDriver $driver) => [
                'provider' => $driver->provider(),
                'label' => $driver->label(),
                'configured' => $driver->isConfigured(),
                'ready' => $driver->isConfigured(),
                'required_config_fields' => $driver->requiredConfigFields(),
                'capabilities' => $driver->capabilities(),
                'settings_route' => $driver->settingsRoute(),
                'transactions_route' => $driver->transactionsRoute(),
            ])
            ->values()
            ->all();
    }

    public function driver(?string $provider = null): ?PaymentGatewayDriver
    {
        $provider ??= $this->activeProviderCode();

        if (!$provider) {
            return null;
        }

        return $this->drivers->get($provider);
    }

    public function activeGatewayRecord(): ?TenantPaymentGateway
    {
        return BooleanQuery::apply(
            TenantPaymentGateway::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId()),
            'is_enabled',
            true
        )
            ->latest('id')
            ->first();
    }

    public function activeProviderCode(): ?string
    {
        return $this->activeGatewayRecord()?->provider;
    }

    public function activeProviderLabel(): ?string
    {
        $driver = $this->driver();

        return $driver ? $driver->label() : null;
    }

    /**
     * @return array{
     *   provider:string,
     *   label:string,
     *   configured:bool,
     *   ready:bool,
     *   required_config_fields:array<int, string>,
     *   capabilities:array<string, bool|int|string>,
     *   settings_route:?string,
     *   transactions_route:?string
     * }|null
     */
    public function providerMetadata(?string $provider = null): ?array
    {
        $driver = $this->driver($provider);

        if (!$driver) {
            return null;
        }

        return [
            'provider' => $driver->provider(),
            'label' => $driver->label(),
            'configured' => $driver->isConfigured(),
            'ready' => $driver->isConfigured(),
            'required_config_fields' => $driver->requiredConfigFields(),
            'capabilities' => $driver->capabilities(),
            'settings_route' => $driver->settingsRoute(),
            'transactions_route' => $driver->transactionsRoute(),
        ];
    }

    public function assertCheckoutReady(?string $provider = null): PaymentGatewayDriver
    {
        $driver = $this->driver($provider);

        if (!$driver) {
            throw new RuntimeException('Metode pembayaran online sedang tidak tersedia.');
        }

        if (!$driver->isConfigured()) {
            throw new RuntimeException($driver->label() . ' belum siap dipakai untuk checkout online.');
        }

        return $driver;
    }

    /**
     * @return array{provider:string,redirect_url:string,reference:string,token:?string}
     */
    public function createCheckoutForTarget(object $checkoutTarget, ?string $provider = null): array
    {
        $driver = $this->assertCheckoutReady($provider);

        return $driver->createCheckoutForTarget($checkoutTarget);
    }
}
