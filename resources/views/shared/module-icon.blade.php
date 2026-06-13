@php
    $registry = app(\App\Support\ModuleIconRegistry::class);
    $svg = null;

    if (isset($module) && is_array($module)) {
        $svg = $registry->svgForModule($module);
    } elseif (!empty($slug)) {
        $svg = $registry->svgForSlug((string) $slug);
    } elseif (!empty($channel)) {
        $svg = $registry->svgForChannel((string) $channel);
    }

    $size = isset($size) ? (int) $size : 20;
    $class = trim((string) ($class ?? ''));

    $resolvedSlug = (isset($module) && is_array($module))
        ? ($module['slug'] ?? '')
        : (string) ($slug ?? $channel ?? '');

    $tiIconMap = [
        'affiliate'       => 'ti-users-group',
        'biteship'        => 'ti-truck-delivery',
        'chatbot'         => 'ti-robot',
        'contacts'        => 'ti-address-book',
        'conversations'   => 'ti-messages',
        'crm'             => 'ti-chart-arrows-vertical',
        'discounts'       => 'ti-tag',
        'email_inbox'     => 'ti-mail',
        'email_marketing' => 'ti-send',
        'finance'         => 'ti-report-money',
        'fulfillment'     => 'ti-package-export',
        'inventory'       => 'ti-box',
        'live_chat'       => 'ti-message-circle-2',
        'midtrans'        => 'ti-credit-card',
        'payments'        => 'ti-cash',
        'point-of-sale'   => 'ti-device-tablet',
        'products'        => 'ti-shopping-bag',
        'purchases'       => 'ti-shopping-cart',
        'rajaongkir'      => 'ti-truck',
        'reports'         => 'ti-chart-bar',
        'sales'           => 'ti-receipt',
        'sample_data'     => 'ti-database-import',
        'shipping'        => 'ti-truck',
        'shortlink'       => 'ti-link',
        'social_media'    => 'ti-social',
        'storefront'      => 'ti-store',
        'task_management' => 'ti-checklist',
        'tripay'          => 'ti-credit-card',
        'wallet'          => 'ti-wallet',
        'whatsapp_api'    => 'ti-brand-whatsapp',
        'whatsapp_web'    => 'ti-brand-whatsapp',
        'xendit'          => 'ti-credit-card',
    ];
    $tiIcon = $tiIconMap[$resolvedSlug] ?? 'ti-package';
@endphp

@if($svg)
    <span class="module-svg-icon {{ $class }}" style="width: {{ $size }}px; height: {{ $size }}px;" aria-hidden="true">{!! $svg !!}</span>
@else
    <i class="ti {{ $tiIcon }} {{ $class }}" style="font-size:{{ $size }}px;" aria-hidden="true"></i>
@endif
