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
        'url',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    protected $appends = [
        'image_url',
    ];

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path
                ? (str_starts_with($this->image_path, 'http') ? $this->image_path : url('storage/' . ltrim($this->image_path, '/')))
                : null
        );
    }
}
