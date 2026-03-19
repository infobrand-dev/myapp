<?php

namespace App\Modules\Conversations\Contracts;

use App\Modules\Conversations\Data\ConversationIngestionResult;
use App\Modules\Conversations\Data\InboxMessageEnvelope;

interface InboxMessageIngester
{
    public function ingest(InboxMessageEnvelope $envelope): ConversationIngestionResult;
}
