<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Obtener el perfil del usuario autenticado
     */
    public function show()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        // Cargar relaciones necesarias
        $user->load(['roles', 'provider']);

        // Estructura base del perfil
        $profile = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'personal_phone_number' => $user->personal_phone_number,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'roles' => $user->roles->pluck('name'),
        ];

        // Si es proveedor, incluir información adicional
        if ($user->hasRole('Provider') && $user->provider) {
            $profile['provider'] = [
                'id' => $user->provider->id,
                'ced' => $user->provider->ced,
                'contact_email' => $user->provider->contact_email,
                'phone_number' => $user->provider->phone_number,
                'location' => $user->provider->location,
                'latitude' => $user->provider->lat,
                'longitude' => $user->provider->long,
                'experience_years' => $user->provider->experience_years,
                'schedule_type' => $user->provider->schedule_type,
                'likes' => $user->provider->likes,
                'img' => $user->provider->img,
                'services' => $user->provider->services,
                'created_at' => $user->provider->created_at,
                'updated_at' => $user->provider->updated_at,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Perfil obtenido exitosamente',
            'data' => $profile
        ], 200);
    }

    /**
     * Actualizar el perfil del usuario autenticado
     */
    public function update(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        try {
            // Validaciones solo para los campos permitidos
            $validated = $request->validate([
                'first_name' => 'sometimes|required|string|max:20',
                'last_name' => 'sometimes|required|string|max:20',
                'personal_phone_number' => 'sometimes|required|string|max:15',
            ]);

            // Actualizar solo los campos permitidos del usuario
            $userFields = ['first_name', 'last_name', 'personal_phone_number'];
            $userUpdates = array_intersect_key($validated, array_flip($userFields));
            
            if (!empty($userUpdates)) {
                $user->update($userUpdates);
            }

            // Recargar el usuario con las relaciones actualizadas
            $user->refresh();
            $user->load(['roles', 'provider']);

            // Construir respuesta actualizada
            $profile = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'personal_phone_number' => $user->personal_phone_number,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'roles' => $user->roles->pluck('name'),
            ];

            if ($user->hasRole('Provider') && $user->provider) {
                $profile['provider'] = [
                    'id' => $user->provider->id,
                    'ced' => $user->provider->ced,
                    'contact_email' => $user->provider->contact_email,
                    'phone_number' => $user->provider->phone_number,
                    'location' => $user->provider->location,
                    'latitude' => $user->provider->lat,
                    'longitude' => $user->provider->long,
                    'experience_years' => $user->provider->experience_years,
                    'schedule_type' => $user->provider->schedule_type,
                    'likes' => $user->provider->likes,
                    'img' => $user->provider->img,
                    'services' => $user->provider->services,
                    'created_at' => $user->provider->created_at,
                    'updated_at' => $user->provider->updated_at,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado exitosamente',
                'data' => $profile
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
