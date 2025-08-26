<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }

        $user = Auth::user();
        /** @var \App\Models\User $user */
        $token = $user->createToken('api_token')->plainTextToken;

        // Obtener roles como arreglo de nombres
        $roles = $user->roles->pluck('name');

        // Verificar si el usuario tiene información de proveedor
        $providerData = $user->provider;

        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->personal_phone_number,
                'roles' => $roles,
                'provider' => $providerData,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        // Revocar solo el token actual (el que usó para autenticarse)
        $user->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

}
