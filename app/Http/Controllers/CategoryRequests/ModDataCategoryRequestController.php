<?php

namespace App\Http\Controllers\CategoryRequests;

use App\Http\Controllers\Controller;
use App\Models\CategoryRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ModDataCategoryRequestController extends Controller
{
    /**
     * El provider autenticado crea la solicitud.
     * - provider_id se toma SIEMPRE del usuario autenticado.
     * - status se fuerza a 'pending'.
     */
    public function storeProvider(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user || !$user->hasRole('Provider')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $data = $request->validate([
            'type'                   => ['required', Rule::in(['category', 'subcategory', 'both'])],
            'current_category_id'    => ['nullable', 'exists:categories,id'],
            'current_subcategory_id' => ['nullable', 'exists:subcategories,id'],
            'justification'          => ['required', 'string'],
        ]);

        // provider_id = id del usuario autenticado (como pediste)
        $data['provider_id'] = $user->provider->id;
        $data['status']      = 'pending';

        $categoryRequest = CategoryRequest::create($data);

        return response()->json([
            'message' => 'Solicitud creada correctamente.',
            'data'    => $categoryRequest,
        ], 201);
    }

    /**
     * Un admin revisa la solicitud y da respuesta.
     * - reviewed_by SIEMPRE es el admin autenticado.
     * - reviewed_at se setea a now() si no lo envían.
     */
    public function updateAdmin(Request $request, int $id)
    {
        /** @var \App\Models\User|null $admin */
        $admin = Auth::user();

        if (!$admin || !$admin->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'status'        => ['required', Rule::in(['approved', 'rejected'])],
            'reviewed_at'   => ['nullable', 'date'],
            'admin_comment' => ['nullable', 'string'],
        ]);

        $categoryRequest = CategoryRequest::findOrFail($id);

        $categoryRequest->update([
            'status'        => $validated['status'],
            'reviewed_by'   => $admin->id, // se fuerza al admin autenticado
            'reviewed_at'   => $validated['reviewed_at'] ?? now(),
            'admin_comment' => $validated['admin_comment'] ?? null,
        ]);

        return response()->json([
            'message' => 'Solicitud revisada y actualizada correctamente.',
            'data'    => $categoryRequest->refresh(),
        ]);
    }

    /**
     * changeStatus
     * Un admin cambia el estado de una solicitud (approved o rejected).
     * Campos:
     *  - id (en la ruta)
     *  - status: approved|rejected
     * (No obliga a cambiar admin_comment, pero puedes permitirlo si quieres)
     */
    public function changeStatus(Request $request, int $id)
    {
        /** @var \App\Models\User|null $admin */
        $admin = Auth::user();

        if (!$admin || !$admin->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'status'        => ['required', Rule::in(['approved', 'rejected'])],
            'admin_comment' => ['nullable', 'string'],
        ]);

        $categoryRequest = CategoryRequest::findOrFail($id);

        $categoryRequest->update([
            'status'        => $validated['status'],
            'admin_comment' => $validated['admin_comment'] ?? $categoryRequest->admin_comment,
            'reviewed_by'   => $request->user()->id,
            'reviewed_at'   => now(),
        ]);

        return response()->json([
            'message' => 'Estado actualizado correctamente.',
            'data'    => $categoryRequest->refresh(),
        ]);
    }

    /**
     * destroy
     * Elimina un registro por ID.
     * (Puedes restringir a: solo pending o solo Admin, según tu regla de negocio)
     */
    public function destroy(Request $request, int $id)
    {
        /** @var \App\Models\User|null $admin */
        $admin = Auth::user();

        if (!$admin || !$admin->hasRole('Admin')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $categoryRequest = CategoryRequest::find($id);

        if (!$categoryRequest) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        $categoryRequest->delete();

        // 204 no lleva contenido, pero puedes usar 200 si quieres un mensaje.
        return response()->json([
            'message' => 'Solicitud eliminada correctamente.',
        ], 200);
    }
}
