<?php

namespace App\Models;

use App\Models\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessUnit extends Model
{
    use HasFactory, BelongsToSite;

    protected $fillable = [
        'site_id',
        'name',
        'address',
        'image_path',
        'images',
        'url',
        'order',
    ];

    protected $casts = [
        'images' => 'array',
        'order' => 'integer',
    ];

    protected $appends = [
        'image_url',
        'image_urls',
    ];

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path
                ? (str_starts_with($this->image_path, 'http') ? $this->image_path : url('storage/' . ltrim($this->image_path, '/')))
                : null
        );
    }

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
