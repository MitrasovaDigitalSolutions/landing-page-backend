<?php

namespace App\Models;

use App\Models\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceType extends Model
{
    use HasFactory, BelongsToSite;

    protected $fillable = [
        'site_id',
        'name',
        'slug',
        'description',
        'icon',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    protected $appends = [
        'icon_url',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    protected function iconUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->icon && (str_contains($this->icon, '/') || str_contains($this->icon, '.'))
                ? (str_starts_with($this->icon, 'http') ? $this->icon : url('storage/' . ltrim($this->icon, '/')))
                : null
        );
    }
}
