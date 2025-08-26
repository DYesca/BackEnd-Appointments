<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Provider;
use App\Models\ProvidersSubcategories;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    // Limpiar la tabla users antes de sembrar (sin afectar claves foráneas)
    DB::table('users')->delete();

        $adminRole = Role::where('name', 'Admin')->first();
        $providerRole = Role::where('name', 'Provider')->first();
        $clientRole = Role::where('name', 'Client')->first();

        $locations = [
            ['lat' => 10.626407, 'long' => -85.444972],
            ['lat' => 10.644118, 'long' => -85.446940],
            ['lat' => 10.640726, 'long' => -85.424091],
            ['lat' => 10.620611, 'long' => -85.432721],
            ['lat' => 10.666017, 'long' => -85.471990],
            ['lat' => 10.578041, 'long' => -85.404600],
            ['lat' => 10.426888, 'long' => -85.096300],
            ['lat' => 10.263541, 'long' => -85.586977],
            ['lat' => 9.936730,  'long' => -84.110953],
            ['lat' => 10.404800, 'long' => -85.590071],
        ];

        $subcategories = Subcategory::all();
        if ($subcategories->count() < 5) {
            throw new \Exception("Se requieren al menos 5 subcategorías para asignar 2 proveedores por subcategoría.");
        }

        // === Crear 2 administradores ===
        $admins = [
            [
                'first_name' => 'Admin',
                'last_name' => 'Proveedor',
                'email' => 'admin1@example.com',
                'phone' => '88880001',
                'as_provider' => true,
                'provider_data' => [
                    'ced' => '111111111',
                    'contact_email' => 'admin1@empresa.com',
                    'phone_number' => '88880001',
                    'location' => 'San José',
                    'lat' => 9.936730,
                    'long' => -84.110953,
                    'experience_years' => 10,
                    'schedule_type' => true,
                    'img' => 'img/provider/user_default_icon.png',
                ]
            ],
            [
                'first_name' => 'Admin',
                'last_name' => 'Normal',
                'email' => 'admin2@example.com',
                'phone' => '88880002',
                'as_provider' => false,
            ],
        ];

        foreach ($admins as $data) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'personal_phone_number' => $data['phone'],
            ]);
            $user->roles()->attach($adminRole);

            if ($data['as_provider']) {
                $user->roles()->attach($providerRole);
                $provider = Provider::create([
                    ...$data['provider_data'],
                    'user_id' => $user->id,
                ]);

                $subcategory = $subcategories->random();
                ProvidersSubcategories::create([
                    'provider_id' => $provider->id,
                    'subcategory_id' => $subcategory->id,
                    'father_category' => $subcategory->category_id,
                ]);
            }
        }

        // === Crear 4 clientes ===
        for ($i = 1; $i <= 4; $i++) {
            $user = User::create([
                'first_name' => 'Cliente',
                'last_name' => "Número $i",
                'email' => "client$i@example.com",
                'password' => Hash::make('password'),
                'personal_phone_number' => "8888000" . (2 + $i),
            ]);
            $user->roles()->attach($clientRole);
        }

        // === Crear 9 proveedores (2 por subcategoría) ===
        $locationIndex = 0;
        $providerCount = 1;

        foreach ($subcategories as $subcategory) {
            for ($i = 0; $i < 2; $i++) {
                if ($locationIndex >= count($locations)) {
                    break; // Evitar desbordar ubicaciones
                }

                $user = User::create([
                    'first_name' => 'Proveedor',
                    'last_name' => "Número $providerCount",
                    'email' => "provider$providerCount@example.com",
                    'password' => Hash::make('password'),
                    'personal_phone_number' => "8888001$providerCount",
                ]);
                $user->roles()->attach($providerRole);

                $location = $locations[$locationIndex++];

                $provider = Provider::create([
                    'ced' => (string)(100000000 + $providerCount),
                    'contact_email' => "provider$providerCount@empresa.com",
                    'phone_number' => "8888001$providerCount",
                    'location' => 'Ubicación #' . $providerCount,
                    'lat' => $location['lat'],
                    'long' => $location['long'],
                    'experience_years' => rand(1, 10),
                    'schedule_type' => (bool)rand(0, 1),
                    'user_id' => $user->id,
                    'img' => 'img/provider/user_default_icon.png',
                ]);

                ProvidersSubcategories::create([
                    'provider_id' => $provider->id,
                    'subcategory_id' => $subcategory->id,
                    'father_category' => $subcategory->category_id,
                ]);

                $providerCount++;
            }
        }
    }
}
