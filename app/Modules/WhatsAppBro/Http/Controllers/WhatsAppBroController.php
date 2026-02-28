<?php

namespace App\Modules\WhatsAppBro\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ModuleRuntimeSettings;
use Illuminate\View\View;

class WhatsAppBroController extends Controller
{
    public function index(): View
    {
        $bridgeUrl = rtrim(ModuleRuntimeSettings::waBroBridgeUrl(), '/');

        return view('whatsappbro::index', compact('bridgeUrl'));
    }
}
