<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SelectAppointmentController extends Controller
{
    // 1. Todas las citas pendientes del proveedor autenticado
    public function pendingByProvider(Request $request)
    {
        return $this->filterAppointments('provider', 'pending', $request);
    }

    // 2. Todas las citas canceladas del usuario autenticado
    public function cancelledByClient(Request $request)
    {
        return $this->filterAppointments('client', 'cancelled', $request);
    }

    // 3. Todas las citas confirmadas del usuario autenticado
    public function confirmedByClient(Request $request)
    {
        return $this->filterAppointments('client', 'confirmed', $request);
    }

    // 4. Todas las citas pendientes del proveedor autenticado (repetida)
    public function pendingByProviderAgain(Request $request)
    {
        return $this->filterAppointments('client', 'pending', $request);
    }

    // 5. Todas las citas canceladas del proveedor autenticado
    public function cancelledByProvider(Request $request)
    {
        return $this->filterAppointments('provider', 'cancelled', $request);
    }

    // 6. Todas las citas confirmadas del proveedor autenticado
    public function confirmedByProvider(Request $request)
    {
        return $this->filterAppointments('provider', 'confirmed', $request);
    }

    /**
     * Método privado para aplicar filtros reutilizables.
     * Incluye información completa del cliente, proveedor y servicio para el frontend.
     */
    private function filterAppointments($role, $status, Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'No autenticado.'], 401);
        }

        if ($role === 'provider') {
            if (!$user->provider) {
                return response()->json(['error' => 'Este usuario no tiene un proveedor asociado.'], 403);
            }

            $column = 'provider_id';
            $id = $user->provider->id; // <- este es el ID correcto
        } else {
            $column = 'client_id';
            $id = $user->id;
        }

        $query = Appointment::with([
            'client:id,first_name,last_name,email',
            'provider.user:id,first_name,last_name,email',
            'provider.subcategoryRelation.subcategory:id,name,category_id',
            'provider.subcategoryRelation.category:id,name',
            'schedule:id,provider_id,day,start_at,end_at,hours_per_session'
        ])->where($column, $id)
          ->where('status', $status);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('appointment_date', [$request->start_date, $request->end_date]);
        }

        $appointments = $query->orderBy('appointment_date', 'desc')->get();

        // Formatear la respuesta para el frontend
        $formattedAppointments = $this->formatAppointmentsForFrontend($appointments);

        return response()->json($formattedAppointments);
    }

    // Método público para obtener todas las citas de un proveedor por su ID, con filtro opcional de fechas
    // Incluye información completa del cliente, proveedor y servicio para el frontend
    public function allByProviderId(Request $request, $providerId)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'No autenticado.'], 401);
        }

        // Verificar permisos: solo el mismo proveedor o un admin pueden ver las citas
        $isAdmin = $user->roles()->where('name', 'Admin')->exists();
        $isOwnProvider = $user->provider && $user->provider->id == $providerId;

        if (!$isAdmin && !$isOwnProvider) {
            return response()->json(['error' => 'No tienes permisos para ver las citas de este proveedor.'], 403);
        }

        $query = Appointment::with([
            'client:id,first_name,last_name,email',
            'provider.user:id,first_name,last_name,email',
            'provider.subcategoryRelation.subcategory:id,name,category_id',
            'provider.subcategoryRelation.category:id,name',
            'schedule:id,provider_id,day,start_at,end_at,hours_per_session'
        ])->where('provider_id', $providerId);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('appointment_date', [$request->start_date, $request->end_date]);
        }

        $appointments = $query->orderBy('appointment_date', 'desc')->get();

        // Formatear la respuesta para el frontend
        $formattedAppointments = $this->formatAppointmentsForFrontend($appointments);

        return response()->json($formattedAppointments);
    }

    // NUEVO MÉTODO: Obtener todas las citas de un proveedor usando su USER_ID
    // Este método resuelve la inconsistencia de IDs en el frontend
    public function allByUserIdAsProvider(Request $request, $userId)
    {
        $authUser = Auth::user();

        if (!$authUser) {
            return response()->json(['error' => 'No autenticado.'], 401);
        }

        // Buscar el provider asociado al user_id
        $targetUser = User::with('provider')->find($userId);
        
        if (!$targetUser) {
            return response()->json(['error' => 'Usuario no encontrado.'], 404);
        }

        if (!$targetUser->provider) {
            return response()->json(['error' => 'Este usuario no es un proveedor.'], 400);
        }

        $providerId = $targetUser->provider->id;

        // Verificar permisos: solo el mismo usuario-proveedor o un admin pueden ver las citas
        $isAdmin = $authUser->roles()->where('name', 'Admin')->exists();
        $isOwnUser = $authUser->id == $userId;

        if (!$isAdmin && !$isOwnUser) {
            return response()->json([
                'error' => 'No tienes permisos para ver las citas de este proveedor.',
                'debug_info' => [
                    'auth_user_id' => $authUser->id,
                    'requested_user_id' => $userId,
                    'is_admin' => $isAdmin,
                    'is_own_user' => $isOwnUser
                ]
            ], 403);
        }

        $query = Appointment::with([
            'client:id,first_name,last_name,email',
            'provider.user:id,first_name,last_name,email',
            'provider.subcategoryRelation.subcategory:id,name,category_id',
            'provider.subcategoryRelation.category:id,name',
            'schedule:id,provider_id,day,start_at,end_at,hours_per_session'
        ])->where('provider_id', $providerId);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('appointment_date', [$request->start_date, $request->end_date]);
        }

        $appointments = $query->orderBy('appointment_date', 'desc')->get();

        // Formatear la respuesta para el frontend
        $formattedAppointments = $this->formatAppointmentsForFrontend($appointments);

        return response()->json([
            'appointments' => $formattedAppointments,
            'provider_info' => [
                'user_id' => $userId,
                'provider_id' => $providerId,
                'user_email' => $targetUser->email,
                'provider_location' => $targetUser->provider->location
            ]
        ]);
    }

    // Método para obtener todas las citas de un cliente específico por su ID
    // Incluye información completa del proveedor, usuario y servicio
    public function allByClientId(Request $request, $clientId)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'No autenticado.'], 401);
        }

        // Verificar permisos: solo el mismo cliente o un admin pueden ver las citas
        $isAdmin = $user->roles()->where('name', 'Admin')->exists();
        $isOwnClient = $user->id == $clientId;

        if (!$isAdmin && !$isOwnClient) {
            return response()->json(['error' => 'No tienes permisos para ver las citas de este cliente.'], 403);
        }

        $query = Appointment::with([
            'client:id,first_name,last_name,email',
            'provider.user:id,first_name,last_name,email',
            'provider.subcategoryRelation.subcategory:id,name,category_id',
            'provider.subcategoryRelation.category:id,name',
            'schedule:id,provider_id,day,start_at,end_at,hours_per_session'
        ])->where('client_id', $clientId);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('appointment_date', [$request->start_date, $request->end_date]);
        }

        $appointments = $query->orderBy('appointment_date', 'desc')->get();

        // Formatear la respuesta para el frontend
        $formattedAppointments = $this->formatAppointmentsForFrontend($appointments);

        return response()->json($formattedAppointments);
    }

    /**
     * Formatea las citas para el frontend con toda la información necesaria
     * Método reutilizable para mantener consistencia en todas las respuestas
     */
    private function formatAppointmentsForFrontend($appointments)
    {
        return $appointments->map(function ($appointment) {
            $subcategoryData = optional($appointment->provider->subcategoryRelation);
            
            return [
                'id' => $appointment->id,
                'appointment_date' => $appointment->appointment_date,
                'start_at' => $appointment->start_at,
                'end_at' => $appointment->end_at,
                'status' => $appointment->status,
                'created_at' => $appointment->created_at,
                'updated_at' => $appointment->updated_at,
                
                // Información del cliente
                'client' => [
                    'id' => $appointment->client->id,
                    'first_name' => $appointment->client->first_name,
                    'last_name' => $appointment->client->last_name,
                    'full_name' => $appointment->client->first_name . ' ' . $appointment->client->last_name,
                    'email' => $appointment->client->email,
                ],
                
                // Información completa del proveedor
                'provider' => [
                    'id' => $appointment->provider->id,
                    'user_id' => $appointment->provider->user_id,
                    'first_name' => $appointment->provider->user->first_name,
                    'last_name' => $appointment->provider->user->last_name,
                    'full_name' => $appointment->provider->user->first_name . ' ' . $appointment->provider->user->last_name,
                    'email' => $appointment->provider->user->email,
                    'location' => $appointment->provider->location,
                    'experience_years' => $appointment->provider->experience_years,
                ],
                
                // Información del servicio (categoría y subcategoría)
                'service' => [
                    'subcategory' => $subcategoryData && $subcategoryData->subcategory ? [
                        'id' => $subcategoryData->subcategory->id,
                        'name' => $subcategoryData->subcategory->name,
                        'category_id' => $subcategoryData->subcategory->category_id,
                    ] : null,
                    'category' => $subcategoryData && $subcategoryData->category ? [
                        'id' => $subcategoryData->category->id,
                        'name' => $subcategoryData->category->name,
                    ] : null,
                ],
                
                // Información del horario
                'schedule' => [
                    'id' => $appointment->schedule->id,
                    'day' => $appointment->schedule->day,
                    'hours_per_session' => $appointment->schedule->hours_per_session,
                ],
                
                // Status traducido al español para el frontend
                'status_text' => $this->getStatusText($appointment->status),
                
                // Fecha formateada para mostrar en el frontend
                'formatted_date' => $this->formatDateForDisplay($appointment->appointment_date),
                
                // Horario formateado
                'formatted_time' => $this->formatTimeRange($appointment->start_at, $appointment->end_at),
            ];
        });
    }

    /**
     * Convierte el estado de la cita a texto en español
     */
    private function getStatusText($status)
    {
        $statusMap = [
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmada',
            'cancelled' => 'Cancelada',
        ];

        return $statusMap[$status] ?? 'Desconocido';
    }

    /**
     * Formatea la fecha para mostrar en el frontend
     */
    private function formatDateForDisplay($date)
    {
        $dateCarbon = \Carbon\Carbon::parse($date);
        $dayNames = [
            'Monday' => 'lunes',
            'Tuesday' => 'martes', 
            'Wednesday' => 'miércoles',
            'Thursday' => 'jueves',
            'Friday' => 'viernes',
            'Saturday' => 'sábado',
            'Sunday' => 'domingo'
        ];
        
        $monthNames = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
        ];
        
        $dayName = $dayNames[$dateCarbon->format('l')];
        $day = $dateCarbon->day;
        $month = $monthNames[$dateCarbon->month];
        $year = $dateCarbon->year;
        
        return "$dayName $day de $month de $year";
    }

    /**
     * Formatea el rango de tiempo para mostrar en el frontend
     */
    private function formatTimeRange($startTime, $endTime)
    {
        $start = \Carbon\Carbon::parse($startTime)->format('H:i');
        $end = \Carbon\Carbon::parse($endTime)->format('H:i');
        
        return "$start-$end";
    }
}
