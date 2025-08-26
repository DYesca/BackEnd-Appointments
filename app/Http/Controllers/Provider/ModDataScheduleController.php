<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ModDataScheduleController extends Controller
{
    /**
     * Verifica si el usuario autenticado es provider o admin
     */
    private function authorizeAdmin()
    {
        /** @var \App\Models\User|null $user */
        
        $user = Auth::user();
        if (!$user || !$user->hasRole('Admin') && !$user->hasRole('Provider')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
    }

    // Crear horario
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        try {
            $validated = $request->validate([
                'provider_id' => 'required|exists:providers,id',
                'day' => 'required|string',
                'start_at' => 'required|date_format:H:i',
                'end_at' => 'required|date_format:H:i|after:start_at',
                'hours_per_session' => 'required|numeric|min:0.33|max:24',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validación fallida',
                'details' => $e->errors()
            ], 422);
        }

        $provider = Provider::find($validated['provider_id']);
        if (!$provider->schedule_type) {
            return response()->json(['error' => 'El proveedor tiene un horario flexible y no puede asignar horarios fijos.'], 403);
        }

        // 🔍 Validar si existe un traslape con horarios existentes
        $traslape = Schedule::where('provider_id', $validated['provider_id'])
            ->where('day', $validated['day'])
            ->where(function ($query) use ($validated) {
                $query->where(function ($q) use ($validated) {
                    $q->where('start_at', '<', $validated['end_at'])
                        ->where('end_at', '>', $validated['start_at']);
                });
            })
            ->exists();

        if ($traslape) {
            return response()->json(['error' => 'Existe un horario traslapado para este día.'], 422);
        }

        $schedule = Schedule::create($validated);

        return response()->json(['message' => 'Horario creado correctamente', 'schedule' => $schedule], 201);
    }

    // Editar horario
    public function update(Request $request, $id)
    {
        $this->authorizeAdmin();

        $schedule = Schedule::find($id);
        if (!$schedule) {
            return response()->json(['error' => 'Horario no encontrado'], 404);
        }

        $provider = $schedule->provider;
        if (!$provider->schedule_type) {
            return response()->json(['error' => 'El proveedor tiene un horario flexible y no puede modificar horarios fijos.'], 403);
        }

        try {
            $validated = $request->validate([
                'day' => 'sometimes|required|string',
                'start_at' => 'sometimes|required|date_format:H:i',
                'end_at' => 'sometimes|required|date_format:H:i|after:start_at',
                'hours_per_session' => 'required|numeric|min:0.33|max:24',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validación fallida',
                'details' => $e->errors()
            ], 422);
        }

        // Fusionar valores antiguos con los nuevos para la comparación
        $day = $validated['day'] ?? $schedule->day;
        $start_at = $validated['start_at'] ?? $schedule->start_at;
        $end_at = $validated['end_at'] ?? $schedule->end_at;

        // Verificar traslape excluyendo el horario actual
        $traslape = Schedule::where('provider_id', $provider->id)
            ->where('day', $day)
            ->where('id', '!=', $schedule->id) // Excluir el horario actual
            ->where(function ($query) use ($start_at, $end_at) {
                $query->where('start_at', '<', $end_at)
                    ->where('end_at', '>', $start_at);
            })
            ->exists();

        if ($traslape) {
            return response()->json(['error' => 'Existe un horario traslapado para este día.'], 422);
        }

        $schedule->update($validated);

        return response()->json(['message' => 'Horario actualizado correctamente', 'schedule' => $schedule]);
    }

    // Eliminar horario
    public function destroy($id)
    {
        $this->authorizeAdmin();

        $schedule = Schedule::find($id);
        if (!$schedule) {
            return response()->json(['error' => 'Horario no encontrado'], 404);
        }

        $provider = $schedule->provider;
        if (!$provider->schedule_type) {
            return response()->json(['error' => 'El proveedor tiene un horario flexible y no puede eliminar horarios fijos.'], 403);
        }

        $schedule->delete();

        return response()->json(['message' => 'Horario eliminado correctamente']);
    }
}
