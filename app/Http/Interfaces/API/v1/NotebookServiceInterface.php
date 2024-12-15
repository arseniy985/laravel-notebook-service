<?php

namespace App\Http\Interfaces\API\v1;

use App\Http\Resources\v1\NotebookResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

interface NotebookServiceInterface
{
    public function getAllNotes(): AnonymousResourceCollection;
    public function getPaginatedNotes(int $per_page): AnonymousResourceCollection;
    public function getNote(int $id): NotebookResource;
    public function storeNote(array $data): NotebookResource;
    public function updateNote(array $data, int $id): NotebookResource;
    public function destroyNote(int $id) : void;
}
