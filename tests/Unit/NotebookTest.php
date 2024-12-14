<?php


use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);
uses(TestCase::class);

test('index возвращает коллекцию заметок', function () {
    Note::factory()->count(3)->create();
    $response = $this->get('/api/v1/notebook');
    $response->assertOk();
    $response->assertJsonCount(3, 'data');
});

test('index выполняет пагинацию заметок', function () {
    Note::factory()->count(15)->create();
    $response = $this->get('/api/v1/notebook?per_page=5&page=2');
    $response->assertOk();
    $response->assertJsonCount(5, 'data');
});

test('store создает новую заметку', function () {
    $response = $this->post('/api/v1/notebook', [
        'full_name' => 'Тестовый пользователь',
        'phone' => '+79991112233',
        'email' => 'test@example.com',
    ]);

    $response->assertCreated();
    $response->assertJsonStructure(['data' => ['id', 'full_name', 'phone', 'email', 'photo']]);
    $this->assertDatabaseHas('notes', ['full_name' => 'Тестовый пользователь', 'email' => 'test@example.com']);
});


test('store завершается ошибкой валидации', function () {
    $response = $this->postJson('/api/v1/notebook', [
        'full_name' => '',
        'phone' => '',
        'email' => '',
    ]);
    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['full_name', 'phone', 'email']);
});

test('show возвращает одну заметку', function () {
    $note = Note::factory()->create();
    $response = $this->get("/api/v1/notebook/{$note->id}");
    $response->assertOk();
    $response->assertJsonStructure(['data' => ['id', 'full_name', 'phone', 'email']]);
    $response->assertJsonPath('data.id', $note->id);
});

test('show возвращает 404, если заметка не найдена', function () {
    $response = $this->get('/api/v1/notebook/999');
    $response->assertNotFound();
});

test('update обновляет существующую заметку', function () {
    $note = Note::factory()->create();
    $response = $this->putJson("/api/v1/notebook/{$note->id}", [
        'full_name' => 'Обновленный пользователь',
        'phone' => '+79999999999',
        'email' => 'updated@example.com',
    ]);
    $response->assertOk();
    $response->assertJsonStructure(['data' => ['id', 'full_name', 'phone', 'email', 'photo']]);
    $response->assertJsonPath('data.full_name', 'Обновленный пользователь');
    $this->assertDatabaseHas('notes', ['id' => $note->id, 'full_name' => 'Обновленный пользователь', 'email' => 'updated@example.com']);
});

test('update завершается ошибкой валидации', function () {
    $note = Note::factory()->create();
    $response = $this->putJson("/api/v1/notebook/{$note->id}", [
        'email' => 'invalid-email',
    ]);
    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email']);
});

test('destroy удаляет заметку', function () {
    $note = Note::factory()->create();
    $response = $this->delete("/api/v1/notebook/{$note->id}");
    $response->assertNoContent();
    $this->assertDatabaseHas('notes', ['id' => $note->id]);
    $this->assertSoftDeleted('notes', ['id' => $note->id]);
});

test('destroy возвращает 404, если заметка не найдена', function () {
    $response = $this->delete('/api/v1/notebook/999');
    $response->assertNotFound();
});
