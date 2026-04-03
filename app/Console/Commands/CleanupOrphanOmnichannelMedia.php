<?php

namespace App\Console\Commands;

use App\Services\TenantStorageUsageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanOmnichannelMedia extends Command
{
    protected $signature = 'media:cleanup-orphans {--dry-run : Tampilkan file orphan tanpa menghapus}';

    protected $description = 'Delete orphaned omnichannel media files that are no longer referenced by the database';

    public function handle(TenantStorageUsageService $usage): int
    {
        $disk = Storage::disk('public');
        $directories = [
            'wa_messages',
            'social_messages',
            'wa_templates/headers',
        ];

        $referenced = array_fill_keys($usage->publicReferencedPathsForAllTenants(), true);
        $candidates = [];

        foreach ($directories as $directory) {
            foreach ($disk->allFiles($directory) as $path) {
                $normalized = ltrim(str_replace('\\', '/', $path), '/');
                if ($normalized !== '') {
                    $candidates[] = $normalized;
                }
            }
        }

        $orphaned = array_values(array_filter($candidates, static fn ($path) => !isset($referenced[$path])));

        if ($orphaned === []) {
            $this->info('No orphaned omnichannel media files found.');

            return self::SUCCESS;
        }

        foreach ($orphaned as $path) {
            $this->line(($this->option('dry-run') ? '[dry-run] ' : '') . $path);
        }

        if (!$this->option('dry-run')) {
            $disk->delete($orphaned);
        }

        $this->info(sprintf(
            '%s %d orphaned omnichannel media file(s).',
            $this->option('dry-run') ? 'Detected' : 'Deleted',
            count($orphaned)
        ));

        return self::SUCCESS;
    }
}
