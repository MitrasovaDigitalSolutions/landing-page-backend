<?php

namespace App\Models;

use App\Models\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasFactory, BelongsToSite;

    protected $fillable = [
        'site_id',
        'group',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];
}
