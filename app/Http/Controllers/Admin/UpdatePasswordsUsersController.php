<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UpdatePasswordsUsersController extends Controller
{
    public function updatePassword(Request $request, $id)
    {
        /** @var \App\Models\User|null $admin */
        
        $admin = Auth::user();

        if (!$admin || !$admin->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        try {
            $validated = $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación de contraseña.',
                'details' => $e->errors()
            ], 422);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }
}
