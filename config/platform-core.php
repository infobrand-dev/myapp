<?php

return [
    'boundary' => [
        'approved_module_references' => [],
        'approved_module_table_touches' => [],
    ],
    'tenant_lifecycle' => [
        'states' => ['pending', 'active', 'suspended', 'closed', 'purged'],
        'allowed_transitions' => [
            'pending' => ['active', 'suspended', 'closed'],
            'active' => ['suspended', 'closed'],
            'suspended' => ['active', 'closed'],
            'closed' => ['purged'],
            'purged' => [],
        ],
        'slug_retention_days' => 30,
        'domain_retention_days' => 30,
    ],
    'entitlement' => [
        'states' => ['installed_module', 'active_module', 'feature', 'limit', 'billing_state'],
        'future_scopes' => ['department', 'team', 'division', 'owner'],
        'byo_chatbot_account_model' => 'App\\Modules\\Chatbot\\Models\\ChatbotAccount',
    ],
    'accounting' => [
        'chart_of_account_model' => 'App\\Modules\\Finance\\Models\\ChartOfAccount',
        'source_references' => [
            'App\\Modules\\Finance\\Models\\FinanceTransaction' => [
                'model' => 'App\\Modules\\Finance\\Models\\FinanceTransaction',
                'route' => 'finance.transactions.show',
                'number_field' => 'reference_number',
                'label' => 'Finance Transaction',
            ],
            'App\\Modules\\Payments\\Models\\Payment' => [
                'model' => 'App\\Modules\\Payments\\Models\\Payment',
                'route' => 'payments.show',
                'number_field' => 'payment_number',
                'label' => 'Payment',
            ],
            'App\\Modules\\Sales\\Models\\Sale' => [
                'model' => 'App\\Modules\\Sales\\Models\\Sale',
                'route' => 'sales.show',
                'number_field' => 'sale_number',
                'label' => 'Sale',
            ],
            'App\\Modules\\Sales\\Models\\SaleQuotation' => [
                'model' => 'App\\Modules\\Sales\\Models\\SaleQuotation',
                'route' => 'sales.quotations.show',
                'number_field' => 'quotation_number',
                'label' => 'Quotation',
            ],
            'App\\Modules\\Sales\\Models\\SaleOrder' => [
                'model' => 'App\\Modules\\Sales\\Models\\SaleOrder',
                'route' => 'sales.orders.show',
                'number_field' => 'order_number',
                'label' => 'Sales Order',
            ],
            'App\\Modules\\Sales\\Models\\SaleReturn' => [
                'model' => 'App\\Modules\\Sales\\Models\\SaleReturn',
                'route' => 'sales.returns.show',
                'number_field' => 'return_number',
                'label' => 'Sale Return',
            ],
            'App\\Modules\\Sales\\Models\\SaleReceivableAdjustment' => [
                'model' => 'App\\Modules\\Sales\\Models\\SaleReceivableAdjustment',
                'route' => 'sales.show',
                'number_field' => 'adjustment_number',
                'label' => 'Sale Receivable Adjustment',
            ],
            'App\\Modules\\Purchases\\Models\\PurchaseRequest' => [
                'model' => 'App\\Modules\\Purchases\\Models\\PurchaseRequest',
                'route' => 'purchases.requests.show',
                'number_field' => 'request_number',
                'label' => 'Purchase Request',
            ],
            'App\\Modules\\Purchases\\Models\\PurchaseOrder' => [
                'model' => 'App\\Modules\\Purchases\\Models\\PurchaseOrder',
                'route' => 'purchases.orders.show',
                'number_field' => 'order_number',
                'label' => 'Purchase Order',
            ],
            'App\\Modules\\Purchases\\Models\\Purchase' => [
                'model' => 'App\\Modules\\Purchases\\Models\\Purchase',
                'route' => 'purchases.show',
                'number_field' => 'purchase_number',
                'label' => 'Purchase',
            ],
            'App\\Modules\\Purchases\\Models\\PurchaseReceipt' => [
                'model' => 'App\\Modules\\Purchases\\Models\\PurchaseReceipt',
                'route' => 'purchases.receipts.show',
                'number_field' => 'receipt_number',
                'label' => 'Purchase Receipt',
            ],
            'App\\Modules\\Inventory\\Models\\StockOpening' => [
                'model' => 'App\\Modules\\Inventory\\Models\\StockOpening',
                'route' => 'inventory.openings.show',
                'number_field' => 'code',
                'label' => 'Opening Stock',
            ],
            'App\\Modules\\Inventory\\Models\\StockAdjustment' => [
                'model' => 'App\\Modules\\Inventory\\Models\\StockAdjustment',
                'route' => 'inventory.adjustments.show',
                'number_field' => 'code',
                'label' => 'Stock Adjustment',
            ],
            'App\\Modules\\Inventory\\Models\\StockTransfer' => [
                'model' => 'App\\Modules\\Inventory\\Models\\StockTransfer',
                'route' => 'inventory.transfers.show',
                'number_field' => 'code',
                'label' => 'Stock Transfer',
            ],
        ],
        'transactional_mail' => [
            'sale_model' => 'App\\Modules\\Sales\\Models\\Sale',
        ],
    ],
    'commerce' => [
        'sale_model' => 'App\\Modules\\Sales\\Models\\Sale',
        'sale_status_cancelled' => 'cancelled',
        'sale_payment_unpaid' => 'unpaid',
        'sale_source_online' => 'online',
        'sale_source_manual' => 'manual',
        'product_track_stock_default_weight' => 1,
    ],
    'payments' => [
        'payment_source_backoffice' => 'backoffice',
        'payment_reconciliation_unreconciled' => 'unreconciled',
    ],
    'purchases' => [
        'bill_pending' => 'pending',
    ],
    'finance' => [
        'entry_mode_standard' => 'standard',
        'entry_mode_transfer' => 'transfer',
    ],
    'api' => [
        'current_version' => 'v1',
        'default_pagination' => 25,
        'max_pagination' => 100,
        'error_code_prefix' => 'platform',
    ],
    'webhooks' => [
        'failure_states' => ['received', 'duplicate', 'processed', 'failed', 'replayed', 'invalid_signature'],
        'retention_days' => 30,
    ],
    'search' => [
        'default_limit' => 10,
        'max_limit' => 25,
        'fuzzy_driver' => 'pg_trgm',
        'fulltext_driver' => 'tsvector',
    ],
    'notifications' => [
        'channels' => ['in_app', 'email', 'web_push', 'future_whatsapp'],
    ],
    'files' => [
        'processing_stages' => ['scan', 'process'],
        'legal_hold_meta_key' => 'legal_hold',
    ],
    'billing' => [
        'platform_midtrans_setting_model' => 'App\\Modules\\Midtrans\\Models\\MidtransSetting',
    ],
    'shipping' => [
        'product_model' => 'App\\Modules\\Products\\Models\\Product',
    ],
    'multitenancy' => [
        'tenant_route_binding_models' => [
            'App\\Models\\User',
            'App\\Models\\StoredFile',
            'App\\Models\\ApprovalRequest',
            'App\\Modules\\Sales\\Models\\Sale',
            'App\\Modules\\PointOfSale\\Models\\PosCashSession',
            'App\\Modules\\SocialMedia\\Models\\SocialAccount',
        ],
    ],
];
