<?php

namespace App\Models;

use App\Models\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasFactory, BelongsToSite;

    protected $fillable = [
        'site_id',
        'name',
        'title',
        'avatar_path',
        'message',
        'rating',
        'order',
        'is_active',
    ];

    protected $casts = [
        'rating' => 'integer',
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'avatar_url',
    ];

    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->avatar_path
                ? (str_starts_with($this->avatar_path, 'http') ? $this->avatar_path : url('storage/' . ltrim($this->avatar_path, '/')))
                : null
        );
    }
}
