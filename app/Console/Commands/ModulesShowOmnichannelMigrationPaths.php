<?php

namespace App\Console\Commands;

use App\Support\ModuleFilesystemAudit;
use Illuminate\Console\Command;

class ModulesShowOmnichannelMigrationPaths extends Command
{
    protected $signature = 'modules:show-omnichannel-migrations';

    protected $description = 'Show exact Omnichannel module migration paths and Linux-safe execution order.';

    /**
     * @var array<int, array{slug: string, dir: string}>
     */
    private array $omnichannelModules = [
        ['slug' => 'conversations', 'dir' => 'Conversations'],
        ['slug' => 'contacts', 'dir' => 'Contacts'],
        ['slug' => 'crm', 'dir' => 'Crm'],
        ['slug' => 'live_chat', 'dir' => 'LiveChat'],
        ['slug' => 'social_media', 'dir' => 'SocialMedia'],
        ['slug' => 'whatsapp_api', 'dir' => 'WhatsAppApi'],
        ['slug' => 'whatsapp_web', 'dir' => 'WhatsAppWeb'],
        ['slug' => 'chatbot', 'dir' => 'Chatbot'],
    ];

    public function handle(ModuleFilesystemAudit $audit): int
    {
        $this->info('Omnichannel module migration order:');
        $this->newLine();

        foreach ($this->omnichannelModules as $index => $module) {
            $base = 'app/Modules/' . $module['dir'];
            $migrationPath = $base . '/Database/Migrations';
            $issues = $audit->issuesForModule([
                'slug' => $module['slug'],
                'name' => $module['dir'],
                '_dir' => $module['dir'],
            ]);

            $this->line(sprintf('%d. %s', $index + 1, $module['slug']));
            $this->line('   Path: ' . $migrationPath);
            $this->line('   Command: /opt/cpanel/ea-php83/root/usr/bin/php artisan migrate --path=' . $migrationPath . ' --force');

            if (!empty($issues)) {
                $this->warn('   Issues: ' . implode('; ', $issues));
            } else {
                $this->line('   Status: filesystem path looks present');
            }

            $this->newLine();
        }

        $this->info('Run these only after app/Modules is deployed with exact Linux-safe casing.');

        return self::SUCCESS;
    }
}
