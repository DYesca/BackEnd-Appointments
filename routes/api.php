<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\UpdateUserController;
use App\Http\Controllers\Auth\ProfileController;

use App\Http\Controllers\Admin\DeleteUserController;
use App\Http\Controllers\Admin\SelectUsersController;
use App\Http\Controllers\Admin\UpdatePasswordsUsersController;
use App\Http\Controllers\Admin\UpdateUsersController;

use App\Http\Controllers\Category\ModDataCategoryController;
use App\Http\Controllers\Category\SelectCategoryController;
use App\Http\Controllers\CategoryRequests\ModDataCategoryRequestController;
use App\Http\Controllers\CategoryRequests\SelectCategoryRequestController;
use App\Http\Controllers\SubCategory\ModDataSubCategoryController;
use App\Http\Controllers\SubCategory\SelectSubCategoryController;

use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\Provider\LikeController;
use App\Http\Controllers\Provider\ModDataScheduleController;
use App\Http\Controllers\Provider\SelectScheduleController;
use App\Http\Controllers\Provider\AppointmentController;
use App\Http\Controllers\Provider\SelectAppointmentController;
use App\Http\Controllers\Provider\SelectProviderInfoController;
use App\Http\Controllers\Auth\BroadcastAuthController;
use Illuminate\Support\Facades\Broadcast;

// LOGIN y LOGOUT
Route::post('/login', [LoginController::class, 'login'])->name('auth.login');
Route::middleware('auth:sanctum')->post('/logout', [LoginController::class, 'logout'])->name('auth.logout');

// REGISTRO
Route::post('/register/client', [RegisterController::class, 'registerClient'])->name('register.client');
Route::post('/register/provider', [RegisterController::class, 'registerProvider'])->name('register.provider');
Route::middleware(['auth:sanctum', 'role:Admin'])->post('/register/admin', [RegisterController::class, 'registerAdmin'])->name('register.admin');

// Actualizar datos del usuario autenticado
Route::middleware('auth:sanctum')->put('/user/update', [UpdateUserController::class, 'update'])->name('user.update');

// PERFIL DEL USUARIO
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// FUNCIONES DE ADMINISTRADOR - USUARIOS
Route::middleware(['auth:sanctum', 'role:Admin'])->prefix('admin/users')->group(function () {
    Route::put('{id}/update', [UpdateUsersController::class, 'update'])->name('admin.users.update');
    Route::put('{id}/password', [UpdatePasswordsUsersController::class, 'updatePassword'])->name('admin.users.password');
    Route::get('list', [SelectUsersController::class, 'listByType'])->name('admin.users.list');
    Route::get('search', [SelectUsersController::class, 'searchByType'])->name('admin.users.search');
    Route::get('find', [SelectUsersController::class, 'findByTypeAndId'])->name('admin.users.find');
    Route::delete('{id}', [DeleteUserController::class, 'destroy'])->name('admin.users.delete');
});

// CATEGORÍAS (ADMIN)
Route::middleware(['auth:sanctum', 'role:Admin'])->prefix('admin/categories')->group(function () {
    Route::get('/search', [SelectCategoryController::class, 'findByName'])->name('admin.categories.search');
    Route::get('/{id}', [SelectCategoryController::class, 'findById'])->name('admin.categories.get');
    Route::post('/', [ModDataCategoryController::class, 'store'])->name('admin.categories.create');
    Route::put('/{id}', [ModDataCategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/{id}', [ModDataCategoryController::class, 'destroy'])->name('admin.categories.delete');
});

// SUBCATEGORÍAS (ADMIN)
Route::middleware(['auth:sanctum', 'role:Admin'])->prefix('admin/subcategories')->group(function () {
    Route::get('/search', [SelectSubCategoryController::class, 'searchByName'])->name('admin.subcategories.search');
    Route::get('/{id}', [SelectSubCategoryController::class, 'getById'])->name('admin.subcategories.get');
    Route::post('/', [ModDataSubCategoryController::class, 'store'])->name('admin.subcategories.create');
    Route::put('/{id}', [ModDataSubCategoryController::class, 'update'])->name('admin.subcategories.update');
    Route::delete('/{id}', [ModDataSubCategoryController::class, 'destroy'])->name('admin.subcategories.delete');
});

// DASHBOARD SEGÚN ROL
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard/client', [UserRoleController::class, 'client'])->middleware('role:Client')->name('dashboard.client');
    Route::get('/dashboard/provider', [UserRoleController::class, 'provider'])->middleware('role:Provider')->name('dashboard.provider');
    Route::get('/dashboard/admin', [UserRoleController::class, 'admin'])->middleware('role:Admin')->name('dashboard.admin');
});

// SCHEDULES (CRUD – PROVEEDORES AUTENTICADOS)
Route::middleware(['auth:sanctum', 'role:Provider'])->prefix('schedules')->group(function () {
    Route::post('/', [ModDataScheduleController::class, 'store'])->name('schedules.create');
    Route::put('/{id}', [ModDataScheduleController::class, 'update'])->name('schedules.update');
    Route::delete('/{id}', [ModDataScheduleController::class, 'destroy'])->name('schedules.delete');
});

