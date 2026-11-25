<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['type', 'name', 'slug', 'description', 'category_id', 'subcategory_id', 'city_id', 'area_id', 'price'])]
class Listing extends Model
{
    #[Scope]
    protected function forCards(Builder $query): void
    {
        $query->with(['primaryImage', 'category', 'city', 'area'])->latest()->orderByDesc('id');
    }

    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        if (isset($filters['q'])) {
            $phrase = '%'.strtr($filters['q'], ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
            $query->where(function (Builder $search) use ($phrase) {
                $search->whereRaw("listings.name LIKE ? ESCAPE '!'", [$phrase])
                    ->orWhereRaw("listings.description LIKE ? ESCAPE '!'", [$phrase])
                    ->orWhereHas('category', fn (Builder $category) => $category->whereRaw("name LIKE ? ESCAPE '!'", [$phrase]))
                    ->orWhereHas('subcategory', fn (Builder $subcategory) => $subcategory->whereRaw("name LIKE ? ESCAPE '!'", [$phrase]));
            });
        }

        foreach (['category', 'subcategory', 'city', 'area'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field.'_id', $filters[$field]);
            }
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['state'])) {
            $query->whereRelation('city', 'state_id', $filters['state']);
        }

        if (isset($filters['country'])) {
            $query->whereRelation('city.state', 'country_id', $filters['country']);
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }
    }

    #[Scope]
    protected function sorted(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->reorder('created_at')->orderBy('id'),
            'price_asc' => $query->reorder('price')->orderByDesc('id'),
            'price_desc' => $query->reorder('price', 'desc')->orderByDesc('id'),
            default => null,
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ListingImage::class)->ofMany(['sort_order' => 'min', 'id' => 'min']);
    }

    public function formattedPrice(): string
    {
        [$rupees, $paise] = explode('.', $this->price);
        $grouped = preg_replace('/\B(?=(?:\d{2})*\d{3}$)/', ',', $rupees);

        return "\u{20B9}".$grouped.'.'.$paise;
    }

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }
}
