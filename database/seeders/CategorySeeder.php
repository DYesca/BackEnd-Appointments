<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Medicina' => [
                'Ginecología',
                'Obstetra',
                'Pediatra',
                'Urólogo',
                'Dermatólogo',
                'Cirujano general',
                'Cardiólogo',
            ],
            'Mecánica' => [
                'Mecánico automotor',
                'Electro Mecánico',
                'Mecánico de Motor Diesel',
                'Mecánico industrial',
                'Técnico en frenos',
            ],
            'Informática' => [
                'Desarrollador Web',
                'Soporte Técnico',
                'Administrador de Redes',
                'Ciberseguridad',
                'DevOps',
            ],
            'Educación' => [
                'Docente de primaria',
                'Docente de secundaria',
                'Profesor universitario',
                'Asistente educativo',
                'Orientador educativo',
            ],
            'Construcción' => [
                'Albañil',
                'Electricista',
                'Fontanero',
                'Topógrafo',
                'Ingeniero civil',
            ],
        ];

        foreach ($data as $categoryName => $subcategories) {
            $category = Category::create(['name' => $categoryName]);

            foreach ($subcategories as $sub) {
                Subcategory::create([
                    'name' => $sub,
                    'category_id' => $category->id,
                    'img' => 'img/subcategory/default_subcategory.png',
                ]);
            }
        }
    }
}
