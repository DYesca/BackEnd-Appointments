<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SelectProviderInfoController extends Controller
{
    // SELECT 1: Obtener proveedor por ID (requiere autenticación)
    public function getAuthenticatedProviderById(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $provider = Provider::with(['user.roles', 'subcategoryRelation.subcategory', 'subcategoryRelation.category'])
            ->where('id', $id)
            ->first();

        if (!$provider) {
            return response()->json(['error' => 'Proveedor no encontrado'], 404);
        }

        return $this->formatProviderResponse($provider);
    }

    // SELECT 2: Buscar por categoría/subcategoría y años experiencia (ruta pública)
    public function filterProvidersByCategory($category, $subcategory = null)
    {
        $validator = Validator::make(['category' => $category, 'subcategory' => $subcategory], [
            'category' => 'required|integer|exists:categories,id',
            'subcategory' => 'required|nullable|integer|exists:subcategories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = Provider::with(['user.roles', 'subcategoryRelation.subcategory', 'subcategoryRelation.category'])
            ->join('providers_subcategories', 'providers.id', '=', 'providers_subcategories.provider_id')
            ->where('providers_subcategories.father_category', $category);

        if ($subcategory) {
            $query->where('providers_subcategories.subcategory_id', $subcategory);
        }
        $providers = $query->select('providers.*')->get();

        return $this->formatProviderCollection($providers);
    }

    // SELECT 3: Búsqueda por distancia + filtros opcionales (POST)
    public function searchProvidersByLocation(Request $request)
    {
        $request->validate([
            'long' => 'required|numeric',
            'lat' => 'required|numeric',
            'range_km' => 'required|numeric|min:1',
            'subcategories_id' => 'nullable|array',
            'subcategories_id.*' => 'integer|exists:subcategories,id',
            'experience_years' => 'nullable|integer|min:0',
        ]);

        $radius = $request->range_km;

        $haversine = "(6371 * acos(cos(radians(?)) 
                * cos(radians(lat)) 
                * cos(radians(`long`) - radians(?)) 
                + sin(radians(?)) 
                * sin(radians(lat))))";

        // Query base con filtro de distancia
        $query = Provider::with(['user.roles', 'subcategory', 'subcategory.category'])
            ->select('providers.*')
            ->whereRaw("$haversine < ?", [
                $request->lat,
                $request->long,
                $request->lat,
                $radius,
            ]);

        // Si hay subcategorías específicas, filtrar por ellas
        if ($request->filled('subcategories_id') && !empty($request->subcategories_id)) {
            $query->where(function ($q) use ($request) {
                // Buscar en la subcategoría principal (relación directa)
                $q->whereIn('providers.subcategory_id', $request->subcategories_id)
                  // O buscar en las subcategorías adicionales (tabla pivot)
                  ->orWhereHas('subcategoryRelation', function ($subQuery) use ($request) {
                      $subQuery->whereIn('subcategory_id', $request->subcategories_id);
                  });
            });
        }

        // Filtro por años de experiencia
        if ($request->filled('experience_years')) {
            $query->where('providers.experience_years', '>=', $request->experience_years);
        }

        $providers = $query->get();

        return $this->formatProviderCollection($providers);
    }

    private function formatProviderResponse($provider)
    {
        // Usar la relación directa de subcategoría primero, luego la de la tabla pivot
        $subcategory = $provider->subcategory ?? optional($provider->subcategoryRelation)->subcategory;
        $category = $subcategory ? $subcategory->category : optional($provider->subcategoryRelation)->category;
        
        return response()->json([
            'user' => [
                'first_name' => $provider->user->first_name,
                'last_name' => $provider->user->last_name,
                'email' => $provider->user->email,
            ],
            'provider' => [
                'user_id' => $provider->user_id,
                'ced' => $provider->ced,
                'contact_email' => $provider->contact_email,
                'phone_number' => $provider->phone_number,
                'location' => $provider->location,
                'long' => $provider->long,
                'lat' => $provider->lat,
                'experience_years' => $provider->experience_years,
                'schedule_type' => $provider->schedule_type,
                'likes' => $provider->likes,
            ],
            'subcategory' => $subcategory ? [
                'id' => $subcategory->id,
                'category_id' => $subcategory->category_id,
                'name' => $subcategory->name,
            ] : null,
            'category' => $category ? [
                'id' => $category->id,
                'name' => $category->name,
            ] : null,
            'role' => $provider->user->roles->pluck('name'),
        ]);
    }

    private function formatProviderCollection($providers)
    {
        return response()->json($providers->map(function ($provider) {
            // Usar la relación directa de subcategoría primero, luego la de la tabla pivot
            $subcategory = $provider->subcategory ?? optional($provider->subcategoryRelation)->subcategory;
            $category = $subcategory ? $subcategory->category : optional($provider->subcategoryRelation)->category;
            
            return [
                'user' => [
                    'first_name' => $provider->user->first_name,
                    'last_name' => $provider->user->last_name,
                    'email' => $provider->user->email,
                ],
                'provider' => [
                    'user_id' => $provider->user_id,
                    'ced' => $provider->ced,
                    'contact_email' => $provider->contact_email,
                    'phone_number' => $provider->phone_number,
                    'location' => $provider->location,
                    'long' => $provider->long,
                    'lat' => $provider->lat,
                    'experience_years' => $provider->experience_years,
                    'schedule_type' => $provider->schedule_type,
                    'likes' => $provider->likes,
                ],
                'subcategory' => $subcategory ? [
                    'id' => $subcategory->id,
                    'category_id' => $subcategory->category_id,
                    'name' => $subcategory->name,
                ] : null,
                'category' => $category ? [
                    'id' => $category->id,
                    'name' => $category->name,
                ] : null,
                'role' => $provider->user->roles->pluck('name'),
            ];
        }));
    }
}
