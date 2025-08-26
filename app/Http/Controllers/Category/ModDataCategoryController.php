<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ModDataCategoryController extends Controller
{
    /**
     * Verifica si el usuario autenticado es admin
     */
    private function authorizeAdmin()
    {
        /** @var \App\Models\User|null $user */

        $user = Auth::user();
        if (!$user || !$user->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
    }

    /**
     * Almacenar una nueva categoría
     */
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Categoría creada correctamente.',
            'category' => $category,
        ], 201);
    }

    /**
     * Actualizar el nombre o imagen de una categoría
     */
    public function update(Request $request, $id)
    {
        $this->authorizeAdmin();

        $category = Category::find($id);
        if (!$category) {
            return response()->json(['error' => 'Categoría no encontrada.'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Categoría actualizada correctamente.',
            'category' => $category,
        ]);
    }

    /**
     * Eliminar una categoría y su imagen
     */
    public function destroy($id)
    {
        $this->authorizeAdmin();

        $category = Category::find($id);

        if (!$category) {
            return response()->json(['error' => 'Categoría no encontrada.'], 404);
        }

        $category->delete();

        return response()->json([
            'message' => 'Categoría y sus subcategorías eliminadas correctamente.',
        ]);
    }
}
