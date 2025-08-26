<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'provider_id' => 'required|exists:providers,id',
        ]);

        $user = Auth::user();
        $provider = Provider::findOrFail($request->provider_id);

        $existingLike = Like::where('user_id', $user->id)
            ->where('provider_id', $provider->id)
            ->first();

        if ($existingLike) {
            return response()->json(['message' => 'Ya diste like a este proveedor.'], 409);
        }

        Like::create([
            'user_id' => $user->id,
            'provider_id' => $provider->id,
            'liked' => true,
        ]);

        $provider->increment('likes'); // Asume que el campo 'likes' ya existe

        return response()->json(['message' => 'Like registrado exitosamente.'], 201);
    }

    public function destroy($provider_id)
    {
        $user = Auth::user();

        $like = Like::where('user_id', $user->id)
            ->where('provider_id', $provider_id)
            ->first();

        if (!$like) {
            return response()->json(['message' => 'No se encontró el like para eliminar.'], 404);
        }

        $like->delete();

        Provider::where('id', $provider_id)->decrement('likes');

        return response()->json(['message' => 'Like eliminado correctamente.'], 200);
    }
}
