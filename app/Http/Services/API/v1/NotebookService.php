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
    public function getAllNotes(): AnonymousResourceCollection
    {
        return Cache::remember('notebook_all_notes', 3600, function () {
            return NotebookResource::collection(
                Note::all()
            );
        });
    }

    public function getPaginatedNotes(int $per_page): AnonymousResourceCollection
    {
        $cacheKey = "notebook_paginated_notes_{$per_page}";

        $this->addPaginatedKey($cacheKey);

        return Cache::remember($cacheKey, 3600, function () use ($per_page) {
            return NotebookResource::collection(
                Note::paginate($per_page)
            );
        });
    }

    public function getNote(int $id): NotebookResource
    {
        $cacheKey = "notebook_note_{$id}";

        return Cache::remember($cacheKey, 3600, function () use ($id) {
            return new NotebookResource(
                Note::findOrFail($id)
            );
        });
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
            $data['photo'] = $this->saveNotePhoto($data['photo']);
            if ($note->photo) {
                $this->deleteNotePhoto($note->photo);
            }
        }

        $note->update($data);

        $this->clearAllCache($id);

        return new NotebookResource(
            $note->fresh()
        );
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
        $filename = date('Y-m-d-H-i-s') . '-' .
            Uuid::uuid4()->toString() . '.' .
            $photo->getClientOriginalExtension();
        if (!$photo->move(storage_path('app/public/notebook-photos'), $filename)) {
            return false;
        }

        return 'notebook-photos/' . $filename;
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
        // Очистить все ключи пагинации
        $keys = Cache::get('notebook_paginated_keys', []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        Cache::forget('notebook_paginated_keys');
    }

    private function addPaginatedKey(string $cacheKey): void
    {
        $keys = Cache::get('notebook_paginated_keys', []);
        if (!in_array($cacheKey, $keys)) {
            $keys[] = $cacheKey;
            Cache::put('notebook_paginated_keys', $keys, 3600);
        }
    }

    private function clearAllCache(?int $id = null): void
    {
        if ($id !== null) {
            Cache::forget("notebook_note_{$id}");
        }
        Cache::forget('notebook_all_notes');
        $this->clearPaginatedCache();
    }
}