// LIKES DE PROVEEDORES (USUARIOS AUTENTICADOS)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/likes', [LikeController::class, 'store'])->name('likes.add');
    Route::delete('/likes/{provider_id}', [LikeController::class, 'destroy'])->name('likes.remove');

    // Info proveedor
    Route::get('/provider/show/{id}', [SelectProviderInfoController::class, 'getAuthenticatedProviderById'])->name('provider.show');

    // Horarios públicos de proveedores
    Route::get('/providers/{provider_id}/schedules', [SelectScheduleController::class, 'getAllSchedules'])->name('provider.schedules.all');
    Route::get('/providers/{provider_id}/schedules/{day}', [SelectScheduleController::class, 'getDaySchedule'])->name('provider.schedules.day');
    Route::get('/providers/{provider_id}/schedulesNotFormatted', [SelectScheduleController::class, 'getAllSchedulesNotFormatted'])->name('provider.schedules.all.not.formatted');
    Route::get('/providers/{provider_id}/schedulesNotFormatted/{day}', [SelectScheduleController::class, 'getDayScheduleNotFormatted'])->name('provider.schedules.day.not.formatted');
});

// CITAS
Route::middleware('auth:sanctum')->prefix('appointments')->group(function () {

    // Citas de proveedor autenticado
    Route::prefix('provider')->group(function () {
        Route::get('/pending', [SelectAppointmentController::class, 'pendingByProvider'])->name('appointments.provider.pending');
        Route::get('/confirmed', [SelectAppointmentController::class, 'confirmedByProvider'])->name('appointments.provider.confirmed');
        Route::get('/cancelled', [SelectAppointmentController::class, 'cancelledByProvider'])->name('appointments.provider.cancelled');
        Route::get('/all/{providerId}', [SelectAppointmentController::class, 'allByProviderId'])->name('appointments.provider.all');
        Route::get('/user/{userId}', [SelectAppointmentController::class, 'allByUserIdAsProvider'])->name('appointments.provider.byUserId');
    });

    // Citas del cliente autenticado
    Route::prefix('client')->group(function () {
        Route::get('/pending', [SelectAppointmentController::class, 'pendingByProviderAgain'])->name('appointments.client.pending');
        Route::get('/confirmed', [SelectAppointmentController::class, 'confirmedByClient'])->name('appointments.client.confirmed');
        Route::get('/cancelled', [SelectAppointmentController::class, 'cancelledByClient'])->name('appointments.client.cancelled');
        Route::get('/all/{clientId}', [SelectAppointmentController::class, 'allByClientId'])->name('appointments.client.all');
    });

    // Crear cita
    Route::post('/', [AppointmentController::class, 'store'])->name('appointments.create');

    // Confirmar/cancelar cita
    Route::patch('/{id}/confirm', function ($id, Request $request) {
        $request->merge(['status' => 'confirmed']);
        return app(AppointmentController::class)->updateStatus($request, $id);
    })->name('appointments.confirm');

    Route::patch('/{id}/cancel', function ($id, Request $request) {
        $request->merge(['status' => 'cancelled']);
        return app(AppointmentController::class)->updateStatus($request, $id);
    })->name('appointments.cancel');
});

// Provider crea la solicitud
Route::middleware(['auth:sanctum', 'role:Provider'])->prefix('category-requests')->group(function () {
    Route::post('/', [ModDataCategoryRequestController::class, 'storeProvider'])->name('category-requests.create');
    Route::get('/', [SelectCategoryRequestController::class, 'getByProvider'])->name('category-requests.by-provider');
});

// ADMIN gestiona las solicitudes
Route::middleware(['auth:sanctum', 'role:Admin'])->prefix('admin/category-requests')->group(function () {
    Route::put('{id}/review', [ModDataCategoryRequestController::class, 'updateAdmin'])->name('admin.category-requests.review');
    Route::patch('{id}/status', [ModDataCategoryRequestController::class, 'changeStatus'])->name('admin.category-requests.status');
    Route::delete('{id}', [ModDataCategoryRequestController::class, 'destroy'])->name('admin.category-requests.delete');
    Route::get('/responded-requests', [SelectCategoryRequestController::class, 'getRespondedByAdmin'])->name('admin.category-requests.responded');
    Route::get('/requests/pending', [SelectCategoryRequestController::class, 'getPending'])->name('admin.category-requests.pending');
});


Route::get('/providers/category-search/{category}/{subcategory}', [SelectProviderInfoController::class, 'filterProvidersByCategory']);
Route::post('/providers/geo-search', [SelectProviderInfoController::class, 'searchProvidersByLocation']);
Route::get('/categories', [SelectCategoryController::class, 'all'])->name('public.categories.all');

// RUTA DE AUTENTICACIÓN DE BROADCASTING
Route::post('/broadcasting/auth', function (Request $request) {
    
    if ($request->hasSession()){
      session()->reflash();
    }
    return Broadcast::auth($request);
})->middleware(['auth:sanctum', 'api']);
