<?php

namespace App\Models;

use App\Models\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaqCategory extends Model
{
    use HasFactory, BelongsToSite;

    protected $fillable = [
        'site_id',
        'name',
        'slug',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class)->orderBy('order');
    }
}
