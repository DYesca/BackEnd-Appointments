<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Provider;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Events\AppointmentStatusUpdated;

class AppointmentController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'client_id' => 'required|exists:users,id',
                'provider_id' => 'required|exists:providers,id',
                'appointment_date' => 'required|date',
                'start_at' => 'required|date_format:H:i',
                'end_at' => 'required|date_format:H:i|after:start_at',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validación fallida',
                'details' => $e->errors()
            ], 422);
        }

        $client = User::findOrFail($validated['client_id']);
        $provider = Provider::findOrFail($validated['provider_id']);

        // El cliente no puede ser el proveedor
        if ($client->id === $provider->user_id) {
            return response()->json(['error' => 'No puede reservar una cita consigo mismo.'], 403);
        }

        // Obtener el día de la semana en texto (ej: Lunes)
        $dayOfWeek = $this->getDayName(date('N', strtotime($validated['appointment_date']))); // 1 = Monday

        // Obtener horario del proveedor para ese día
        $schedule = Schedule::where('provider_id', $provider->id)
            ->where('day', $dayOfWeek)
            ->first();

        if (!$schedule) {
            return response()->json(['error' => "El proveedor no tiene horario el día $dayOfWeek"], 400);
        }

        // Validar que la cita esté dentro del rango del horario
        $start = strtotime($validated['start_at']);
        $end = strtotime($validated['end_at']);
        $schedStart = strtotime($schedule->start_at);
        $schedEnd = strtotime($schedule->end_at);
        $duration = ($end - $start) / 3600;

        if ($start < $schedStart || $end > $schedEnd) {
            return response()->json(['error' => 'La cita está fuera del horario disponible.'], 400);
        }

        // Validar que la duración sea exactamente igual a lo definido en el horario
        $allowedDuration = round($schedule->hours_per_session, 3); // Redondeo por precaución
        $actualDuration = round($duration, 3);

        if (abs($actualDuration - $allowedDuration) > 0.01) {
            return response()->json([
                'error' => "La duración de la cita debe ser exactamente de {$allowedDuration} hora(s)."
            ], 400);
        }

        // Validar que el rango solicitado sea un slot válido
        $validSlots = [];
        $minutesPerSession = round($schedule->hours_per_session * 60);

        $current = Carbon::createFromFormat('H:i:s', $schedule->start_at)->seconds(0);
        $endLimit = Carbon::createFromFormat('H:i:s', $schedule->end_at)->seconds(0);

        while ($current->copy()->addMinutes($minutesPerSession)->lte($endLimit)) {
            $slotStart = $current->copy();
            $slotEnd = $slotStart->copy()->addMinutes($minutesPerSession);

            $validSlots[] = [
                'start' => $slotStart->format('H:i'),
                'end' => $slotEnd->format('H:i'),
            ];

            $current = $slotEnd;
        }

        $matchedSlot = collect($validSlots)->first(function ($slot) use ($validated) {
            return $slot['start'] === $validated['start_at'] && $slot['end'] === $validated['end_at'];
        });

        if (!$matchedSlot) {
            return response()->json([
                'error' => 'La cita debe coincidir exactamente con un intervalo válido del horario del proveedor.'
            ], 400);
        }

        // Verificar solapamiento con otras citas activas
        $exists = Appointment::where('provider_id', $provider->id)
            ->where('status', 'pending') // Solo considerar citas pendientes
            ->where('appointment_date', $validated['appointment_date'])
            ->where(function ($q) use ($validated) {
                $q->where('start_at', '<', $validated['end_at'])
                    ->where('end_at', '>', $validated['start_at']);
            })
            ->exists();

        if ($exists) {
            return response()->json(['error' => 'Ya existe una cita pendiente con ese proveedor que se traslapa con el horario seleccionado.'], 409);
        }

        // Crear cita
        $appointment = Appointment::create([
            ...$validated,
            'schedule_id' => $schedule->id,
            'status' => 'pending'
        ]);

        return response()->json(['message' => 'Cita creada exitosamente.', 'appointment' => $appointment], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:confirmed,cancelled',
        ]);

        $appointment = Appointment::with('provider')->find($id);

        if (!$appointment) {
            return response()->json(['error' => 'Cita no encontrada.'], 404);
        }

        $authUser = Auth::user();

        if ($authUser->id !== $appointment->client_id && $authUser->id !== $appointment->provider->user_id) {
            return response()->json([
                'error' => 'No tienes permisos para modificar esta cita.',
                'debug_info' => [
                    'your_user_id' => $authUser->id,
                    'appointment_client_id' => $appointment->client_id,
                    'appointment_provider_user_id' => $appointment->provider->user_id ?? null,
                    'message' => 'Tu ID de usuario no coincide con el cliente ni con el proveedor de esta cita'
                ]
            ], 403);
        }

        // Lógica especial para cancelaciones - PERMITIR cancelar pending Y confirmed
        if ($request->status === 'cancelled') {
            if ($appointment->status === 'cancelled') {
                return response()->json(['error' => 'La cita ya está cancelada.'], 403);
            }

            // Permitir cancelar citas pending o confirmed
            if (!in_array($appointment->status, ['pending', 'confirmed'])) {
                return response()->json(['error' => 'No se puede cancelar una cita con este estado.'], 403);
            }
        } else {
            // Para confirmaciones, solo permitir desde pending
            if ($appointment->status !== 'pending') {
                return response()->json(['error' => 'Solo se pueden confirmar citas con estado pendiente.'], 403);
            }

            // Solo proveedores pueden confirmar citas
            if ($request->status === 'confirmed' && $authUser->id !== $appointment->provider->user_id) {
                return response()->json(['error' => 'Solo los proveedores pueden confirmar citas.'], 403);
            }
        }

        $appointment->status = $request->status;
        $appointment->save();

        \Log::info('✅ CITA ACTUALIZADA EXITOSAMENTE', [
            'appointment_id' => $id,
            'new_status' => $request->status,
            'user_id' => $authUser->id,
        ]);

        if ($request->status === 'confirmed') {
            $provider = $appointment->provider;
            $provider->increment('services');
        }

        $appointment = Appointment::with('provider')->find($id);
        \Log::info('Disparando evento', [
            'client_id' => $appointment->client_id,
            'provider_user_id' => $appointment->provider ? $appointment->provider->user_id : null,
            'status' => $appointment->status,
        ]);
        if (!$appointment->client_id){
            // Si no hay cliente, no se puede disparar el evento
            return response()->json(['error' => 'No se puede actualizar el estado de la cita sin un cliente asociado.'], 400);
        }
        // Disparar evento para notificar en tiempo real
        AppointmentStatusUpdated::dispatch($authUser, $appointment);

        return response()->json([
            'message' => 'Estado de la cita actualizado correctamente.',
            'appointment' => $appointment,
        ]);

    }

    /**
     * Convierte el número de día (1-7) a nombre de día.
     */
    private function getDayName($dayNumber)
    {
        $days = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];
        return $days[$dayNumber] ?? null;
    }
}
