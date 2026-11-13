<?php

namespace App\Models\Traits;

use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToSite
{
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function scopeForSite(Builder $query, ?string $siteId = null): Builder
    {
        $id = $siteId;

        if ($id === null && app()->bound('current_site')) {
            $currentSite = app('current_site');
            $id = $currentSite ? $currentSite->id : null;
        }

        if ($id !== null) {
            return $query->where($this->getTable() . '.site_id', $id);
        }

        return $query;
    }
}
