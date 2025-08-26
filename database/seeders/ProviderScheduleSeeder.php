<?php

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\Schedule;
use Illuminate\Database\Seeder;

class ProviderScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $days = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

        // Solo proveedores con horario estricto
        $strictProviders = Provider::where('schedule_type', true)->get();

        $usedStartTimes = [];

        foreach ($strictProviders as $provider) {
            // Asegurar que cada proveedor tenga un horario único
            do {
                // Generar una hora de inicio entre 07:00 y 10:00
                $startHour = rand(7, 10);
                $startMinute = rand(0, 1) ? '00' : '30';
                $startAt = sprintf('%02d:%s', $startHour, $startMinute);

                // La duración será entre 4 y 6 horas
                $duration = rand(4, 6);
                $endHour = $startHour + $duration;
                $endAt = sprintf('%02d:%s', $endHour, $startMinute);
            } while (in_array($startAt . '-' . $endAt, $usedStartTimes)); // Evitar duplicados

            $usedStartTimes[] = $startAt . '-' . $endAt;

            foreach ($days as $day) {
                Schedule::create([
                    'provider_id' => $provider->id,
                    'day' => $day,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'hours_per_session' => rand(1, 2),
                ]);
            }
        }
    }
}
