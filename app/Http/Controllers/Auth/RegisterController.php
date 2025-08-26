<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\ProvidersSubcategories;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    // Registro Cliente
    public function registerClient(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'string|max:20',
                'last_name' => 'string|max:20',
                'email' => 'string|email|max:50|unique:users',
                'password' => 'string|min:8|confirmed',
                'personal_phone_number' => 'string|max:15',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación en datos de usuario',
                'details' => $e->errors()
            ], 422);
        }

        $user = User::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
        ]);

        $role = Role::where('name', 'Client')->first();
        $user->roles()->attach($role);

        return response()->json(['message' => 'Bienvenido Usuario Cliente.']);
    }

    // Registro Proveedor
    public function registerProvider(Request $request)
    {
        try {
            // Validación de datos de usuario
            $validatedUser = $request->validate([
                'first_name' => 'required|string|max:20',
                'last_name' => 'required|string|max:20',
                'email' => 'required|string|email|max:50|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'personal_phone_number' => 'required|string|max:15',
            ]);

            // Validación de datos del proveedor
            $validatedProvider = $request->validate([
                'ced' => 'required|string|max:14|unique:providers,ced',
                'contact_email' => 'required|email|max:40',
                'phone_number' => 'required|string|max:14',
                'location' => 'required|string|max:255',
                'long' => 'required|string|max:40',
                'lat' => 'required|string|max:40',
                'experience_years' => 'required|integer|min:0|max:65535',
                'schedule_type' => 'required|boolean',
                'subcategory_id' => 'required|exists:subcategories,id',
                'father_category' => 'required|exists:categories,id',
                'img' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', // nueva validación
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación en datos de usuario',
                'details' => $e->errors()
            ], 422);
        }

        // Crear usuario
        $user = User::create([
            ...$validatedUser,
            'password' => Hash::make($validatedUser['password']),
        ]);

        $role = Role::where('name', 'Provider')->first();
        $user->roles()->attach($role);

        // Manejo de imagen
        if ($request->hasFile('img')) {
            $imgPath = $request->file('img')->move(public_path('img/provider'), uniqid() . '.' . $request->file('img')->getClientOriginalExtension());
            $imgRelativePath = 'img/provider/' . basename($imgPath); // Ruta accesible desde el navegador
        } else {
            $imgRelativePath = 'img/provider/user_default_icon.png'; // Imagen por defecto
        }

        // Crear proveedor
        $provider = Provider::create([
            ...$validatedProvider,
            'user_id' => $user->id,
            'img' => $imgRelativePath,
        ]);

        // Relación subcategoría
        ProvidersSubcategories::create([
            'provider_id' => $provider->id,
            'subcategory_id' => $validatedProvider['subcategory_id'],
            'father_category' => $validatedProvider['father_category'],
        ]);

        return response()->json(['message' => 'Bienvenido Usuario Proveedor de Servicios.']);
    }

    // Registro Admin (solo si el usuario autenticado es admin)
    public function registerAdmin(Request $request)
    {
        /** @var \App\Models\User|null $authUser */

        $authUser = Auth::user();
        if (!$authUser || !$authUser->hasRole('Admin')) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        DB::beginTransaction();

        try {
            // Validar datos de usuario
            $validatedUser = $request->validate([
                'first_name' => 'required|string|max:20',
                'last_name' => 'required|string|max:20',
                'email' => 'required|string|email|max:50|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'personal_phone_number' => 'required|string|max:15',
            ]);

            // Crear el usuario admin
            $user = User::create([
                ...$validatedUser,
                'password' => Hash::make($validatedUser['password']),
            ]);

            $role = Role::where('name', 'Admin')->first();
            $user->roles()->attach($role);

            // Si vienen datos de proveedor, validar y crear proveedor
            $providerFields = [
                'ced',
                'contact_email',
                'phone_number',
                'location',
                'long',
                'lat',
                'experience_years',
                'schedule_type'
            ];

            $hasProviderData = collect($providerFields)->some(fn($field) => $request->has($field));

            if ($hasProviderData) {
                $validatedProvider = $request->validate([
                    'ced' => 'required|string|max:14|unique:providers,ced',
                    'contact_email' => 'required|email|max:40',
                    'phone_number' => 'required|string|max:14',
                    'location' => 'required|string|max:255',
                    'long' => 'required|string|max:40',
                    'lat' => 'required|string|max:40',
                    'experience_years' => 'required|integer|min:0|max:65535',
                    'schedule_type' => 'required|boolean',
                    'subcategory_id' => 'required|exists:subcategories,id',
                    'father_category' => 'required|exists:categories,id',
                    'img' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                ]);

                // Manejo de imagen
                if ($request->hasFile('img')) {
                    $imgPath = $request->file('img')->move(public_path('img/provider'), uniqid() . '.' . $request->file('img')->getClientOriginalExtension());
                    $imgRelativePath = 'img/provider/' . basename($imgPath);
                } else {
                    $imgRelativePath = 'img/provider/user_default_icon.png';
                }

                $provider = Provider::create([
                    ...$validatedProvider,
                    'user_id' => $user->id,
                    'img' => $imgRelativePath,
                ]);

                ProvidersSubcategories::create([
                    'provider_id' => $provider->id,
                    'subcategory_id' => $validatedProvider['subcategory_id'],
                    'father_category' => $validatedProvider['father_category'],
                ]);

                $providerRole = Role::where('name', 'Provider')->first();
                $user->roles()->attach($providerRole);
            }

            DB::commit();

            return response()->json(['message' => 'Bienvenido usuario admin.']);

        } catch (ValidationException $e) {
            DB::rollBack();

            // Si se creó el usuario, eliminarlo
            if (isset($user)) {
                $user->delete(); // Soft delete o hard delete según tu modelo
            }

            return response()->json([
                'error' => 'Error de validación',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();

            // Eliminar usuario si ya fue creado
            if (isset($user)) {
                $user->delete();
            }

            Log::error('Error al registrar admin: ' . $e->getMessage());

            return response()->json([
                'error' => 'Error al registrar usuario admin',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
