<?php

namespace App\Modules\WhatsAppApi\ViewModels;

use App\Modules\WhatsAppApi\Models\WhatsAppInstance;

class InstanceHealthViewModel
{
    public static function summary(): array
    {
        $instances = WhatsAppInstance::orderBy('name')->get();
        return [
            'total' => $instances->count(),
            'connected' => $instances->where('status', 'connected')->count(),
            'error' => $instances->where('status', 'error')->count(),
            'disconnected' => $instances->where('status', 'disconnected')->count(),
            'instances' => $instances,
        ];
    }
}
