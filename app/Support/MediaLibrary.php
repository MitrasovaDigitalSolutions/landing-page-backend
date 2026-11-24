<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Branch;
use App\Models\BusinessUnit;
use App\Models\PageSection;
use App\Models\Partner;
use App\Models\Portfolio;
use App\Models\Service;
use App\Models\Site;
use App\Models\SiteSetting;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Storage;

/**
 * Inventaris file gambar di disk "public" + peta pemakaiannya oleh konten satu site.
 * Dipakai endpoint /admin/media (list, ganti isi file, hapus).
 */
class MediaLibrary
{
    private const IMAGE_EXT = 'jpe?g|png|webp|gif|svg';

    private const SITE_DIRS = [
        'services', 'branches', 'business-units', 'hero', 'career',
        'partners', 'portfolios', 'sections', 'testimonials', 'articles', 'pages',
    ];

    /** "storage/foo/bar.jpg", "http://host/storage/foo/bar.jpg", "/foo/bar.jpg" -> "foo/bar.jpg" */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $path = preg_replace('#^https?://[^/]+/#i', '', trim($raw)) ?? '';
        $path = ltrim($path, '/');
        $path = preg_replace('#^storage/#', '', $path) ?? '';

        return $path === '' ? null : $path;
    }

    public static function isImage(string $path): bool
    {
        return (bool) preg_match('/\.(' . self::IMAGE_EXT . ')$/i', $path);
    }

    /** Tolak traversal dan karakter aneh sebelum menyentuh disk. */
    public static function isSafePath(string $path): bool
    {
        return $path !== ''
            && strlen($path) <= 255
            && ! str_contains($path, '..')
            && ! str_starts_with($path, '/')
            && (bool) preg_match('#^[A-Za-z0-9._\-/]+$#', $path);
    }

    /**
     * path => daftar pemakai [{type, id, label, field}]
     *
     * @return array<string, array<int, array{type: string, id: int, label: string|null, field: string}>>
     */
    public function usageMap(Site $site): array
    {
        $map = [];
        $seen = [];

        $add = function (?string $raw, string $type, int|string $id, ?string $label, string $field) use (&$map, &$seen) {
            $path = self::normalize($raw);
            if (! $path || ! self::isImage($path)) {
                return;
            }
            $key = "{$type}#{$id}#{$field}#{$path}";
            if (isset($seen[$key])) {
                return;
            }
            $seen[$key] = true;
            $map[$path][] = ['type' => $type, 'id' => $id, 'label' => $label, 'field' => $field];
        };

        foreach (Service::where('site_id', $site->id)->get() as $row) {
            $add($row->thumbnail_path, 'service', $row->id, $row->name, 'thumbnail_path');
            $add($row->icon_path, 'service', $row->id, $row->name, 'icon_path');
        }

        foreach (Portfolio::where('site_id', $site->id)->get() as $row) {
            $add($row->thumbnail_path, 'portfolio', $row->id, $row->title, 'thumbnail_path');
            foreach ((array) $row->gallery_images as $i => $path) {
                $add($path, 'portfolio', $row->id, $row->title, "gallery_images.{$i}");
            }
        }

        foreach (Branch::where('site_id', $site->id)->get() as $row) {
            $add($row->image_path, 'branch', $row->id, $row->name, 'image_path');
            foreach ((array) $row->images as $i => $path) {
                $add($path, 'branch', $row->id, $row->name, "images.{$i}");
            }
        }

        foreach (BusinessUnit::where('site_id', $site->id)->get() as $row) {
            $add($row->image_path, 'business_unit', $row->id, $row->name, 'image_path');
            foreach ((array) $row->images as $i => $path) {
                $add($path, 'business_unit', $row->id, $row->name, "images.{$i}");
            }
        }

        foreach (Article::where('site_id', $site->id)->get() as $row) {
            $add($row->thumbnail_path, 'article', $row->id, $row->title, 'thumbnail_path');
        }

        foreach (Partner::where('site_id', $site->id)->get() as $row) {
            $add($row->logo_path, 'partner', $row->id, $row->name, 'logo_path');
        }

        foreach (Testimonial::where('site_id', $site->id)->get() as $row) {
            $add($row->avatar_path, 'testimonial', $row->id, $row->name, 'avatar_path');
        }

        foreach (PageSection::whereHas('page', fn ($q) => $q->where('site_id', $site->id))->with('page')->get() as $row) {
            $label = trim(($row->page?->title ?? 'Halaman') . ' / ' . $row->key);
            $add($row->image_path, 'page_section', $row->id, $label, 'image_path');
            foreach (array_unique($this->payloadPaths($row->payload)) as $path) {
                $add($path, 'page_section', $row->id, $label, 'payload');
            }
        }

        foreach (SiteSetting::where('site_id', $site->id)->get() as $row) {
            if (is_string($row->value)) {
                $add($row->value, 'setting', $row->id, $row->group . '.' . $row->key, 'value');
            }
        }

        return $map;
    }

    /**
     * @return array<int, array{path: string, url: string, size: int, modified_at: string, owner_site: string|null, usage: array}>
     */
    public function files(Site $site): array
    {
        $usage = $this->usageMap($site);
        $disk = Storage::disk('public');

        $items = [];

        foreach ($disk->allFiles() as $path) {
            if (! self::isImage($path)) {
                continue;
            }

            $owner = $this->ownerSlug($path);
            $refs = $usage[$path] ?? [];

            // file milik tenant lain & tidak dipakai site ini -> sembunyikan
            if ($owner !== null && $owner !== $site->slug && $refs === []) {
                continue;
            }

            $mtime = $disk->lastModified($path);

            $items[] = [
                'path' => $path,
                'url' => url('storage/' . $path) . '?v=' . $mtime,
                'size' => $disk->size($path),
                'modified_at' => date(DATE_ATOM, $mtime),
                'owner_site' => $owner,
                'usage' => $refs,
            ];
        }

        usort($items, fn ($a, $b) => strcmp($b['modified_at'], $a['modified_at']));

        return $items;
    }

    public function isUsedBySite(string $path, Site $site): bool
    {
        return array_key_exists($path, $this->usageMap($site));
    }

    /** Pemilik file ditebak dari konvensi folder (<jenis>/<slug>/... atau branding/<slug>-...). */
    public function ownerSlug(string $path): ?string
    {
        $parts = explode('/', $path);
        $slugs = Site::pluck('slug')->all();

        if ($parts[0] === 'branding' && isset($parts[1])) {
            foreach ($slugs as $slug) {
                if (str_starts_with($parts[1], $slug)) {
                    return $slug;
                }
            }

            return null;
        }

        if (! in_array($parts[0], self::SITE_DIRS, true)) {
            return null;
        }

        $candidate = ($parts[0] === 'services' && ($parts[1] ?? '') === 'icons')
            ? ($parts[2] ?? null)
            : ($parts[1] ?? null);

        return in_array($candidate, $slugs, true) ? $candidate : null;
    }

    /**
     * @return array<int, string>
     */
    private function payloadPaths(mixed $payload): array
    {
        if (is_string($payload)) {
            $path = self::normalize($payload);

            return $path && self::isImage($path) ? [$path] : [];
        }

        if (! is_array($payload)) {
            return [];
        }

        $out = [];
        foreach ($payload as $value) {
            foreach ($this->payloadPaths($value) as $path) {
                $out[] = $path;
            }
        }

        return $out;
    }
}
