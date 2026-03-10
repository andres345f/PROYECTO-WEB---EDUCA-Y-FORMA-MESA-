<?php

namespace Database\Seeders;

use App\Models\CategoriaNivel;
use App\Models\Servicio;
use Illuminate\Database\Seeder;


class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $catalogo = [
            // Base solicitada por el usuario
            ['nivel' => 'Primaria', 'materia' => 'Matemáticas', 'tema' => 'Multiplicación y tablas', 'modalidad' => 'Virtual'],
            ['nivel' => 'Primaria', 'materia' => 'Matemáticas', 'tema' => 'Problemas con divisiones', 'modalidad' => 'Casa'],
            ['nivel' => 'Primaria', 'materia' => 'Lenguaje', 'tema' => 'Comprensión lectora', 'modalidad' => 'Oficina'],
            ['nivel' => 'Primaria', 'materia' => 'Lenguaje', 'tema' => 'Ortografía y acentuación', 'modalidad' => 'Virtual'],
            ['nivel' => 'Primaria', 'materia' => 'Ciencias Naturales', 'tema' => 'Sistema solar y planetas', 'modalidad' => 'Casa'],
            ['nivel' => 'Primaria', 'materia' => 'Ciencias Sociales', 'tema' => 'Mapas y continentes', 'modalidad' => 'Oficina'],
            ['nivel' => 'Primaria', 'materia' => 'Inglés', 'tema' => 'Vocabulario básico', 'modalidad' => 'Virtual'],
            ['nivel' => 'Primaria', 'materia' => 'Música', 'tema' => 'Lectura de notas musicales', 'modalidad' => 'Casa'],
            ['nivel' => 'Primaria', 'materia' => 'Arte', 'tema' => 'Técnicas básicas de dibujo', 'modalidad' => 'Oficina'],
            ['nivel' => 'Primaria', 'materia' => 'Tecnología', 'tema' => 'Uso básico de computadora', 'modalidad' => 'Virtual'],
            ['nivel' => 'Secundaria', 'materia' => 'Matemáticas', 'tema' => 'Álgebra (ecuaciones)', 'modalidad' => 'Oficina'],
            ['nivel' => 'Secundaria', 'materia' => 'Matemáticas', 'tema' => 'Trigonometría', 'modalidad' => 'Virtual'],
            ['nivel' => 'Secundaria', 'materia' => 'Física', 'tema' => 'Movimiento rectilíneo', 'modalidad' => 'Casa'],
            ['nivel' => 'Secundaria', 'materia' => 'Química', 'tema' => 'Balanceo de ecuaciones químicas', 'modalidad' => 'Oficina'],
            ['nivel' => 'Secundaria', 'materia' => 'Biología', 'tema' => 'Célula y funciones', 'modalidad' => 'Virtual'],
            ['nivel' => 'Secundaria', 'materia' => 'Lenguaje', 'tema' => 'Análisis sintáctico', 'modalidad' => 'Casa'],
            ['nivel' => 'Secundaria', 'materia' => 'Historia', 'tema' => 'Independencia de Bolivia', 'modalidad' => 'Oficina'],
            ['nivel' => 'Secundaria', 'materia' => 'Geografía', 'tema' => 'Coordenadas geográficas', 'modalidad' => 'Virtual'],
            ['nivel' => 'Secundaria', 'materia' => 'Inglés', 'tema' => 'Tiempos verbales', 'modalidad' => 'Casa'],
            ['nivel' => 'Secundaria', 'materia' => 'Informática', 'tema' => 'Uso de Excel', 'modalidad' => 'Oficina'],

            // Extras para ampliar catálogo
            ['nivel' => 'Primaria', 'materia' => 'Matemáticas', 'tema' => 'Fracciones y decimales', 'modalidad' => 'Virtual'],
            ['nivel' => 'Primaria', 'materia' => 'Lenguaje', 'tema' => 'Redacción de cuentos cortos', 'modalidad' => 'Oficina'],
            ['nivel' => 'Primaria', 'materia' => 'Ciencias Naturales', 'tema' => 'Ciclo del agua', 'modalidad' => 'Casa'],
            ['nivel' => 'Primaria', 'materia' => 'Inglés', 'tema' => 'Conversación básica', 'modalidad' => 'Híbrido'],
            ['nivel' => 'Secundaria', 'materia' => 'Física', 'tema' => 'Leyes de Newton', 'modalidad' => 'Virtual'],
            ['nivel' => 'Secundaria', 'materia' => 'Química', 'tema' => 'Tabla periódica y enlaces', 'modalidad' => 'Casa'],
            ['nivel' => 'Secundaria', 'materia' => 'Biología', 'tema' => 'Genética básica', 'modalidad' => 'Oficina'],
            ['nivel' => 'Secundaria', 'materia' => 'Historia', 'tema' => 'Guerras mundiales (resumen)', 'modalidad' => 'Virtual'],
            ['nivel' => 'Secundaria', 'materia' => 'Informática', 'tema' => 'Presentaciones efectivas en PowerPoint', 'modalidad' => 'Híbrido'],
            ['nivel' => 'Secundaria', 'materia' => 'Matemáticas', 'tema' => 'Funciones lineales y cuadráticas', 'modalidad' => 'Híbrido'],
        ];

        $modalidadMap = [
            'Virtual' => 'VIRTUAL',
            'Casa' => 'PRESENCIAL',
            'Oficina' => 'PRESENCIAL',
            'Híbrido' => 'HIBRIDO',
        ];

        foreach ($catalogo as $item) {
            $nivel = CategoriaNivel::where('nombre', $item['nivel'])
                ->whereNull('id_categoria_padre')
                ->first();

            if (!$nivel) {
                $this->command->warn("Nivel no encontrado: {$item['nivel']}. Ejecuta primero CategoriaSeeder.");
                continue;
            }

            $materia = CategoriaNivel::where('nombre', $item['materia'])
                ->where('id_categoria_padre', $nivel->id)
                ->first();

            if (!$materia) {
                $this->command->warn("Materia no encontrada para {$item['nivel']}: {$item['materia']}.");
                continue;
            }

            $modalidadApp = $modalidadMap[$item['modalidad']] ?? 'VIRTUAL';

            Servicio::updateOrCreate(
                [
                    'id_categoria' => $materia->id,
                    'nombre' => $item['tema'],
                ],
                [
                    'descripcion' => "Tutoría de {$item['materia']} para {$item['nivel']}. Modalidad sugerida: {$item['modalidad']}.",
                    'modalidad' => $modalidadApp,
                    'estado_activo' => true,
                ]
            );
        }
    }
}
