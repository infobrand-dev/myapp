<?php

namespace App\Modules\WhatsAppApi\Console\Commands;

use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Support\BooleanQuery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckWhatsAppInstances extends Command
{
    protected $signature = 'whatsapp:check-instances';
    protected $description = 'Ping WhatsApp API instances and update status/health timestamp';

    public function handle(): int
    {
        $instances = BooleanQuery::apply(WhatsAppInstance::query(), 'is_active', true)->get();
        $success = 0;
        $fail = 0;

        foreach ($instances as $instance) {
            $url = rtrim($instance->api_base_url ?? '', '/') . '/health';
            if (!$instance->api_base_url) {
                $instance->update([
                    'status' => 'disconnected',
                    'last_health_check_at' => now(),
                    'last_error' => 'api_base_url not set',
                ]);
                $fail++;
                continue;
            }

            try {
                $resp = Http::withToken($instance->api_token)->timeout(5)->get($url);
                if ($resp->successful()) {
                    $instance->update([
                        'status' => 'connected',
                        'last_health_check_at' => now(),
                        'last_error' => null,
                    ]);
                    $success++;
                } else {
                    $instance->update([
                        'status' => 'error',
                        'last_health_check_at' => now(),
                        'last_error' => $resp->body(),
                    ]);
                    $fail++;
                }
            } catch (\Throwable $e) {
                $instance->update([
                    'status' => 'error',
                    'last_health_check_at' => now(),
                    'last_error' => $e->getMessage(),
                ]);
                $fail++;
            }
        }

        $this->info("Health check done: {$success} ok / {$fail} fail.");
        return Command::SUCCESS;
    }
}
