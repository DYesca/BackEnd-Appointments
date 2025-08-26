<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DeleteUserController extends Controller
{
    public function destroy($id)
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

        // Eliminar imagen física del proveedor si existe y no es la imagen por defecto
        if ($user->provider && $user->provider->img) {
            $imgPath = public_path($user->provider->img);
            $defaultImg = public_path('img/provider/user_default_icon.png');

            if (file_exists($imgPath) && realpath($imgPath) !== realpath($defaultImg)) {
                unlink($imgPath);
            }
        }

        // Eliminar relaciones: proveedor, roles, tokens, etc.
        if ($user->provider) {
            $user->provider->delete();
        }

        $user->roles()->detach(); // quita todos los roles

        $user->tokens()->delete(); // elimina todos los tokens (Sanctum)

        $user->delete();

        return response()->json(['message' => 'Usuario y su información eliminados correctamente.']);
    }
}
