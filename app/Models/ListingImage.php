<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['image_path', 'sort_order'])]
class ListingImage extends Model
{
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public static function isManagedPath(string $path): bool
    {
        return preg_match('~\Alistings/[A-Za-z0-9_-]+\.(?:jpe?g|png|webp)\z~i', $path) === 1;
    }

    public function publicUrl(): string
    {
        return self::isManagedPath($this->image_path)
            ? Storage::disk('public')->url($this->image_path)
            : asset('images/listing-placeholder.png');
    }

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}
