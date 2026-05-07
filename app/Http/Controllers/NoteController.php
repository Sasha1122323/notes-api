<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function index(Request $request)
    {
        $query = Note::with(['category', 'user', 'tags']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('status')) {
            $query->where('is_published', $request->status === 'published');
        }

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $notes = $query->get();

        return response()->json($notes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'nullable|exists:categories,id',
            'user_id' => 'required|exists:users,id'
        ]);

        $note = Note::create($validated);

        if ($request->has('tags')) {
            $note->tags()->sync($request->tags);
        }

        return response()->json($note->load('tags'), 201);
    }

    public function show($id)
    {
        $note = Note::with(['category', 'user', 'tags'])->findOrFail($id);
        return response()->json($note);
    }

    public function update(Request $request, $id)
    {
        $note = Note::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'category_id' => 'nullable|exists:categories,id'
        ]);

        $note->update($validated);

        if ($request->has('tags')) {
            $note->tags()->sync($request->tags);
        }

        return response()->json($note->load('tags'));
    }

    public function destroy($id)
    {
        $note = Note::findOrFail($id);
        $note->delete();
        return response()->json(null, 204);
    }
}