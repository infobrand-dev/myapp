<?php

namespace App\Modules\Shortlink\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shortlink\Models\ShortlinkClick;
use App\Modules\Shortlink\Models\ShortlinkCode;
use Illuminate\Http\Request;

class ShortlinkRedirectController extends Controller
{
    public function show(Request $request, string $code)
    {
        $shortlinkCode = ShortlinkCode::where('code', $code)
            ->where('is_active', true)
            ->first();

        if (!$shortlinkCode) {
            abort(404);
        }

        $shortlink = $shortlinkCode->shortlink;

        if (!$shortlink || !$shortlink->is_active) {
            abort(404);
        }

        $targetUrl = $this->appendUtm($shortlink->destination_url, [
            'utm_source'   => $shortlink->utm_source,
            'utm_medium'   => $shortlink->utm_medium,
            'utm_campaign' => $shortlink->utm_campaign,
            'utm_term'     => $shortlink->utm_term,
            'utm_content'  => $shortlink->utm_content,
        ]);

        ShortlinkClick::create([
            'shortlink_id'      => $shortlink->id,
            'shortlink_code_id' => $shortlinkCode->id,
            'code_used'         => $shortlinkCode->code,
            'utm_source'        => $shortlink->utm_source,
            'utm_medium'        => $shortlink->utm_medium,
            'utm_campaign'      => $shortlink->utm_campaign,
            'utm_term'          => $shortlink->utm_term,
            'utm_content'       => $shortlink->utm_content,
            'referer'           => $request->header('referer'),
            'user_agent'        => $request->userAgent(),
            'ip_address'        => $request->ip(),
            'query'             => http_build_query($request->query()),
        ]);

        return redirect()->away($targetUrl);
    }

    protected function appendUtm(string $url, array $utm): string
    {
        $active = array_filter($utm);

        if (empty($active)) {
            return $url;
        }

        $parsed = parse_url($url);
        $base = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
        $base .= $parsed['host'] ?? '';
        $base .= isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $base .= $parsed['path'] ?? '';

        $existingQuery = [];
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $existingQuery);
        }

        $query = http_build_query(array_merge($existingQuery, $active));
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        return $query ? $base . '?' . $query . $fragment : $base . $fragment;
    }
}
