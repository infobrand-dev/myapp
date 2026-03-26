<?php

namespace App\Modules\EmailInbox\Services;

use App\Modules\EmailInbox\Contracts\MailboxProvider;
use App\Modules\EmailInbox\Models\EmailAccount;
use App\Modules\EmailInbox\Models\EmailFolder;
use App\Modules\EmailInbox\Models\EmailMessage;
use App\Modules\EmailInbox\Models\EmailSyncRun;
use Carbon\Carbon;
use RuntimeException;

class ImapMailboxProvider implements MailboxProvider
{
    public function fetch(EmailAccount $account, EmailSyncRun $syncRun): array
    {
        if (!function_exists('imap_open')) {
            throw new RuntimeException('Ekstensi PHP imap belum aktif. Inbound sync dihentikan dengan aman.');
        }

        $folderName = 'INBOX';
        $folder = EmailFolder::query()->firstOrCreate(
            [
                'tenant_id' => $account->tenant_id,
                'account_id' => $account->id,
                'name' => $folderName,
            ],
            [
                'type' => 'inbox',
                'is_selectable' => true,
            ]
        );

        $mailbox = sprintf(
            '{%s:%d/imap%s}%s',
            $account->inbound_host,
            $account->inbound_port ?: 993,
            $account->inbound_validate_cert ? '/ssl' : '/ssl/novalidate-cert',
            $folderName
        );

        $stream = @imap_open($mailbox, $account->inbound_username, $account->inbound_password);

        if (!$stream) {
            throw new RuntimeException((string) imap_last_error() ?: 'Gagal terhubung ke server IMAP.');
        }

        try {
            $uids = imap_search($stream, 'ALL', SE_UID) ?: [];
            rsort($uids);

            $limit = (int) config('modules.email_inbox.fetch_limit', 20);
            $uids = array_slice($uids, 0, max(1, $limit));

            $created = 0;
            $updated = 0;

            foreach ($uids as $uid) {
                $overview = imap_fetch_overview($stream, (string) $uid, FT_UID);
                $overview = $overview[0] ?? null;
                if (!$overview) {
                    continue;
                }

                $bodyText = trim((string) imap_fetchbody($stream, (string) $uid, '1', FT_UID | FT_PEEK));
                if ($bodyText === '') {
                    $bodyText = trim((string) imap_body($stream, (string) $uid, FT_UID | FT_PEEK));
                }

                $payload = [
                    'tenant_id' => $account->tenant_id,
                    'account_id' => $account->id,
                    'folder_id' => $folder->id,
                    'direction' => 'inbound',
                    'status' => 'received',
                    'message_id' => $overview->message_id ?? null,
                    'in_reply_to' => $overview->in_reply_to ?? null,
                    'subject' => $overview->subject ?? '(Tanpa subject)',
                    'from_email' => $this->extractEmail((string) ($overview->from ?? '')),
                    'from_name' => $this->extractName((string) ($overview->from ?? '')),
                    'to_json' => $this->wrapAddress($account->email_address, $account->outbound_from_name ?: $account->name),
                    'sent_at' => $this->toCarbon($overview->date ?? null),
                    'received_at' => $this->toCarbon($overview->date ?? null) ?: now(),
                    'is_read' => (bool) ($overview->seen ?? false),
                    'read_at' => !empty($overview->seen) ? now() : null,
                    'has_attachments' => false,
                    'body_text' => $bodyText,
                    'body_html' => nl2br(e($bodyText)),
                    'raw_headers' => [
                        'from' => $overview->from ?? null,
                        'to' => $overview->to ?? null,
                        'date' => $overview->date ?? null,
                    ],
                    'sync_uid' => (int) $uid,
                    'metadata' => [
                        'imap' => [
                            'uid' => (int) $uid,
                            'msgno' => (int) imap_msgno($stream, (string) $uid),
                        ],
                    ],
                ];

                $message = EmailMessage::query()->firstOrNew([
                    'tenant_id' => $account->tenant_id,
                    'account_id' => $account->id,
                    'folder_id' => $folder->id,
                    'sync_uid' => (int) $uid,
                ]);

                $exists = $message->exists;
                $message->fill($payload);
                $message->save();

                if ($exists) {
                    $updated++;
                } else {
                    $created++;
                }
            }

            $folder->update([
                'last_uid' => empty($uids) ? $folder->last_uid : max($uids),
            ]);

            return [
                'created' => $created,
                'updated' => $updated,
                'fetched_uids' => count($uids),
            ];
        } finally {
            imap_close($stream);
        }
    }

    private function extractEmail(string $formatted): ?string
    {
        if (preg_match('/<([^>]+)>/', $formatted, $matches)) {
            return trim($matches[1]);
        }

        return filter_var(trim($formatted), FILTER_VALIDATE_EMAIL) ? trim($formatted) : null;
    }

    private function extractName(string $formatted): ?string
    {
        if (preg_match('/^(.*)<[^>]+>$/', $formatted, $matches)) {
            return trim(trim($matches[1]), '" ');
        }

        return null;
    }

    private function wrapAddress(?string $email, ?string $name = null): array
    {
        if (!$email) {
            return [];
        }

        return [[
            'email' => $email,
            'name' => $name,
        ]];
    }

    private function toCarbon(?string $date): ?Carbon
    {
        if (!$date) {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (\Throwable) {
            return null;
        }
    }
}
