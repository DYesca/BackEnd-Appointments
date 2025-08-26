<?php

namespace App\Http\Controllers\SubCategory;

use App\Http\Controllers\Controller;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SelectSubCategoryController extends Controller
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

    // Buscar subcategoría por ID
    public function getById($id)
    {
        $this->authorizeAdmin();

        $subcategory = Subcategory::with('category')->find($id);

        if (!$subcategory) {
            return response()->json(['error' => 'Subcategoría no encontrada.'], 404);
        }

        return response()->json($subcategory);
    }

    // Buscar subcategoría por nombre (like)
    public function searchByName(Request $request)
    {
        $this->authorizeAdmin();

        $name = $request->query('name');

        if (!$name) {
            return response()->json(['error' => 'Debe proporcionar un nombre para buscar.'], 400);
        }

        $subcategories = Subcategory::with('category')
            ->where('name', 'LIKE', "%{$name}%")
            ->get();

        return response()->json($subcategories);
    }
}
