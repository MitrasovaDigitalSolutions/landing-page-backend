<?php

namespace App\Models;

use App\Models\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    use HasFactory, BelongsToSite;

    protected $fillable = [
        'site_id',
        'article_category_id',
        'author_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'thumbnail_path',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected $appends = [
        'thumbnail_url',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
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
