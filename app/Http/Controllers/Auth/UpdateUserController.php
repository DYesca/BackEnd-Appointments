<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ProvidersSubcategories;
use App\Models\Role;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UpdateUserController extends Controller
{
    public function update(Request $request)
    {
        /** @var \App\Models\User|null $user */

        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        if ($user->hasRole('Admin')) {
            return $this->updateAdmin($request, $user);
        } elseif ($user->hasRole('Provider')) {
            return $this->updateProvider($request, $user);
        } elseif ($user->hasRole('Client')) {
            return $this->updateClient($request, $user);
        } else {
            return response()->json(['error' => 'Tipo de usuario no identificado'], 403);
        }
    }

    private function updateClient(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'sometimes|required|string|max:20',
                'last_name' => 'sometimes|required|string|max:20',
                'email' => 'sometimes|required|email|max:50|unique:users,email,' . $user->id,
                'personal_phone_number' => 'sometimes|required|string|max:15',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validación fallida para cliente',
                'details' => $e->errors()
            ], 422);
        }

        $user->update($validated);
        return response()->json(['message' => 'Información de cliente actualizada correctamente.']);
    }

    private function updateProvider(Request $request, User $user)
    {
        try {
            $validatedUser = $request->validate([
                'first_name' => 'sometimes|required|string|max:20',
                'last_name' => 'sometimes|required|string|max:20',
                'email' => 'sometimes|required|email|max:50|unique:users,email,' . $user->id,
                'personal_phone_number' => 'sometimes|required|string|max:15',
            ]);

            $validatedProvider = $request->validate([
                'ced' => 'sometimes|required|string|max:14|unique:providers,ced,' . $user->provider->id,
                'contact_email' => 'sometimes|required|email|max:40',
                'phone_number' => 'sometimes|required|string|max:14',
                'location' => 'sometimes|required|string|max:255',
                'long' => 'sometimes|required|string|max:40',
                'lat' => 'sometimes|required|string|max:40',
                'experience_years' => 'sometimes|required|integer|min:0|max:65535',
                'schedule_type' => 'sometimes|required|boolean',
                'subcategory_id' => 'sometimes|required|exists:subcategories,id',
                'father_category' => 'sometimes|required|exists:categories,id',
                'img' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validación fallida para colaborador',
                'details' => $e->errors()
            ], 422);
        }

        $user->update($validatedUser);

        // Manejo imagen
        if ($request->hasFile('img')) {
            $defaultImgPath = 'img/provider/user_default_icon.png';
            $currentImg = $user->provider->img ?? null;

            // Solo eliminar si no es la imagen por defecto y si existe el archivo
            if ($currentImg && $currentImg !== $defaultImgPath && file_exists(public_path($currentImg))) {
                unlink(public_path($currentImg));
            }

            $imgFile = $request->file('img');
            $imgName = uniqid() . '.' . $imgFile->getClientOriginalExtension();
            $imgFile->move(public_path('img/provider'), $imgName);
            $validatedProvider['img'] = 'img/provider/' . $imgName;
        }

        $user->provider->update($validatedProvider);

        if ($request->has(['subcategory_id', 'father_category'])) {
            $subcategory = Subcategory::where('id', $request->subcategory_id)
                ->where('category_id', $request->father_category)
                ->first();

            if (!$subcategory) {
                return response()->json([
                    'error' => 'La subcategoría no pertenece a la categoría indicada.'
                ], 422);
            }

            ProvidersSubcategories::updateOrCreate(
                ['provider_id' => $user->provider->id],
                [
                    'subcategory_id' => $request->subcategory_id,
                    'father_category' => $request->father_category
                ]
            );
        }

        return response()->json(['message' => 'Información de colaborador actualizada correctamente.']);
    }

    private function updateAdmin(Request $request, User $user)
    {
        try {
            $validatedUser = $request->validate([
                'first_name' => 'sometimes|required|string|max:20',
                'last_name' => 'sometimes|required|string|max:20',
                'email' => 'sometimes|required|email|max:50|unique:users,email,' . $user->id,
                'personal_phone_number' => 'sometimes|required|string|max:15',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validación fallida para admin',
                'details' => $e->errors()
            ], 422);
        }

        $user->update($validatedUser);

        // Verificar si hay datos de proveedor en la solicitud
        $providerDataKeys = [
            'ced',
            'contact_email',
            'phone_number',
            'location',
            'long',
            'lat',
            'experience_years',
            'schedule_type'
        ];

        $hasProviderData = collect($providerDataKeys)->contains(function ($key) use ($request) {
            return $request->has($key);
        });

        if ($hasProviderData) {
            try {
                $validatedProvider = $request->validate([
                    'ced' => 'sometimes|required|string|max:14|unique:providers,ced,' . optional($user->provider)->id,
                    'contact_email' => 'sometimes|required|email|max:40',
                    'phone_number' => 'sometimes|required|string|max:14',
                    'location' => 'sometimes|required|string|max:255',
                    'long' => 'sometimes|required|string|max:40',
                    'lat' => 'sometimes|required|string|max:40',
                    'experience_years' => 'sometimes|required|integer|min:0|max:65535',
                    'schedule_type' => 'sometimes|required|boolean',
                    'subcategory_id' => 'sometimes|required|exists:subcategories,id',
                    'father_category' => 'sometimes|required|exists:categories,id',
                    'img' => 'sometimes|nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
                ]);

                // Manejo imagen
                if ($request->hasFile('img')) {
                    $defaultImgPath = 'img/provider/user_default_icon.png';
                    $currentImg = $user->provider->img ?? null;

                    // Solo eliminar si no es la imagen por defecto y si existe el archivo
                    if ($currentImg && $currentImg !== $defaultImgPath && file_exists(public_path($currentImg))) {
                        unlink(public_path($currentImg));
                    }

                    $imgFile = $request->file('img');
                    $imgName = uniqid() . '.' . $imgFile->getClientOriginalExtension();
                    $imgFile->move(public_path('img/provider'), $imgName);
                    $validatedProvider['img'] = 'img/provider/' . $imgName;
                }

                if ($user->provider) {
                    $user->provider->update($validatedProvider);
                } else {
                    $user->provider()->create($validatedProvider);
                }

                // Asignar rol de Provider si no lo tiene
                if (!$user->hasRole('Provider')) {
                    $providerRole = Role::where('name', 'Provider')->first();
                    if ($providerRole) {
                        $user->roles()->attach($providerRole);
                    }
                }

                if ($request->has(['subcategory_id', 'father_category'])) {
                    $subcategory = Subcategory::where('id', $request->subcategory_id)
                        ->where('category_id', $request->father_category)
                        ->first();

                    if (!$subcategory) {
                        return response()->json([
                            'error' => 'La subcategoría no pertenece a la categoría indicada.'
                        ], 422);
                    }

                    ProvidersSubcategories::updateOrCreate(
                        ['provider_id' => $user->provider->id],
                        [
                            'subcategory_id' => $request->subcategory_id,
                            'father_category' => $request->father_category
                        ]
                    );
                }
            } catch (ValidationException $e) {
                return response()->json([
                    'error' => 'Validación fallida para admin proveedor',
                    'details' => $e->errors()
                ], 422);
            }
        }

        return response()->json(['message' => 'Información del admin actualizada correctamente.']);
    }
}
