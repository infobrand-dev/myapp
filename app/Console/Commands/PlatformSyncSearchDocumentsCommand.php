<?php

namespace App\Console\Commands;

use App\Services\Search\SearchDocumentIndexer;
use Illuminate\Console\Command;

class PlatformSyncSearchDocumentsCommand extends Command
{
    protected $signature = 'platform:sync-search-documents';

    protected $description = 'Rebuild platform global search documents for core searchable entities.';

    public function handle(SearchDocumentIndexer $indexer): int
    {
        $indexer->rebuild();

        $this->info('Platform search documents synced.');

        return self::SUCCESS;
    }
}
