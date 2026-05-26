<?php

namespace App\Jobs;

use App\Mail\AccountingTransactionalDocumentMail;
use App\Models\TenantTransactionalMailLog;
use App\Services\TenantTransactionalMailConfigResolver;
use App\Services\TenantTransactionalMailerFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendTenantTransactionalMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $logId,
        public array $payload,
    ) {
    }

    public function handle(
        TenantTransactionalMailConfigResolver $configResolver,
        TenantTransactionalMailerFactory $mailerFactory,
    ): void {
        $log = TenantTransactionalMailLog::query()->find($this->logId);
        if (!$log) {
            return;
        }

        $setting = $configResolver->requireEnabled((int) $log->tenant_id);
        $mailer = $mailerFactory->configure('tenant_transactional_' . $log->id, $setting);
        $identity = $configResolver->senderIdentity($setting);

        try {
            Mail::mailer($mailer)
                ->to($this->payload['recipient_email'], $this->payload['recipient_name'] ?? null)
                ->send(new AccountingTransactionalDocumentMail(
                    subjectLine: $this->payload['subject'],
                    viewName: $this->payload['view'],
                    viewData: $this->payload['data'],
                    fromEmail: $identity['from_email'],
                    fromName: $identity['from_name'],
                    replyToEmail: $identity['reply_to_email'],
                ));

            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
