<?php

namespace App\Http\Services\API\v1;

use App\Http\Interfaces\API\v1\NotebookServiceInterface;
use App\Http\Resources\v1\NotebookResource;
use App\Models\Note;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\Nonstandard\Uuid;

class NotebookService implements NotebookServiceInterface
{
    public function getAllNotes(): AnonymousResourceCollection
    {
        return NotebookResource::collection(
            Note::all()
        );
    }

    public function getPaginatedNotes(int $per_page): AnonymousResourceCollection
    {
        return NotebookResource::collection(
            Note::paginate($per_page, 15)
        );

    }

    public function getNote(int $id): NotebookResource
    {
        return new NotebookResource(
            Note::findOrFail($id)
        );
    }

    public function storeNote(array $data): NotebookResource
    {
        if (isset($data['photo'])) {
            $data['photo'] = $this->saveNotePhoto($data['photo']);
        }

        return new NotebookResource(
            Note::create($data)
        );
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
}
