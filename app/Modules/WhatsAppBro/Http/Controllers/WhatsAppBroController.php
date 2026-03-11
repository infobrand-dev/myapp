<?php

namespace App\Modules\WhatsAppBro\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\WhatsAppBro\Support\RuntimeSettings;
use Illuminate\View\View;

class WhatsAppBroController extends Controller
{
    public function index(): View
    {
        $bridgeUrl = rtrim(RuntimeSettings::waBroBridgeUrl(), '/');

        return view('whatsappbro::index', compact('bridgeUrl'));
    }
}
