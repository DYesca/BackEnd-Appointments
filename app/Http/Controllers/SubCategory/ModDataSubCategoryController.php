<?php

namespace App\Http\Controllers\SubCategory;

use App\Http\Controllers\Controller;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ModDataSubCategoryController extends Controller
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

    // Crear subcategoría
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'img' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('img')) {
            $path = $request->file('img')->store('img/subcategory', 'public');
            $validated['img'] = $path;
        } else {
            // Ruta relativa a la imagen por defecto ubicada en public/subcategory/default_subcategory.png
            $validated['img'] = 'subcategory/default_subcategory.png';
        }

        $subcategory = Subcategory::create($validated);

        return response()->json(['message' => 'Subcategoría creada correctamente.', 'data' => $subcategory], 201);
    }

    // Modificar subcategoría
    public function update(Request $request, $id)
    {
        $this->authorizeAdmin();

        $subcategory = Subcategory::find($id);
        if (!$subcategory) {
            return response()->json(['error' => 'Subcategoría no encontrada.'], 404);
        }

        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'img' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Si hay nueva imagen, eliminar la anterior y guardar la nueva
        if ($request->hasFile('img')) {
            if ($subcategory->img) {
                Storage::disk('public')->delete($subcategory->img);
            }

            $path = $request->file('img')->store('img/subcategory', 'public');
            $validated['img'] = $path;
        }

        $subcategory->update($validated);

        return response()->json(['message' => 'Subcategoría actualizada correctamente.', 'data' => $subcategory]);
    }

    // Eliminar subcategoría
    public function destroy($id)
    {
        $this->authorizeAdmin();

        $subcategory = Subcategory::find($id);
        if (!$subcategory) {
            return response()->json(['error' => 'Subcategoría no encontrada.'], 404);
        }

        // Eliminar imagen asociada si no es la por defecto
        if ($subcategory->img && $subcategory->img !== 'img/subcategory/default_subcategory.png') {
            Storage::disk('public')->delete($subcategory->img);
        }

        $subcategory->delete();

        return response()->json(['message' => 'Subcategoría eliminada correctamente.']);
    }
}
