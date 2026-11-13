<?php

namespace App\Models;

use App\Models\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleCategory extends Model
{
    use HasFactory, BelongsToSite;

    protected $fillable = [
        'site_id',
        'name',
        'slug',
        'description',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
