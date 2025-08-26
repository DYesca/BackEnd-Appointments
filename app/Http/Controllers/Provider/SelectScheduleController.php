<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class SelectScheduleController extends Controller
{
    // SELECT 1: Obtener todos los horarios de un proveedor
    public function getAllSchedules($provider_id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $schedules = Schedule::where('provider_id', $provider_id)->get();

        return response()->json($this->formatSchedules($schedules));
    }

    // SELECT 2: Obtener horarios de un proveedor en un día específico
    public function getDaySchedule($provider_id, $day)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $schedules = Schedule::where('provider_id', $provider_id)
            ->where('day', ucfirst(strtolower($day))) // ej. lunes → Lunes
            ->get();

        return response()->json($this->formatSchedules($schedules));
    }

    /**
     * SELECT 1: Obtener todos los horarios de un proveedor (dato limpio de la BD)
     */
    public function getAllSchedulesNotFormatted(int $provider_id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        // Devuelve los registros tal cual están en la tabla schedules
        $schedules = Schedule::where('provider_id', $provider_id)->get();

        return response()->json($schedules);
    }

    /**
     * SELECT 2: Obtener el/los horarios de un proveedor en un día específico
     * (dato limpio de la BD, SIN darle formato)
     */
    public function getDayScheduleNotFormatted(int $provider_id, string $day)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        // Si NO quieres tocar el valor del día, compara tal cual llega:
        $schedules = Schedule::where('provider_id', $provider_id)
            ->where('day', $day)
            ->get();

        return response()->json($schedules);
    }

    // Formateo común
    private function formatSchedules($schedules)
    {
        $formatted = [];

        foreach ($schedules as $schedule) {
            $start = Carbon::createFromFormat('H:i:s', $schedule->start_at)->seconds(0)->milliseconds(0);
            $end = Carbon::createFromFormat('H:i:s', $schedule->end_at)->seconds(0)->milliseconds(0);
            $minutesPerSession = round($schedule->hours_per_session * 60); // <- REDONDEAMOS

            while ($start->copy()->addMinutes($minutesPerSession)->lte($end)) {
                $endAt = $start->copy()->addMinutes($minutesPerSession);

                $formatted[] = [
                    'provider_id' => $schedule->provider_id,
                    'day' => $schedule->day,
                    'start_at' => $start->format('H:i'),
                    'end_at' => $endAt->format('H:i'),
                ];

                $start = $endAt->copy(); // <- continuar sin heredar decimales
            }
        }

        return $formatted;
    }
}
