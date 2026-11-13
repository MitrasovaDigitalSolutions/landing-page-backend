<?php

namespace App\Models;

use App\Models\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Portfolio extends Model
{
    use HasFactory, BelongsToSite;

    protected $fillable = [
        'site_id',
        'service_type_id',
        'title',
        'slug',
        'client_name',
        'completion_date',
        'description',
        'thumbnail_path',
        'gallery_images',
        'order',
        'is_featured',
    ];

    protected $casts = [
        'completion_date' => 'date',
        'gallery_images' => 'array',
        'is_featured' => 'boolean',
        'order' => 'integer',
    ];

    protected $appends = [
        'thumbnail_url',
        'gallery_image_urls',
    ];

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->thumbnail_path
                ? (str_starts_with($this->thumbnail_path, 'http') ? $this->thumbnail_path : url('storage/' . ltrim($this->thumbnail_path, '/')))
                : null
        );
    }

    protected function galleryImageUrls(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! is_array($this->gallery_images)) {
                    return [];
                }

                return array_map(function ($img) {
                    if (! $img) return null;
                    return str_starts_with($img, 'http') ? $img : url('storage/' . ltrim($img, '/'));
                }, $this->gallery_images);
            }
        );
    }
}
