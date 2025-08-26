<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Provider;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener usuarios cliente (excluyendo administradores y proveedores)
        $clients = User::whereHas('roles', function ($query) {
            $query->where('name', 'Client');
        })->get();

        // Obtener todos los proveedores
        $providers = Provider::all();

        // Obtener todos los horarios disponibles
        $schedules = Schedule::all();

        if ($clients->isEmpty() || $providers->isEmpty() || $schedules->isEmpty()) {
            $this->command->warn('No hay suficientes clientes, proveedores o horarios para crear citas.');
            return;
        }

        $statuses = ['pending', 'confirmed', 'cancelled'];
        $appointmentCount = 0;

        // Crear citas para los próximos 30 días y los últimos 15 días
        $startDate = Carbon::now()->subDays(15);
        $endDate = Carbon::now()->addDays(30);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            // No crear citas los domingos (ya que no hay horarios)
            if ($date->format('N') == 7) {
                continue;
            }

            // Crear entre 0 y 3 citas por día
            $appointmentsPerDay = rand(0, 3);

            for ($i = 0; $i < $appointmentsPerDay; $i++) {
                // Seleccionar un proveedor aleatorio
                $provider = $providers->random();

                // Obtener el día de la semana en español
                $dayName = $this->getDayName($date->format('N'));

                // Buscar horario del proveedor para ese día
                $schedule = Schedule::where('provider_id', $provider->id)
                    ->where('day', $dayName)
                    ->first();

                if (!$schedule) {
                    continue;
                }

                // Generar slots válidos para ese día y horario
                $validSlots = $this->generateValidSlots($schedule);

                if (empty($validSlots)) {
                    continue;
                }

                // Seleccionar un slot aleatorio
                $selectedSlot = $validSlots[array_rand($validSlots)];

                // Seleccionar un cliente aleatorio (que no sea el mismo usuario del proveedor)
                $availableClients = $clients->filter(function ($client) use ($provider) {
                    return $client->id !== $provider->user_id;
                });

                if ($availableClients->isEmpty()) {
                    continue;
                }

                $client = $availableClients->random();

                // Verificar que no exista una cita con el mismo proveedor, fecha y horario
                $existingAppointment = Appointment::where('provider_id', $provider->id)
                    ->where('appointment_date', $date->format('Y-m-d'))
                    ->where('start_at', $selectedSlot['start'])
                    ->where('end_at', $selectedSlot['end'])
                    ->exists();

                if ($existingAppointment) {
                    continue;
                }

                // Determinar el estado de la cita
                $status = $this->determineStatus($date);

                // Crear la cita
                $appointment = Appointment::create([
                    'client_id' => $client->id,
                    'provider_id' => $provider->id,
                    'schedule_id' => $schedule->id,
                    'appointment_date' => $date->format('Y-m-d'),
                    'start_at' => $selectedSlot['start'],
                    'end_at' => $selectedSlot['end'],
                    'status' => $status,
                ]);

                // Si la cita está confirmada, incrementar el contador de servicios del proveedor
                if ($status === 'confirmed') {
                    try {
                        $provider->refresh(); // Refrescar el modelo para evitar problemas de concurrencia
                        $provider->services = $provider->services + 1;
                        $provider->save();
                    } catch (\Exception $e) {
                        // Si hay error con el incremento, continuar sin fallar el seeder
                        $this->command->warn("No se pudo incrementar servicios para proveedor {$provider->id}: " . $e->getMessage());
                    }
                }

                $appointmentCount++;
            }
        }

        $this->command->info("Se crearon {$appointmentCount} citas exitosamente.");
    }

    /**
     * Generar slots válidos basados en el horario del proveedor
     */
    private function generateValidSlots(Schedule $schedule): array
    {
        $slots = [];
        $minutesPerSession = round($schedule->hours_per_session * 60);

        $current = Carbon::createFromFormat('H:i:s', $schedule->start_at)->seconds(0);
        $endLimit = Carbon::createFromFormat('H:i:s', $schedule->end_at)->seconds(0);

        while ($current->copy()->addMinutes($minutesPerSession)->lte($endLimit)) {
            $slotStart = $current->copy();
            $slotEnd = $slotStart->copy()->addMinutes($minutesPerSession);

            $slots[] = [
                'start' => $slotStart->format('H:i'),
                'end' => $slotEnd->format('H:i'),
            ];

            $current = $slotEnd;
        }

        return $slots;
    }

    /**
     * Determinar el estado de la cita basado en la fecha
     */
    private function determineStatus(Carbon $date): string
    {
        $now = Carbon::now();

        // Citas pasadas tienen mayor probabilidad de estar confirmadas o canceladas
        if ($date->lt($now)) {
            $statuses = ['confirmed', 'confirmed', 'confirmed', 'cancelled']; // 75% confirmadas, 25% canceladas
            return $statuses[array_rand($statuses)];
        }

        // Citas futuras pueden estar en cualquier estado
        $statuses = ['pending', 'pending', 'confirmed', 'cancelled']; // 50% pendientes, 25% confirmadas, 25% canceladas
        return $statuses[array_rand($statuses)];
    }

    /**
     * Convertir número de día a nombre en español
     */
    private function getDayName(int $dayNumber): string
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

        return $days[$dayNumber] ?? 'Lunes';
    }
}
