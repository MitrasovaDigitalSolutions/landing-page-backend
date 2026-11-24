<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Support\MediaLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminMediaController extends Controller
{
    public function __construct(private readonly MediaLibrary $library)
    {
    }

    /**
     * Daftar gambar milik site aktif + info dipakai di mana.
     */
    public function index(Request $request): JsonResponse
    {
        $site = app('current_site');
        $items = $this->library->files($site);

        if ($request->boolean('unused')) {
            $items = array_values(array_filter($items, fn ($item) => $item['usage'] === []));
        }

        if ($request->filled('q')) {
            $needle = strtolower((string) $request->query('q'));
            $items = array_values(array_filter(
                $items,
                fn ($item) => str_contains(strtolower($item['path']), $needle)
            ));
        }

        if ($request->filled('dir')) {
            $dir = trim((string) $request->query('dir'), '/') . '/';
            $items = array_values(array_filter(
                $items,
                fn ($item) => str_starts_with($item['path'], $dir)
            ));
        }

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'total' => count($items),
                'unused' => count(array_filter($items, fn ($item) => $item['usage'] === [])),
            ],
        ]);
    }

    /**
     * Ganti isi file di path yang sama supaya semua referensi konten ikut berubah.
     */
    public function replace(Request $request): JsonResponse
    {
        $site = app('current_site');

        $validated = $request->validate([
            'path' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:10240'],
        ]);

        $path = MediaLibrary::normalize($validated['path']);
        if (! $path || ! MediaLibrary::isSafePath($path)) {
            return $this->error('Path tidak valid.', 422);
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return $this->error('File tidak ditemukan.', 404);
        }

        if (! $this->canManage($path, $site)) {
            return $this->error('File bukan milik site ini.', 403);
        }

        // ekstensi harus sama: ganti isi file, bukan rename, agar referensi DB tetap valid
        $oldExt = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $newExt = strtolower($request->file('file')->getClientOriginalExtension());
        if ($oldExt === 'jpeg') {
            $oldExt = 'jpg';
        }
        if ($newExt === 'jpeg') {
            $newExt = 'jpg';
        }
        if ($oldExt !== $newExt) {
            return $this->error("Ekstensi file harus sama dengan file lama (.{$oldExt}).", 422);
        }

        $dir = dirname($path);
        $disk->putFileAs($dir === '.' ? '' : $dir, $request->file('file'), basename($path));

        $mtime = $disk->lastModified($path);

        return response()->json([
            'success' => true,
            'message' => 'Gambar berhasil diganti.',
            'data' => [
                'path' => $path,
                'url' => url('storage/' . $path) . '?v=' . $mtime,
                'size' => $disk->size($path),
                'modified_at' => date(DATE_ATOM, $mtime),
            ],
        ]);
    }

    /**
     * Hapus file. Ditolak (409) kalau masih dipakai konten, kecuali force=1.
     */
    public function destroy(Request $request): JsonResponse
    {
        $site = app('current_site');

        $validated = $request->validate([
            'path' => ['required', 'string', 'max:255'],
            'force' => ['sometimes', 'boolean'],
        ]);

        $path = MediaLibrary::normalize($validated['path']);
        if (! $path || ! MediaLibrary::isSafePath($path)) {
            return $this->error('Path tidak valid.', 422);
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return $this->error('File tidak ditemukan.', 404);
        }

        if (! $this->canManage($path, $site)) {
            return $this->error('File bukan milik site ini.', 403);
        }

        $usage = $this->library->usageMap($site)[$path] ?? [];

        if ($usage !== [] && ! $request->boolean('force')) {
            return response()->json([
                'success' => false,
                'message' => 'Gambar masih dipakai konten. Kirim force=1 untuk tetap menghapus (referensi akan menjadi gambar rusak).',
                'data' => ['usage' => $usage],
            ], 409);
        }

        $disk->delete($path);

        return response()->json([
            'success' => true,
            'message' => 'Gambar dihapus.',
            'data' => ['path' => $path, 'usage' => $usage],
        ]);
    }

    private function canManage(string $path, $site): bool
    {
        return $this->library->ownerSlug($path) === $site->slug
            || $this->library->isUsedBySite($path, $site);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
