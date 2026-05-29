<?php

namespace App\Support\Shipping;

use App\Models\TenantShippingProvider;
use App\Support\CompanyContext;
use App\Support\Shipping\Contracts\ShippingProviderDriver;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use RuntimeException;

class ShippingProviderManager
{
    /**
     * @param  iterable<ShippingProviderDriver>  $drivers
     */
    public function __construct(iterable $drivers)
    {
        $this->drivers = collect($drivers)
            ->keyBy(fn (ShippingProviderDriver $driver) => $driver->provider());
    }

    private Collection $drivers;

    /**
     * @return array<int, array{
     *   provider:string,
     *   label:string,
     *   configured:bool,
     *   ready:bool,
     *   required_config_fields:array<int, string>,
     *   required_checkout_fields:array<int, string>,
     *   capabilities:array<string, bool|int|string>,
     *   settings_route:?string,
     *   transactions_route:?string
     * }>
     */
    public function providers(): array
    {
        return $this->drivers
            ->map(fn (ShippingProviderDriver $driver) => [
                'provider' => $driver->provider(),
                'label' => $driver->label(),
                'configured' => $driver->isConfigured(),
                'ready' => $driver->isConfigured(),
                'required_config_fields' => $driver->requiredConfigFields(),
                'required_checkout_fields' => $driver->requiredCheckoutFields(),
                'capabilities' => $driver->capabilities(),
                'settings_route' => $driver->settingsRoute(),
                'transactions_route' => $driver->transactionsRoute(),
            ])
            ->values()
            ->all();
    }

    public function driver(?string $provider = null): ?ShippingProviderDriver
    {
        $provider ??= $this->activeProviderCode();

        if (!$provider) {
            return null;
        }

        return $this->drivers->get($provider);
    }

    public function activeProviderRecord(): ?TenantShippingProvider
    {
        return TenantShippingProvider::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->where('is_enabled', true)
            ->latest('id')
            ->first();
    }

    public function activeProviderCode(): ?string
    {
        return $this->activeProviderRecord()?->provider;
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
     *   required_checkout_fields:array<int, string>,
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
            'required_checkout_fields' => $driver->requiredCheckoutFields(),
            'capabilities' => $driver->capabilities(),
            'settings_route' => $driver->settingsRoute(),
            'transactions_route' => $driver->transactionsRoute(),
        ];
    }

    public function assertQuoteReady(?string $provider = null): ShippingProviderDriver
    {
        $driver = $this->driver($provider);

        if (!$driver) {
            throw new RuntimeException('Pengiriman delivery sedang tidak tersedia.');
        }

        if (!$driver->isConfigured()) {
            throw new RuntimeException($driver->label() . ' belum siap dipakai untuk hitung ongkir.');
        }

        return $driver;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *   provider:string,
     *   options:array<int, array{
     *     courier_code:string,
     *     courier_name:string,
     *     service_name:string,
     *     service_code:string,
     *     price:float,
     *     currency:string,
     *     etd:?string,
     *     raw:array
     *   }>,
     *   raw:array
     * }
     */
    public function quoteRates(array $payload, ?string $provider = null): array
    {
        $driver = $this->assertQuoteReady($provider);

        return $driver->quoteRates($payload);
    }
}
