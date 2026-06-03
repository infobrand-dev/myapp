<?php

namespace App\Console\Commands;

use App\Models\StoredFile;
use App\Models\StorageProfile;
use Illuminate\Console\Command;

class AuditStorageProfilesCommand extends Command
{
    protected $signature = 'storage:audit-profiles';

    protected $description = 'List stored files tied to inactive or unreachable storage profiles.';

    public function handle(): int
    {
        $profiles = StorageProfile::query()
            ->withCount('storedFiles')
            ->orderBy('visibility_scope')
            ->orderByDesc('is_active')
            ->orderBy('priority')
            ->get();

        if ($profiles->isEmpty()) {
            $this->info('No storage profiles configured.');
        } else {
            $this->table(
                ['Code', 'Name', 'Driver', 'Scope', 'Active', 'Files', 'Last Error'],
                $profiles->map(fn (StorageProfile $profile) => [
                    $profile->code,
                    $profile->name,
                    $profile->driver,
                    $profile->visibility_scope,
                    $profile->is_active ? 'yes' : 'no',
                    $profile->stored_files_count,
                    $profile->last_error_summary ?: '-',
                ])->all()
            );
        }

        $files = StoredFile::query()
            ->with('storageProfile:id,code,is_active')
            ->whereNotNull('storage_profile_id')
            ->where(function ($query) {
                $query->where('availability_status', '!=', 'available')
                    ->orWhereHas('storageProfile', fn ($builder) => $builder->where('is_active', false));
            })
            ->limit(50)
            ->get();

        if ($files->isEmpty()) {
            $this->info('No at-risk files found.');
        } else {
            $this->table(
                ['ID', 'Category', 'Path', 'Status', 'Profile'],
                $files->map(fn (StoredFile $file) => [
                    $file->id,
                    $file->category,
                    $file->path,
                    $file->availability_status,
                    optional($file->storageProfile)->code ?: '-',
                ])->all()
            );
        }

        $legacyExposures = StoredFile::query()
            ->whereIn('category', ['finance_attachment', 'payment_proof', 'sales_attachment', 'bank_statement'])
            ->where(function ($query) {
                $query->where('availability_status', 'legacy_exposed')
                    ->orWhere('meta->legacy_public_exposed', true);
            })
            ->limit(50)
            ->get();

        if ($legacyExposures->isEmpty()) {
            $this->info('No legacy public-sensitive exposures recorded.');

            return self::SUCCESS;
        }

        $this->warn('Legacy public-sensitive files detected. These should be migrated behind authenticated routes.');
        $this->table(
            ['ID', 'Category', 'Path', 'Status', 'Source'],
            $legacyExposures->map(fn (StoredFile $file) => [
                $file->id,
                $file->category,
                $file->path,
                $file->availability_status,
                $file->origin_owner ?: '-',
            ])->all()
        );

        return self::SUCCESS;
    }
}
