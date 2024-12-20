<?php

namespace App\Http\Services\API\v1;

use App\Http\Interfaces\API\v1\NotebookServiceInterface;
use App\Http\Resources\v1\NotebookResource;
use App\Models\Note;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Ramsey\Uuid\Nonstandard\Uuid;

class NotebookService implements NotebookServiceInterface
{
    private const CACHE_PREFIX = 'notebook';
    private const CACHE_TTL = 3600;
    private const CACHE_KEYS_KEY = 'notebook_paginated_keys';

    public function getAllNotes(): AnonymousResourceCollection
    {
        return Cache::remember(
            self::CACHE_PREFIX . '_all_notes',
            self::CACHE_TTL,
            fn() => NotebookResource::collection(Note::all())
        );
    }

    public function getPaginatedNotes(int $page, int $per_page): AnonymousResourceCollection
    {
        $cacheKey = sprintf(
            '%s_paginated_notes_per_%d_page_%d',
            self::CACHE_PREFIX,
            $per_page,
            $page
        );

        $this->addPaginatedKey($cacheKey);

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            fn() => NotebookResource::collection(Note::paginate($per_page))
        );
    }

    public function getNote(int $id): NotebookResource
    {
        $cacheKey = self::CACHE_PREFIX . "_note_{$id}";

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            fn() => new NotebookResource(Note::findOrFail($id))
        );
    }

    public function storeNote(array $data): NotebookResource
    {
        if (isset($data['photo'])) {
            $data['photo'] = $this->saveNotePhoto($data['photo']);
        }

        $note = Note::create($data);
        $this->clearAllCache();

        return new NotebookResource($note);
    }

    public function updateNote(array $data, int $id): NotebookResource
    {
        $note = Note::findOrFail($id);

        if (isset($data['photo'])) {
            $photoPath = $this->saveNotePhoto($data['photo']);
            if ($photoPath) {
                if ($note->photo) {
                    $this->deleteNotePhoto($note->photo);
                }
                $data['photo'] = $photoPath;
            }
        }

        $note->update($data);
        $this->clearAllCache($id);

        return new NotebookResource($note->fresh());
    }

    public function destroyNote(int $id): void
    {
        $note = Note::findOrFail($id);

        if ($note->photo) {
            $this->deleteNotePhoto($note->photo);
        }

        $note->delete();
        $this->clearAllCache($id);
    }

    private function saveNotePhoto(UploadedFile $photo): false|string
    {
        $filename = sprintf(
            '%s-%s.%s',
            date('Y-m-d-H-i-s'),
            Uuid::uuid4()->toString(),
            $photo->getClientOriginalExtension()
        );

        $path = 'notebook-photos/' . $filename;

        if (!$photo->move(storage_path('app/public/notebook-photos'), $filename)) {
            return false;
        }

        return $path;
    }

    private function deleteNotePhoto(string $photoPath): void
    {
        $fullPath = storage_path('app/public/' . $photoPath);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    private function clearPaginatedCache(): void
    {
        $keys = Cache::get(self::CACHE_KEYS_KEY, []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget(self::CACHE_KEYS_KEY);
    }


    private function addPaginatedKey(string $cacheKey): void
    {
        $keys = Cache::get(self::CACHE_KEYS_KEY, []);
        if (!in_array($cacheKey, $keys)) {
            $keys[] = $cacheKey;
            Cache::put(self::CACHE_KEYS_KEY, $keys, self::CACHE_TTL);
        }
    }

    private function clearAllCache(?int $id = null): void
    {
        if ($id !== null) {
            Cache::forget(self::CACHE_PREFIX . "_note_{$id}");
        }

        Cache::forget(self::CACHE_PREFIX . '_all_notes');
        $this->clearPaginatedCache();
    }
}
