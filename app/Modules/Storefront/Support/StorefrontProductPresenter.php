<?php

namespace App\Modules\Storefront\Support;

use App\Modules\Products\Models\Product;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class StorefrontProductPresenter
{
    public function imageUrl(Product $product): ?string
    {
        $gallery = $product->relationLoaded('media') ? $product->media : $product->media()->get();
        $mediaUrl = optional($gallery->first())->url();

        if ($mediaUrl) {
            return $mediaUrl;
        }

        $path = (string) ($product->featured_image_path ?? '');
        if ($path === '') {
            return null;
        }

        $publicStoragePath = public_path('storage/' . $path);
        if (File::exists($publicStoragePath)) {
            return asset('storage/' . $path);
        }

        $publicDirectPath = public_path($path);
        if (File::exists($publicDirectPath)) {
            return asset($path);
        }

        return Storage::disk('public')->url($path);
    }
}
