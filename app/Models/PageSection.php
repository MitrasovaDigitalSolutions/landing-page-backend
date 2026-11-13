<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'key',
        'title',
        'subtitle',
        'body',
        'payload',
        'image_path',
        'order',
    ];

    protected $casts = [
        'payload' => 'array',
        'order' => 'integer',
    ];

    protected $appends = [
        'image_url',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path
                ? (str_starts_with($this->image_path, 'http') ? $this->image_path : url('storage/' . ltrim($this->image_path, '/')))
                : null
        );
    }
}
