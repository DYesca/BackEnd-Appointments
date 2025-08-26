<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SelectCategoryController extends Controller
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

    public function findById($id)
    {
        $this->authorizeAdmin();

        $category = Category::with('subcategories')->find($id);

        if (!$category) {
            return response()->json(['error' => 'Categoría no encontrada'], 404);
        }

        return response()->json($category);
    }

    public function findByName(Request $request)
    {
        $this->authorizeAdmin();

        $name = $request->query('name');

        if (!$name) {
            return response()->json(['error' => 'Debe proporcionar el nombre a buscar'], 400);
        }

        $categories = Category::with('subcategories')
            ->where('name', 'LIKE', "%{$name}%")
            ->get();

        return response()->json($categories);
    }

    public function all()
    {
        $categories = Category::with('subcategories')->get();

        return response()->json($categories);
    }
}
