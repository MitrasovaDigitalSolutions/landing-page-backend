<?php

namespace App\Models;

use App\Models\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use HasFactory, BelongsToSite;

    protected $fillable = [
        'site_id',
        'service_type_id',
        'name',
        'slug',
        'short_desc',
        'body',
        'icon_path',
        'thumbnail_path',
        'features',
        'is_featured',
        'is_active',
        'order',
    ];

    protected $casts = [
        'features' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    protected $appends = [
        'icon_url',
        'thumbnail_url',
    ];

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    protected function iconUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->icon_path
                ? (str_starts_with($this->icon_path, 'http') ? $this->icon_path : url('storage/' . ltrim($this->icon_path, '/')))
                : null
        );
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->thumbnail_path
                ? (str_starts_with($this->thumbnail_path, 'http') ? $this->thumbnail_path : url('storage/' . ltrim($this->thumbnail_path, '/')))
                : null
        );
    }
}
