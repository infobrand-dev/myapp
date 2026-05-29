<?php

namespace App\Modules\Storefront\Http\Requests;

use App\Support\Commerce\PublicStorefrontContext;
use App\Support\Payments\PaymentGatewayManager;
use App\Support\Shipping\ShippingProviderManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorefrontCartCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return !$this->attributes->get('platform_admin_host');
    }

    public function rules(): array
    {
        app(PublicStorefrontContext::class)->apply();

        $activeProvider = app(PaymentGatewayManager::class)->activeProviderCode();
        $shippingProviderManager = app(ShippingProviderManager::class);
        $shippingProvider = $shippingProviderManager->activeProviderCode();
        $shippingDriver = $shippingProviderManager->driver($shippingProvider);
        $providerOptions = collect(['manual'])
            ->when($activeProvider, fn ($values) => $values->push($activeProvider))
            ->unique()
            ->values()
            ->all();
        $deliveryRequested = (string) $this->input('fulfillment_method', 'pickup') === 'delivery';
        $shippingConfigured = $shippingDriver ? $shippingDriver->isConfigured() : false;

        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['nullable', 'email:rfc', 'max:150'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'customer_address' => ['required_if:fulfillment_method,delivery', 'nullable', 'string', 'max:1000'],
            'destination_postal_code' => [
                Rule::requiredIf($deliveryRequested && $shippingConfigured && $shippingProvider === 'biteship'),
                'nullable',
                'string',
                'max:20',
            ],
            'destination_area_id' => [
                Rule::requiredIf($deliveryRequested && $shippingConfigured && $shippingProvider === 'rajaongkir'),
                'nullable',
                'string',
                'max:120',
            ],
            'selected_shipping_rate' => ['nullable', 'string', 'max:100'],
            'couriers' => ['nullable', 'string', 'max:500'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'fulfillment_method' => ['required', Rule::in(['pickup', 'delivery'])],
            'payment_method' => ['required', Rule::in($providerOptions)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_name' => trim((string) $this->input('customer_name', '')),
            'customer_email' => trim((string) $this->input('customer_email', '')),
            'customer_phone' => trim((string) $this->input('customer_phone', '')),
            'customer_address' => trim((string) $this->input('customer_address', '')),
            'destination_postal_code' => trim((string) $this->input('destination_postal_code', '')),
            'destination_area_id' => trim((string) $this->input('destination_area_id', '')),
            'selected_shipping_rate' => trim((string) $this->input('selected_shipping_rate', '')),
            'couriers' => trim((string) $this->input('couriers', '')),
            'customer_note' => trim((string) $this->input('customer_note', '')),
            'fulfillment_method' => $this->filled('fulfillment_method') ? (string) $this->input('fulfillment_method') : 'pickup',
            'payment_method' => $this->filled('payment_method') ? (string) $this->input('payment_method') : 'manual',
        ]);
    }
}
