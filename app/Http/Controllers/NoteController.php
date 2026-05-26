<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /**
     * GET /api/notes
     * Получить все заметки текущего пользователя
     * Поддерживает фильтрацию по category_id, status, поиск по title
     */
    public function index(Request $request)
    {
        $query = Note::with(['category', 'user', 'tags'])
            ->where('user_id', auth()->id());

        // Фильтр по категории
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Фильтр по статусу (published / draft)
        if ($request->has('status')) {
            $query->where('is_published', $request->status === 'published');
        }

        // Поиск по заголовку
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $notes = $query->get();

        return response()->json($notes);
    }

    /**
     * POST /api/notes
     * Создать новую заметку (user_id берётся из авторизации)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'nullable|exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id'
        ]);

        $note = Note::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'category_id' => $validated['category_id'] ?? null,
            'user_id' => auth()->id()
        ]);

        if ($request->has('tags')) {
            $note->tags()->sync($request->tags);
        }

        return response()->json($note->load('tags'), 201);
    }

    /**
     * GET /api/notes/{id}
     * Показать одну заметку (только если она принадлежит текущему пользователю)
     */
    public function show($id)
    {
        $note = Note::with(['category', 'user', 'tags'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json($note);
    }

    /**
     * PUT /api/notes/{id}
     * Обновить заметку (только если она принадлежит текущему пользователю)
     */
    public function update(Request $request, $id)
    {
        $note = Note::where('user_id', auth()->id())->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'category_id' => 'nullable|exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id'
        ]);

        $note->update($validated);

        if ($request->has('tags')) {
            $note->tags()->sync($request->tags);
        }

        return response()->json($note->load('tags'));
    }

    /**
     * DELETE /api/notes/{id}
     * Удалить заметку (только если она принадлежит текущему пользователю)
     */
    public function destroy($id)
    {
        $note = Note::where('user_id', auth()->id())->findOrFail($id);
        $note->delete();

        return response()->json(null, 204);
    }
}