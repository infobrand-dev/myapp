<?php

namespace App\Modules\WhatsAppBro\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class WhatsAppBroController extends Controller
{
    public function index(): View
    {
        $bridgeUrl = rtrim(config('modules.whatsapp_bro.bridge_url'), '/');

        return view('whatsappbro::index', compact('bridgeUrl'));
    }
}
