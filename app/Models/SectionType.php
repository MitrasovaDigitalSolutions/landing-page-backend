<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SectionType extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'fields',
        'aliases',
        'is_active',
    ];

    protected $casts = [
        'fields' => 'array',
        'aliases' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Resolve a section type by canonical key or alias (in PHP, DB-agnostic).
     */
    public static function resolveForKey(?string $key, ?Collection $types = null): ?self
    {
        if ($key === null) {
            return null;
        }

        $types ??= static::all();

        return $types->first(
            fn (self $type) => $type->key === $key || in_array($key, $type->aliases ?? [], true)
        );
    }
}
