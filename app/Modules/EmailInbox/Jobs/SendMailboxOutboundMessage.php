<?php

namespace App\Modules\EmailInbox\Jobs;

use App\Modules\EmailInbox\Models\EmailMessage;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class SendMailboxOutboundMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $messageId)
    {
    }

    public function handle(): void
    {
        $message = EmailMessage::query()->with('account')->find($this->messageId);
        if (!$message || !$message->account) {
            return;
        }

        TenantContext::setCurrentId((int) $message->tenant_id);

        try {
            $mailer = 'email_inbox_dynamic_' . $message->id;
            $account = $message->account;

            if (!$account->outbound_host || !$account->outbound_username || !$account->outbound_password) {
                throw new RuntimeException('Konfigurasi outbound SMTP belum lengkap.');
            }

            config([
                'mail.mailers.' . $mailer => [
                    'transport' => 'smtp',
                    'host' => $account->outbound_host,
                    'port' => $account->outbound_port ?: 587,
                    'encryption' => $account->outbound_encryption ?: 'tls',
                    'username' => $account->outbound_username,
                    'password' => $account->outbound_password,
                    'timeout' => 30,
                ],
            ]);

            $to = collect($message->to_json)->pluck('email')->filter()->values()->all();
            if (empty($to)) {
                throw new RuntimeException('Penerima email tidak ditemukan.');
            }

            Mail::mailer($mailer)->html(
                $message->body_html ?: nl2br(e((string) $message->body_text)),
                function ($mail) use ($message, $account, $to): void {
                    $mail->to($to)
                        ->subject((string) $message->subject)
                        ->from($account->email_address, $account->outbound_from_name ?: $account->name);

                    foreach (collect($message->cc_json)->pluck('email')->filter()->all() as $cc) {
                        $mail->cc($cc);
                    }

                    foreach (collect($message->bcc_json)->pluck('email')->filter()->all() as $bcc) {
                        $mail->bcc($bcc);
                    }

                    if ($account->outbound_reply_to) {
                        $mail->replyTo($account->outbound_reply_to);
                    }
                }
            );

            $message->update([
                'status' => 'sent',
                'sent_at' => now(),
                'metadata' => array_merge($message->metadata ?? [], [
                    'send_job' => [
                        'sent_via' => 'smtp',
                        'sent_at' => now()->toIso8601String(),
                    ],
                ]),
            ]);
        } catch (\Throwable $e) {
            $message->update([
                'status' => 'failed',
                'metadata' => array_merge($message->metadata ?? [], [
                    'send_error' => $e->getMessage(),
                ]),
            ]);

            throw $e;
        } finally {
            TenantContext::forget();
        }
    }
}
