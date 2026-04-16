<?php

namespace App\Modules\WhatsAppWeb\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppWeb\Support\RuntimeSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WhatsAppWebController extends Controller
{
    public function index(): View
    {
        $bridgeUrl = rtrim(RuntimeSettings::waWebBridgeUrl(), '/');
        $bridgeToken = RuntimeSettings::waWebWebhookToken();
        $bridgeStatus = $this->checkBridge($bridgeUrl, $bridgeToken);

        return view('whatsappweb::index', compact('bridgeUrl', 'bridgeToken', 'bridgeStatus'));
    }

    private function checkBridge(string $bridgeUrl, ?string $bridgeToken): array
    {
        if (blank($bridgeUrl)) {
            return [
                'reachable'   => false,
                'url_missing' => true,
                'error'       => 'Bridge URL belum dikonfigurasi.',
            ];
        }

        try {
            $request = Http::timeout(3)->acceptJson();

            if ($bridgeToken) {
                $request = $request->withHeaders(['X-Bridge-Token' => $bridgeToken]);
            }

            $response = $request->get(rtrim($bridgeUrl, '/') . '/status', ['clientId' => 'default']);

            if ($response->successful()) {
                return ['reachable' => true, 'url_missing' => false, 'error' => null];
            }

            return [
                'reachable'   => false,
                'url_missing' => false,
                'error'       => 'Bridge merespons HTTP ' . $response->status() . '. Periksa log bridge server.',
            ];
        } catch (\Throwable $e) {
            return [
                'reachable'   => false,
                'url_missing' => false,
                'error'       => Str::limit($e->getMessage(), 100),
            ];
        }
    }
}
