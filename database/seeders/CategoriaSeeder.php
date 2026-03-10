<?php


namespace Database\Seeders;

use App\Models\CategoriaNivel;
use Illuminate\Database\Seeder;


class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estructura = [
            'Primaria' => [
                'Matemáticas',
                'Lenguaje',
                'Ciencias Naturales',
                'Ciencias Sociales',
                'Inglés',
                'Música',
                'Arte',
                'Tecnología',
            ],
            'Secundaria' => [
                'Matemáticas',
                'Física',
                'Química',
                'Biología',
                'Lenguaje',
                'Historia',
                'Geografía',
                'Inglés',
                'Informática',
                'Literatura',
                'Filosofía',
            ],
        ];

        foreach ($estructura as $nivel => $materias) {
            $nivelCategoria = CategoriaNivel::updateOrCreate(
                [
                    'nombre' => $nivel,
                    'id_categoria_padre' => null,
                ],
                [
                    'estado' => true,
                ]
            );

            foreach ($materias as $materia) {
                CategoriaNivel::updateOrCreate(
                    [
                        'nombre' => $materia,
                        'id_categoria_padre' => $nivelCategoria->id,
                    ],
                    [
                        'estado' => true,
                    ]
                );
            }
        }
    }
}