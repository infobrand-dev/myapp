<?php

namespace App\Services;

use App\Contracts\UtasWebhookNotificationSender;
use App\Mail\UtasPaidWebhookNotificationMail;
use RuntimeException;

class NativeUtasWebhookNotificationSender implements UtasWebhookNotificationSender
{
    public function sendPaidNotification(string $to, array $payload): void
    {
        $mail = new UtasPaidWebhookNotificationMail($payload);
        $fromAddress = $this->sanitizeHeaderValue((string) config('mail.from.address', 'no-reply@example.com'));
        $fromName = $this->sanitizeHeaderValue((string) config('mail.from.name', config('app.name', 'MyApp')));
        $subject = $this->encodeSubject((string) ($mail->envelope()->subject ?? 'UTAS Paid Order'));
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->formatMailbox($fromAddress, $fromName),
            'Reply-To: ' . $fromAddress,
            'X-Mailer: PHP/' . PHP_VERSION,
        ];

        $sent = @mail(
            $this->sanitizeHeaderValue($to),
            $subject,
            $mail->render(),
            implode("\r\n", $headers)
        );

        if ($sent) {
            return;
        }

        $error = error_get_last();

        throw new RuntimeException('Native UTAS mail() failed: ' . trim((string) ($error['message'] ?? 'unknown error')));
    }

    private function formatMailbox(string $address, string $name): string
    {
        if ($name === '') {
            return $address;
        }

        return sprintf('"%s" <%s>', addcslashes($name, '"\\'), $address);
    }

    private function encodeSubject(string $subject): string
    {
        $subject = $this->sanitizeHeaderValue($subject);

        if (function_exists('mb_encode_mimeheader')) {
            return mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n");
        }

        return $subject;
    }

    private function sanitizeHeaderValue(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }
}
