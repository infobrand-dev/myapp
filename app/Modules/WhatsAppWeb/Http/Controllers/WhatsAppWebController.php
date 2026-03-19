<?php

namespace App\Modules\WhatsAppWeb\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppWeb\Support\RuntimeSettings;
use Illuminate\View\View;

class WhatsAppWebController extends Controller
{
    public function index(): View
    {
        $bridgeUrl = rtrim(RuntimeSettings::waWebBridgeUrl(), '/');

        return view('whatsappweb::index', compact('bridgeUrl'));
    }
}
