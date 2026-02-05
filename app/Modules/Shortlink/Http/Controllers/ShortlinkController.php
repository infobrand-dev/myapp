<?php

namespace App\Modules\Shortlink\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shortlink\Models\Shortlink;
use App\Modules\Shortlink\Models\ShortlinkClick;
use App\Modules\Shortlink\Models\ShortlinkCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShortlinkController extends Controller
{
    public function index(Request $request)
    {
        $filterId = $request->get('shortlink_id');

        $allShortlinks = Shortlink::with('primaryCode')
            ->orderByDesc('id')
            ->get(['id', 'title']);

        $shortlinks = Shortlink::with(['primaryCode', 'codes'])
            ->withCount('clicks')
            ->orderByDesc('id')
            ->paginate(15);

        $chartRows = ShortlinkClick::select(
            DB::raw('DATE(created_at) as tanggal'),
            DB::raw('COUNT(*) as total')
        )
            ->when($filterId, fn($q) => $q->where('shortlink_id', $filterId))
            ->where('created_at', '>=', Carbon::now()->subDays(13)->startOfDay())
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'))
            ->get();

        $topReferrers = ShortlinkClick::select('referer', DB::raw('COUNT(*) as total'))
            ->whereNotNull('referer')
            ->when($filterId, fn($q) => $q->where('shortlink_id', $filterId))
            ->groupBy('referer')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $topCodes = ShortlinkClick::select('code_used', DB::raw('COUNT(*) as total'))
            ->when($filterId, fn($q) => $q->where('shortlink_id', $filterId))
            ->groupBy('code_used')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        return view('shortlink::index', [
            'shortlinks'    => $shortlinks,
            'chartLabels'   => $chartRows->pluck('tanggal'),
            'chartValues'   => $chartRows->pluck('total'),
            'topReferrers'  => $topReferrers,
            'topCodes'      => $topCodes,
            'filterId'      => $filterId,
            'allShortlinks' => $allShortlinks,
        ]);
    }

    public function create()
    {
        return view('shortlink::form', [
            'shortlink'      => new Shortlink(),
            'primaryCode'    => null,
            'codes'          => collect(),
            'formAction'     => route('shortlinks.store'),
            'formMethod'     => 'POST',
            'pageTitle'      => 'Buat Shortlink',
            'generatedCode'  => $this->generateCodeSuggestion(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateInput($request);

        DB::transaction(function () use ($data) {
            $shortlink = Shortlink::create([
                'title'           => $data['title'],
                'destination_url' => $data['destination_url'],
                'utm_source'      => $data['utm_source'] ?? null,
                'utm_medium'      => $data['utm_medium'] ?? null,
                'utm_campaign'    => $data['utm_campaign'] ?? null,
                'utm_term'        => $data['utm_term'] ?? null,
                'utm_content'     => $data['utm_content'] ?? null,
                'is_active'       => isset($data['is_active']) ? (bool) $data['is_active'] : true,
                'created_by'      => auth()->id(),
            ]);

            $shortlink->codes()->create([
                'code'       => $data['code'],
                'is_primary' => true,
                'is_active'  => true,
            ]);
        });

        return redirect()->route('shortlinks.index')
            ->with('success', 'Shortlink berhasil dibuat.');
    }

    public function edit(Shortlink $shortlink)
    {
        $shortlink->load(['primaryCode', 'codes']);

        return view('shortlink::form', [
            'shortlink'     => $shortlink,
            'primaryCode'   => $shortlink->primaryCode,
            'codes'         => $shortlink->codes()->latest()->get(),
            'formAction'    => route('shortlinks.update', $shortlink),
            'formMethod'    => 'PUT',
            'pageTitle'     => 'Ubah Shortlink',
            'generatedCode' => null,
        ]);
    }

    public function update(Request $request, Shortlink $shortlink)
    {
        $shortlink->load('primaryCode');

        $data = $this->validateInput($request, $shortlink->primaryCode);

        DB::transaction(function () use ($shortlink, $data) {
            $shortlink->fill([
                'title'           => $data['title'],
                'destination_url' => $data['destination_url'],
                'utm_source'      => $data['utm_source'] ?? null,
                'utm_medium'      => $data['utm_medium'] ?? null,
                'utm_campaign'    => $data['utm_campaign'] ?? null,
                'utm_term'        => $data['utm_term'] ?? null,
                'utm_content'     => $data['utm_content'] ?? null,
                'is_active'       => isset($data['is_active']) ? (bool) $data['is_active'] : true,
                'updated_by'      => auth()->id(),
            ])->save();

            $currentPrimary = $shortlink->primaryCode;
            $desiredCode = $data['code'];

            if ($currentPrimary && $currentPrimary->code === $desiredCode) {
                if (!$currentPrimary->is_primary) {
                    $currentPrimary->is_primary = true;
                    $currentPrimary->save();
                }
            } else {
                if ($currentPrimary) {
                    $currentPrimary->is_primary = false;
                    $currentPrimary->save();
                }

                $shortlink->codes()->create([
                    'code'       => $desiredCode,
                    'is_primary' => true,
                    'is_active'  => true,
                ]);
            }
        });

        return redirect()->route('shortlinks.index')
            ->with('success', 'Shortlink berhasil diperbarui.');
    }

    protected function validateInput(Request $request, ShortlinkCode $primaryCode = null)
    {
        $primaryId = $primaryCode ? $primaryCode->id : null;

        return $request->validate([
            'title'           => 'nullable|string|max:255',
            'destination_url' => 'required|url',
            'code'            => 'required|alpha_dash|unique:shortlink_codes,code,' . $primaryId,
            'utm_source'      => 'nullable|string|max:191',
            'utm_medium'      => 'nullable|string|max:191',
            'utm_campaign'    => 'nullable|string|max:191',
            'utm_term'        => 'nullable|string|max:191',
            'utm_content'     => 'nullable|string|max:191',
            'is_active'       => 'sometimes|boolean',
        ]);
    }

    protected function generateCodeSuggestion(): string
    {
        do {
            $code = Str::lower(Str::random(8));
        } while (ShortlinkCode::where('code', $code)->exists());

        return $code;
    }
}
