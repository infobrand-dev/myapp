<?php

namespace App\Modules\EmailMarketing\Jobs;

use App\Modules\EmailMarketing\Models\EmailCampaignRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

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

        $html = $campaign->body_html;

        // inject open pixel
        $pixel = '<img src="'.route('email-marketing.track.open', $recipient->tracking_token).'" width="1" height="1" style="display:none;">';
        $html = $html . $pixel;

        // rewrite links with click tracker and original url
        $html = $this->rewriteLinks($html, $recipient->tracking_token);

        Mail::html($html, function ($message) use ($recipient, $campaign) {
            $message->to($recipient->recipient_email, $recipient->recipient_name)
                ->subject($campaign->subject);
        });

        $recipient->update([
            'delivery_status' => 'outgoing',
        ]);
    }

    protected function rewriteLinks(string $html, string $token): string
    {
        return preg_replace_callback('#href=["\\\'](.*?)["\\\']#i', function ($m) use ($token) {
            $url = $m[1];
            // skip mailto/tel
            if (stripos($url, 'mailto:') === 0 || stripos($url, 'tel:') === 0) {
                return $m[0];
            }
            $tracked = route('email-marketing.track.click', $token) . '?u=' . urlencode($url);
            return 'href="' . $tracked . '"';
        }, $html);
    }
}
