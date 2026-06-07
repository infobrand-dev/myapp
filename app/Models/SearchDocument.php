<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchDocument extends Model
{
    protected $table = 'search_documents';

    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'document_type',
        'document_id',
        'title',
        'subtitle',
        'snippet',
        'url',
        'search_vector',
        'meta',
        'indexed_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'indexed_at' => 'datetime',
    ];
}
