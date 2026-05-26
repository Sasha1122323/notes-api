<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * GET /api/categories
     */
    public function index()
    {
        $categories = Category::withCount('notes')->get();
        
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * POST /api/categories
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        $slug = Str::slug($validated['name']);
        
        // Уникальность slug
        $originalSlug = $slug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => $slug
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Категория создана',
            'data' => $category
        ], 201);
    }

    /**
     * GET /api/categories/{id}
     */
    public function show($id)
    {
        $category = Category::with('notes')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * PUT /api/categories/{id}
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
        ]);

        if ($category->name !== $validated['name']) {
            $slug = Str::slug($validated['name']);
            $originalSlug = $slug;
            $counter = 1;
            while (Category::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }
            $category->slug = $slug;
        }

        $category->name = $validated['name'];
        $category->save();

        return response()->json([
            'success' => true,
            'message' => 'Категория обновлена',
            'data' => $category
        ]);
    }

    /**
     * DELETE /api/categories/{id}
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        
        $notesCount = $category->notes()->count();
        
        if ($notesCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Нельзя удалить категорию. У неё {$notesCount} заметок. Сначала переместите или удалите заметки."
            ], 400);
        }
        
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Категория удалена'
        ]);
    }
}