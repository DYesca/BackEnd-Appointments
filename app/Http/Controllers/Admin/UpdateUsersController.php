<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UpdateUsersController extends Controller
{
    public function update(Request $request, $id)
    {
        /** @var \App\Models\User|null $authUser */

        $authUser = Auth::user();

        if (!$authUser || !$authUser->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        if ($user->hasRole('Admin')) {
            return $this->updateAdminUser($user, $request);
        } elseif ($user->hasRole('Provider')) {
            return $this->updateProviderUser($user, $request);
        } elseif ($user->hasRole('Client')) {
            return $this->updateClientUser($user, $request);
        } else {
            return response()->json(['message' => 'Tipo de usuario no reconocido.'], 422);
        }
    }

    private function updateAdminUser(User $user, Request $request)
    {
        try {
            $validatedUser = $request->validate([
                'first_name' => 'sometimes|required|string|max:20',
                'last_name' => 'sometimes|required|string|max:20',
                'email' => 'sometimes|required|email|max:50|unique:users,email,' . $user->id,
                'personal_phone_number' => 'sometimes|required|string|max:15',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validación fallida para admin',
                'details' => $e->errors()
            ], 422);
        }

        $user->update($validatedUser);

        if ($user->provider) {
            try {
                $validatedProvider = $request->validate([
                    'ced' => 'sometimes|required|string|max:14|unique:providers,ced,' . $user->provider->id,
                    'contact_email' => 'sometimes|required|email|max:40',
                    'phone_number' => 'sometimes|required|string|max:14',
                    'location' => 'sometimes|required|string|max:255',
                    'long' => 'sometimes|required|string|max:40',
                    'lat' => 'sometimes|required|string|max:40',
                    'experience_years' => 'sometimes|required|integer|min:0|max:65535',
                    'schedule_type' => 'sometimes|required|boolean',
                    'img' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                ]);

                // Manejo de imagen
                // Manejo de imagen
                if ($request->hasFile('img')) {
                    if (
                        $user->provider->img &&
                        $user->provider->img !== 'img/provider/user_default_icon.png' &&
                        file_exists(public_path($user->provider->img))
                    ) {
                        unlink(public_path($user->provider->img));
                    }

                    $imgFile = $request->file('img');
                    $imgName = uniqid() . '.' . $imgFile->getClientOriginalExtension();
                    $imgFile->move(public_path('img/provider'), $imgName);
                    $validatedProvider['img'] = 'img/provider/' . $imgName;
                }

                $user->provider->update($validatedProvider);
            } catch (ValidationException $e) {
                return response()->json([
                    'error' => 'Validación fallida para admin proveedor',
                    'details' => $e->errors()
                ], 422);
            }
        }

        return response()->json(['message' => 'Información del admin actualizada correctamente.']);
    }

    private function updateProviderUser(User $user, Request $request)
    {
        try {
            $validatedUser = $request->validate([
                'first_name' => 'sometimes|required|string|max:20',
                'last_name' => 'sometimes|required|string|max:20',
                'email' => 'sometimes|required|email|max:50|unique:users,email,' . $user->id,
                'personal_phone_number' => 'sometimes|required|string|max:15',
            ]);

            $validatedProvider = $request->validate([
                'ced' => 'sometimes|required|string|max:14|unique:providers,ced,' . $user->provider->id,
                'contact_email' => 'sometimes|required|email|max:40',
                'phone_number' => 'sometimes|required|string|max:14',
                'location' => 'sometimes|required|string|max:255',
                'long' => 'sometimes|required|string|max:40',
                'lat' => 'sometimes|required|string|max:40',
                'experience_years' => 'sometimes|required|integer|min:0|max:65535',
                'schedule_type' => 'sometimes|required|boolean',
                'img' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validación fallida para proveedor',
                'details' => $e->errors()
            ], 422);
        }

        $user->update($validatedUser);

        // Manejo de imagen
        if ($request->hasFile('img')) {
            if (
                $user->provider->img &&
                $user->provider->img !== 'img/provider/user_default_icon.png' &&
                file_exists(public_path($user->provider->img))
            ) {
                unlink(public_path($user->provider->img));
            }

            $imgFile = $request->file('img');
            $imgName = uniqid() . '.' . $imgFile->getClientOriginalExtension();
            $imgFile->move(public_path('img/provider'), $imgName);
            $validatedProvider['img'] = 'img/provider/' . $imgName;
        }

        $user->provider->update($validatedProvider);

        return response()->json(['message' => 'Información del colaborador actualizada correctamente.']);
    }

    private function updateClientUser(User $user, Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'sometimes|required|string|max:20',
                'last_name' => 'sometimes|required|string|max:20',
                'email' => 'sometimes|required|email|max:50|unique:users,email,' . $user->id,
                'personal_phone_number' => 'sometimes|required|string|max:15',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validación fallida para cliente',
                'details' => $e->errors()
            ], 422);
        }

        $user->update($validated);
        return response()->json(['message' => 'Información del cliente actualizada correctamente.']);
    }
}
