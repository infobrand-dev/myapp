<?php

namespace App\Modules\Products\Models;

use App\Services\StorageAccessService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMedia extends Model
{
    protected $fillable = [
        'tenant_id',
        'product_id',
        'product_variant_id',
        'disk',
        'path',
        'collection_name',
        'alt_text',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function url(): ?string
    {
        return $this->path
            ? app(StorageAccessService::class)->publicUrlFromPath($this->path, $this->disk)
            : null;
    }
}
