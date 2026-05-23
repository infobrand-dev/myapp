<?php

return [
    'products' => [
        'standard_types' => ['simple', 'service'],
        'advanced_types' => ['simple', 'variant', 'service'],
    ],
    'accounting' => [
        'default_product_line' => 'accounting',
        'advanced_feature' => \App\Support\PlanFeature::ADVANCED_REPORTS,
        'preference_key' => 'accounting_ui_mode',
    ],
];
