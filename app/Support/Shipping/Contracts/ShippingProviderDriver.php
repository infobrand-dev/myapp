<?php

namespace App\Support\Shipping\Contracts;

interface ShippingProviderDriver
{
    public function provider(): string;

    public function label(): string;

    public function isConfigured(): bool;

    /**
     * @return array<int, string>
     */
    public function requiredConfigFields(): array;

    /**
     * @return array<int, string>
     */
    public function requiredCheckoutFields(): array;

    /**
     * @return array<string, bool|int|string>
     */
    public function capabilities(): array;

    public function settingsRoute(): ?string;

    public function transactionsRoute(): ?string;

    /**
     * @param  array{
     *   origin_postal_code?:string,
     *   destination_postal_code?:string,
     *   origin_area_id?:string,
     *   destination_area_id?:string,
     *   couriers:string,
     *   item_name:string,
     *   item_description?:string|null,
     *   item_value:int|float,
     *   item_weight:int,
     *   item_quantity:int,
     *   item_length?:int|null,
     *   item_width?:int|null,
     *   item_height?:int|null
     * } $payload
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
    public function quoteRates(array $payload): array;
}
