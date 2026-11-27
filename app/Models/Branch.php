<?php

namespace App\Models;

use App\Models\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory, BelongsToSite;

    protected $fillable = [
        'site_id',
        'name',
        'address',
        'phone',
        'whatsapp',
        'maps_embed',
        'is_primary',
        'images',
        'order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'images' => 'array',
        'order' => 'integer',
    ];

    protected $appends = [
        'image_urls',
    ];

    protected function imageUrls(): Attribute
    {
        return Attribute::make(
            get: fn () => collect($this->images ?? [])
                ->map(fn ($path) => str_starts_with($path, 'http') ? $path : url('storage/' . ltrim($path, '/')))
                ->values()
                ->all()
        );
    }
}
