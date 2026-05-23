<?php

namespace App\Support\Notifications;

class NotificationTypeRegistry
{
    /**
     * @return array<string, mixed>
     */
    public function definition(string $type): array
    {
        return $this->definitions()[$type] ?? [
            'severity' => 'info',
            'channels' => config('notifications.channel_defaults.info', ['in_app']),
            'recipient_roles' => ['Super-admin', 'Admin'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function normalize(NotificationMessage $message): array
    {
        $definition = $this->definition($message->type);
        $severity = $message->severity ?: $definition['severity'];

        return [
            'severity' => $severity,
            'channels' => $definition['channels'] ?? config('notifications.channel_defaults.' . $severity, ['in_app']),
            'recipient_roles' => array_values(array_unique(array_merge(
                $definition['recipient_roles'] ?? [],
                $message->recipientRoles
            ))),
            'title' => $message->title ?: ($definition['title'] ?? ucfirst(str_replace(['.', '_'], ' ', $message->type))),
            'body' => $message->body ?: ($definition['body'] ?? null),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->definitions();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function definitions(): array
    {
        $warningChannels = config('notifications.channel_defaults.warning', ['in_app', 'web_push']);
        $criticalChannels = config('notifications.channel_defaults.critical', ['in_app', 'web_push', 'email']);
        $infoChannels = config('notifications.channel_defaults.info', ['in_app']);
        $successChannels = config('notifications.channel_defaults.success', ['in_app']);

        return [
            'finance.approval_request_pending' => [
                'severity' => 'warning',
                'channels' => $warningChannels,
                'recipient_roles' => ['Finance Staff', 'Admin', 'Super-admin'],
            ],
            'finance.bank_reconciliation_exception' => [
                'severity' => 'warning',
                'channels' => $warningChannels,
                'recipient_roles' => ['Finance Staff', 'Admin', 'Super-admin'],
            ],
            'finance.tax_document_incomplete' => [
                'severity' => 'warning',
                'channels' => $warningChannels,
                'recipient_roles' => ['Finance Staff', 'Admin', 'Super-admin'],
            ],
            'finance.inventory_gl_mismatch' => [
                'severity' => 'critical',
                'channels' => $criticalChannels,
                'recipient_roles' => ['Finance Staff', 'Inventory Staff', 'Admin', 'Super-admin'],
            ],
            'finance.accounting_period_close_blocked' => [
                'severity' => 'critical',
                'channels' => $criticalChannels,
                'recipient_roles' => ['Finance Staff', 'Admin', 'Super-admin'],
            ],
            'payments.overpayment_detected' => [
                'severity' => 'warning',
                'channels' => $warningChannels,
                'recipient_roles' => ['Finance Staff', 'Admin', 'Super-admin'],
            ],
            'sales.receivable_anomaly' => [
                'severity' => 'warning',
                'channels' => $warningChannels,
                'recipient_roles' => ['Finance Staff', 'Admin', 'Super-admin'],
            ],
            'purchases.payable_anomaly' => [
                'severity' => 'warning',
                'channels' => $warningChannels,
                'recipient_roles' => ['Finance Staff', 'Admin', 'Super-admin'],
            ],
            'inventory.negative_stock_detected' => [
                'severity' => 'warning',
                'channels' => $warningChannels,
                'recipient_roles' => ['Inventory Staff', 'Admin', 'Super-admin'],
            ],
            'finance.unmatched_statement_lines' => [
                'severity' => 'warning',
                'channels' => $warningChannels,
                'recipient_roles' => ['Finance Staff', 'Admin', 'Super-admin'],
            ],
            'payments.payment_posted' => [
                'severity' => 'success',
                'channels' => $successChannels,
                'recipient_roles' => ['Finance Staff', 'Admin', 'Super-admin'],
            ],
            'purchases.receipt_posted' => [
                'severity' => 'success',
                'channels' => $successChannels,
                'recipient_roles' => ['Inventory Staff', 'Finance Staff', 'Admin', 'Super-admin'],
            ],
            'sales.credit_memo_created' => [
                'severity' => 'info',
                'channels' => $infoChannels,
                'recipient_roles' => ['Finance Staff', 'Admin', 'Super-admin'],
            ],
            'purchases.debit_note_created' => [
                'severity' => 'info',
                'channels' => $infoChannels,
                'recipient_roles' => ['Finance Staff', 'Admin', 'Super-admin'],
            ],
            'finance.statement_import_completed' => [
                'severity' => 'info',
                'channels' => $infoChannels,
                'recipient_roles' => ['Finance Staff', 'Admin', 'Super-admin'],
            ],
        ];
    }
}
