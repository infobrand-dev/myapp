<?php

namespace App\Modules\EmailMarketing\Jobs;

use App\Modules\EmailMarketing\Models\EmailCampaignRecipient;
use App\Modules\EmailMarketing\Models\EmailAttachment;
use App\Modules\EmailMarketing\Models\EmailAttachmentTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class SendCampaignEmailRecipient implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public EmailCampaignRecipient $recipient)
    {
    }

    public function handle(): void
    {
        $recipient = $this->recipient->fresh();
        if (!$recipient) {
            return;
        }

        $campaign = $recipient->campaign()->first();
        if (!$campaign) {
            return;
        }

        if ($campaign->status === 'scheduled') {
            $campaign->update([
                'status' => 'running',
                'started_at' => $campaign->started_at ?: now(),
            ]);
        }

        $html = $this->replacePlaceholders($campaign->body_html, $recipient, $campaign);
        $html = $this->inlineCss($html);
        $html = $this->makeUrlsAbsolute($html);

        // inject open pixel
        $pixel = '<img src="'.route('email-marketing.track.open', $recipient->tracking_token).'" width="1" height="1" style="display:none;">';
        $html = $html . $pixel;

        // rewrite links with click tracker and original url
        $html = $this->rewriteLinks($html, $recipient->tracking_token);

        $attachments = $campaign->attachments()->get();
        $dynamicTemplates = $campaign->dynamicTemplates()->get();

        Mail::html($html, function ($message) use ($recipient, $campaign, $attachments, $dynamicTemplates) {
            $message->to($recipient->recipient_email, $recipient->recipient_name)
                ->subject($campaign->subject)
                ->getHeaders()
                ->addTextHeader('X-Recipient-Token', $recipient->tracking_token);

            foreach ($attachments as $att) {
                if ($att->type === 'static' && $att->path) {
                    $message->attach(Storage::path($att->path), [
                        'as' => $att->filename,
                        'mime' => $att->mime ?? null,
                    ]);
                } elseif ($att->type === 'dynamic' && $att->template_html) {
                    $rendered = $this->replacePlaceholders($att->template_html, $recipient, $campaign);
                    $pdf = Pdf::loadHTML($rendered)->setPaper('a4');
                    $message->attachData($pdf->output(), $att->filename ?? 'attachment.pdf', [
                        'mime' => 'application/pdf',
                    ]);
                }
            }

            foreach ($dynamicTemplates as $tpl) {
                $rendered = $this->replacePlaceholders($tpl->html, $recipient, $campaign);
                $pdf = Pdf::loadHTML($rendered)->setPaper('a4');
                $filename = $tpl->filename ?: ($tpl->name . '.pdf');
                $message->attachData($pdf->output(), $filename, [
                    'mime' => $tpl->mime ?? 'application/pdf',
                ]);
            }
        });

        $recipient->update([
            'delivery_status' => 'outgoing',
        ]);
    }

    /**
     * Inline CSS so email clients keep the styling.
     */
    protected function inlineCss(string $html): string
    {
        $css = '';
        if (preg_match_all('#<style[^>]*>(.*?)</style>#is', $html, $matches)) {
            $css = implode("\n", $matches[1]);
            $html = preg_replace('#<style[^>]*>.*?</style>#is', '', $html);
        }

        $inliner = new CssToInlineStyles();
        return $inliner->convert($html, $css);
    }

    /**
     * Ensure src/href that are relative become absolute with APP_URL,
     * so images/buttons keep styling assets when viewed in inbox.
     */
    protected function makeUrlsAbsolute(string $html): string
    {
        $base = rtrim(config('app.url'), '/');
        // href/src starting with / or without scheme but not mailto/tel/data
        $html = preg_replace_callback('#(href|src)=["\'](\/[^"\']*|(?!(?:https?:|mailto:|tel:|data:))[^"\':]+)["\']#i', function ($m) use ($base) {
            $attr = $m[1];
            $url  = $m[2];
            $absolute = str_starts_with($url, '/') ? $base.$url : $base.'/'.$url;
            return $attr.'="'.$absolute.'"';
        }, $html);
        return $html;
    }

    protected function rewriteLinks(string $html, string $token): string
    {
        return preg_replace_callback('#href=["\\\'](.*?)["\\\']#i', function ($m) use ($token) {
            $url = $m[1];
            // skip mailto/tel
            if (stripos($url, 'mailto:') === 0 || stripos($url, 'tel:') === 0) {
                return $m[0];
            }
            $tracked = url(route('email-marketing.track.click', $token, false)) . '?u=' . urlencode($url);
            return 'href="' . $tracked . '"';
        }, $html);
    }

    protected function replacePlaceholders(string $html, $recipient, $campaign): string
    {
        $unsubscribe = url(route('email-marketing.unsubscribe', $recipient->tracking_token, false));
        $map = [
            '{{name}}' => $recipient->recipient_name ?? '',
            '{{email}}' => $recipient->recipient_email ?? '',
            '{{track_click}}' => url(route('email-marketing.track.click', $recipient->tracking_token, false)),
            '{{unsubscribe}}' => $unsubscribe,
        ];
        return strtr($html, $map);
    }
}
