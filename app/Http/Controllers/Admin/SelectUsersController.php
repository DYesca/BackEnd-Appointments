<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SelectUsersController extends Controller
{
    // Función pública para listar por tipo de usuario
    public function listByType(Request $request)
    {
        /** @var \App\Models\User|null $admin */

        $admin = Auth::user();

        if (!$admin || !$admin->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $type = $request->query('type');

        return match (strtolower($type)) {
            'admin' => $this->getAdmins(),
            'provider' => $this->getProviders(),
            'client' => $this->getClients(),
            default => response()->json(['error' => 'Tipo de usuario inválido'], 422),
        };
    }

    // Función pública para buscar por tipo de usuario y nombre
    public function searchByType(Request $request)
    {
        /** @var \App\Models\User|null $admin */

        $admin = Auth::user();

        if (!$admin || !$admin->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $type = $request->query('type');
        $name = $request->query('name');

        if (!$name) {
            return response()->json(['error' => 'Debe proporcionar un parámetro de búsqueda'], 400);
        }

        return match (strtolower($type)) {
            'admin' => $this->searchAdminsByName($name),
            'provider' => $this->searchProvidersByName($name),
            'client' => $this->searchClientsByName($name),
            default => response()->json(['error' => 'Tipo de usuario inválido'], 422),
        };
    }

    public function findByTypeAndId(Request $request)
    {
        /** @var \App\Models\User|null $admin */

        $admin = Auth::user();

        if (!$admin || !$admin->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $type = $request->query('type');
        $id = $request->query('id');

        if (!$id || !is_numeric($id)) {
            return response()->json(['error' => 'Debe proporcionar un ID válido'], 400);
        }

        return match (strtolower($type)) {
            'admin' => $this->getAdminById($id),
            'provider' => $this->getProviderById($id),
            'client' => $this->getClientById($id),
            default => response()->json(['error' => 'Tipo de usuario inválido'], 422),
        };
    }

    // Función 1: Listar Admins
    private function providerWithCategoryAndSubcategory(): array
    {
        return [
            'roles',
            'provider',
            'provider.subcategoryRelation.category:id,name',              // ajusta columnas si quieres
            'provider.subcategoryRelation.subcategory:id,name,category_id'
        ];
    }

    // 1) Listar Admins (sin cambios)
    private function getAdmins()
    {
        $users = User::whereHas('roles', fn($q) => $q->where('name', 'Admin'))
            ->with('roles')
            ->get();

        return response()->json($users);
    }

    // 2) Listar Providers (con categoría y subcategoría)
    private function getProviders()
    {
        $users = User::whereHas('roles', fn($q) => $q->where('name', 'Provider'))
            ->with($this->providerWithCategoryAndSubcategory())
            ->get();

        return response()->json($users);
    }

    // 3) Listar Clients (sin cambios)
    private function getClients()
    {
        $users = User::whereHas('roles', fn($q) => $q->where('name', 'Client'))
            ->with('roles')
            ->get();

        return response()->json($users);
    }

    // 4) Buscar Admins por nombre (sin cambios)
    private function searchAdminsByName(string $name)
    {
        $users = User::whereHas('roles', fn($q) => $q->where('name', 'Admin'))
            ->where(function ($q) use ($name) {
                $q->where('first_name', 'like', "%$name%")
                    ->orWhere('last_name', 'like', "%$name%");
            })
            ->with('roles')
            ->get();

        return response()->json($users);
    }

    // 5) Buscar Providers por nombre (con categoría y subcategoría)
    private function searchProvidersByName(string $name)
    {
        $users = User::whereHas('roles', fn($q) => $q->where('name', 'Provider'))
            ->where(function ($q) use ($name) {
                $q->where('first_name', 'like', "%$name%")
                    ->orWhere('last_name', 'like', "%$name%");
            })
            ->with($this->providerWithCategoryAndSubcategory())
            ->get();

        return response()->json($users);
    }

    // 6) Buscar Clients por nombre (sin cambios)
    private function searchClientsByName(string $name)
    {
        $users = User::whereHas('roles', fn($q) => $q->where('name', 'Client'))
            ->where(function ($q) use ($name) {
                $q->where('first_name', 'like', "%$name%")
                    ->orWhere('last_name', 'like', "%$name%");
            })
            ->with('roles')
            ->get();

        return response()->json($users);
    }

    // 7) Obtener Admin por id (sin cambios)
    private function getAdminById($id)
    {
        $user = User::where('id', $id)
            ->whereHas('roles', fn($q) => $q->where('name', 'Admin'))
            ->with('roles')
            ->first();

        if (!$user) {
            return response()->json(['error' => 'Admin no encontrado'], 404);
        }

        return response()->json($user);
    }

    // 8) Obtener Provider por id (con categoría y subcategoría)
    private function getProviderById($id)
    {
        $user = User::where('id', $id)
            ->whereHas('roles', fn($q) => $q->where('name', 'Provider'))
            ->with($this->providerWithCategoryAndSubcategory())
            ->first();

        if (!$user) {
            return response()->json(['error' => 'Proveedor no encontrado'], 404);
        }

        return response()->json($user);
    }

    // 9) Obtener Client por id (sin cambios)
    private function getClientById($id)
    {
        $user = User::where('id', $id)
            ->whereHas('roles', fn($q) => $q->where('name', 'Client'))
            ->with('roles')
            ->first();

        if (!$user) {
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }

        return response()->json($user);
    }
}
