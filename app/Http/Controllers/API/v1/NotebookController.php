<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\v1\StoreNoteRequest;
use App\Http\Requests\API\v1\UpdateNoteRequest;
use App\Http\Resources\v1\NotebookResource;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Nonstandard\Uuid;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Laravel Notes API Documentation",
 *     description="API documentation for Notes Service"
 * )
 */
class NotebookController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/notebook",
     *     summary="Получить список заметок",
     *     description="Возвращает список заметок с пагинацией. Если параметр page не указан, возвращает все заметки.",
     *     tags={"Заметки"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Номер страницы (необязательно). Если не указан, возвращает все заметки без пагинации",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Количество элементов на странице (необязательно, по умолчанию: 15)",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешная операция",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Note")
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="first", type="string", example="http://localhost:8000/api/v1/notebook?page=1", description="Ссылка на первую страницу"),
     *                 @OA\Property(property="last", type="string", example="http://localhost:8000/api/v1/notebook?page=3", description="Ссылка на последнюю страницу"),
     *                 @OA\Property(property="prev", type="string", nullable=true, description="Ссылка на предыдущую страницу"),
     *                 @OA\Property(property="next", type="string", example="http://localhost:8000/api/v1/notebook?page=2", description="Ссылка на следующую страницу")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1, description="Текущая страница"),
     *                 @OA\Property(property="from", type="integer", example=1, description="Номер первой записи на странице"),
     *                 @OA\Property(property="last_page", type="integer", example=3, description="Номер последней страницы"),
     *                 @OA\Property(property="path", type="string", example="http://localhost:8000/api/v1/notebook", description="Базовый URL"),
     *                 @OA\Property(property="per_page", type="integer", example=10, description="Количество записей на странице"),
     *                 @OA\Property(property="to", type="integer", example=10, description="Номер последней записи на странице"),
     *                 @OA\Property(property="total", type="integer", example=28, description="Общее количество записей")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Некорректный запрос"
     *     )
     * )
     */
    public function index(Request $req): AnonymousResourceCollection
    {
        return NotebookResource::collection(
            $req->query('page') ?
                Note::paginate($req->query('per_page')) :
                Note::all()
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/notebook",
     *     summary="Создать новую заметку",
     *     tags={"Заметки"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"full_name", "company", "phone", "email", "birth_date"},
     *                 @OA\Property(property="full_name", type="string", maxLength=255, description="ФИО"),
     *                 @OA\Property(property="company", type="string", maxLength=255, description="Название компании"),
     *                 @OA\Property(property="phone", type="string", maxLength=255, description="Номер телефона"),
     *                 @OA\Property(property="email", type="string", format="email", maxLength=255, description="Email адрес"),
     *                 @OA\Property(property="birth_date", type="string", format="date", description="Дата рождения"),
     *                 @OA\Property(property="photo", type="file", format="binary", description="Фотография (опционально)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Заметка успешно создана",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Note")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации"
     *     )
     * )
     */
    public function store(StoreNoteRequest $req): NotebookResource
    {
        $data = $req->validated();
        if ($req->hasFile('photo')) {
            $photo = $req->file('photo');
            $data['photo'] = $this->saveNotePhoto($photo);
        }

        return new NotebookResource(
            Note::create($data)
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/notebook/{id}",
     *     summary="Получить заметку по ID",
     *     tags={"Заметки"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID заметки",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешная операция",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Note")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Заметка не найдена"
     *     )
     * )
     */
    public function show(Request $req): NotebookResource
    {
        return new NotebookResource(
            Note::findOrFail($req->route('id'))
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v1/notebook/{id}",
     *     summary="Обновить заметку",
     *     tags={"Заметки"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID заметки",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="full_name", type="string", maxLength=255, description="ФИО"),
     *                 @OA\Property(property="company", type="string", maxLength=255, description="Название компании"),
     *                 @OA\Property(property="phone", type="string", maxLength=255, description="Номер телефона"),
     *                 @OA\Property(property="email", type="string", format="email", maxLength=255, description="Email адрес"),
     *                 @OA\Property(property="birth_date", type="string", format="date", description="Дата рождения"),
     *                 @OA\Property(property="photo", type="file", format="binary", description="Фотография (опционально)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Заметка успешно обновлена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Note")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Заметка не найдена"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации"
     *     )
     * )
     */
    public function update(UpdateNoteRequest $req): NotebookResource
    {
        $note = Note::findOrFail($req->route('id'));
        $data = $req->validated();

        if ($req->hasFile('photo')) {
            $photo = $req->file('photo');
            $data['photo'] = $this->saveNotePhoto($photo);
            if ($note->photo) {
                $this->deleteNotePhoto($note->photo);
            }
        }

        $note->update($data);

        return new NotebookResource(
            $note->fresh()
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/notebook/{id}",
     *     summary="Удалить заметку",
     *     tags={"Заметки"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID заметки",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Заметка успешно удалена"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Заметка не найдена"
     *     )
     * )
     */
    public function destroy(Request $req): Response
    {
        $note = Note::findOrFail($req->route('id'));
        if ($note->photo) {
            $this->deleteNotePhoto($note->photo);
        }

        $note->delete();

        return response()->noContent();
    }

    /**
     * Save uploaded notebook photo
     *
     * @param UploadedFile $photo Uploaded photo file
     * @return false|string Path to saved photo or false on failure
     */
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

    /**
     * Delete notebook photo
     * 
     * @param string $photoPath Path to photo file
     */
    private function deleteNotePhoto(string $photoPath): void
    {
        $fullPath = storage_path('app/public/' . $photoPath);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        
    }
}