<?php

namespace App\Http\Controllers\CategoryRequests;

use App\Http\Controllers\Controller;
use App\Models\CategoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SelectCategoryRequestController extends Controller
{
    /**
     * Obtener solicitudes respondidas por el admin autenticado según status approved o rejected
     */
    public function getRespondedByAdmin()
    {
        /** @var \App\Models\User|null $admin */
        $admin = Auth::user();

        if (!$admin || !$admin->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Trae las solicitudes revisadas por el admin y con status approved o rejected
        $requests = CategoryRequest::where('reviewed_by', $admin->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->get();

        return response()->json(['data' => $requests]);
    }

    /**
     * Obtener todas las solicitudes en estado pendiente
     */
    public function getPending()
    {
        /** @var \App\Models\User|null $admin */
        $admin = Auth::user();

        if (!$admin || !$admin->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $requests = CategoryRequest::where('status', 'pending')->get();

        return response()->json(['data' => $requests]);
    }

    /**
     * Obtener solicitudes del proveedor autenticado según estado (pending, approved, rejected)
     */
    public function getByProvider(Request $request)
    {
        /** @var \App\Models\User|null $providerUser */
        $providerUser = Auth::user();

        if (!$providerUser || !$providerUser->hasRole('Provider')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $status = $request->query('status', 'pending');

        if (!in_array($status, ['pending', 'approved', 'rejected'])) {
            return response()->json(['message' => 'Estado inválido.'], 422);
        }

        // Asumiendo relación: User -> Provider
        $provider = $providerUser->provider;

        if (!$provider) {
            return response()->json(['message' => 'Proveedor no encontrado.'], 404);
        }

        $requests = CategoryRequest::where('provider_id', $provider->id)
            ->where('status', $status)
            ->get();

        return response()->json(['data' => $requests]);
    }
}
