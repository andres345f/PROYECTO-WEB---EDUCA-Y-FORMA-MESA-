<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use App\Models\CategoriaNivel;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class TutorController extends Controller
{
    
    public function index()
    {
        $servicios = Tutor::orderBy('created_at', 'desc')
            ->paginate(10);
        // Retornamos la vista de Inertia con los datos como "props"
        return Inertia::render('Tutores/Index', [
            'tutores' => $servicios
        ]);
    }

    /**
     * 2. CREATE: Muestra el formulario de creación.
     */
    public function create()
    {
        // Necesitamos las categorías para llenar el <select> del formulario
        $tutores = Tutor::all();
        return Inertia::render('Tutores/Create', [
            'tutores' => $tutores
        ]);
    }

    /**
     * 3. STORE: Guarda el nuevo tutor en la BD.
     */
    public function store(Request $request)
    {
        // A. Validación
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'id_categoria' => 'required|exists:CategoriaNivel,id', // Valida que la FK exista
            'costo_base' => 'required|numeric|min:0',
            'modalidad' => 'required|in:VIRTUAL,PRESENCIAL,HIBRIDO',
            'descripcion' => 'nullable|string',
            'duracion_semanas' => 'nullable|integer',
            'duracion_horas' => 'nullable|integer',
        ], [
            'nombre.required' => 'El nombre del tutor es obligatorio',
            'nombre.string' => 'El nombre del tutor debe ser una cadena de texto',
            'nombre.max' => 'El nombre del tutor no puede tener más de 150 caracteres',
            'id_categoria.required' => 'La categoría es obligatoria',
            'id_categoria.exists' => 'La categoría seleccionada no existe',
            'costo_base.required' => 'El costo base es obligatorio',
            'costo_base.numeric' => 'El costo base debe ser un número válido',
            'costo_base.min' => 'El costo base no puede ser negativo',
            'modalidad.required' => 'La modalidad es obligatoria',
            'modalidad.in' => 'La modalidad seleccionada no es válida',
            'descripcion.string' => 'La descripción debe ser una cadena de texto',
            'duracion_semanas.integer' => 'La duración en semanas debe ser un número entero',
            'duracion_horas.integer' => 'La duración en horas debe ser un número entero',
        ], [
            'nombre' => 'nombre',
            'id_categoria' => 'categoría',
            'costo_base' => 'costo base',
            'modalidad' => 'modalidad',
            'descripcion' => 'descripción',
            'duracion_semanas' => 'duración en semanas',
            'duracion_horas' => 'duración en horas',
        ]);
        // B. Creación
        Tutor::create($validated);
        // C. Redirección con Mensaje Flash
        // Inertia intercepta esto y lo pasa al frontend sin recargar
        return Redirect::route('tutores.index')->with('success', 'Tutor creado correctamente.');
    }

    /**
     * 4. EDIT: Muestra el formulario de edición con datos cargados.
     */
    public function edit(Tutor $tutor)
    {
        // Traemos las categorías para el select
        $categorias = CategoriaNivel::all();
        return Inertia::render('Tutores/Edit', [
            'tutor' => $tutor,
            'categorias' => $categorias
        ]);
    }

    /**
     * 5. UPDATE: Actualiza el tutor existente.
     */
    public function update(Request $request, Tutor $tutor)
    {
        // Validación similar al store
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'id_categoria' => 'required|exists:CategoriaNivel,id',
            'costo_base' => 'required|numeric|min:0',
            'modalidad' => 'required|in:VIRTUAL,PRESENCIAL,HIBRIDO',
            'estado_activo' => 'boolean', // Campo extra que suele editarse
            'descripcion' => 'nullable|string',
        ], [
            'nombre.required' => 'El nombre del tutor es obligatorio',
            'nombre.string' => 'El nombre del tutor debe ser una cadena de texto',
            'nombre.max' => 'El nombre del tutor no puede tener más de 150 caracteres',
            'id_categoria.required' => 'La categoría es obligatoria',
            'id_categoria.exists' => 'La categoría seleccionada no existe',
            'costo_base.required' => 'El costo base es obligatorio',
            'costo_base.numeric' => 'El costo base debe ser un número válido',
            'costo_base.min' => 'El costo base no puede ser negativo',
            'modalidad.required' => 'La modalidad es obligatoria',
            'modalidad.in' => 'La modalidad seleccionada no es válida',
            'estado_activo.boolean' => 'El estado activo debe ser verdadero o falso',
            'descripcion.string' => 'La descripción debe ser una cadena de texto',
        ], [
            'nombre' => 'nombre',
            'id_categoria' => 'categoría',
            'costo_base' => 'costo base',
            'modalidad' => 'modalidad',
            'estado_activo' => 'estado activo',
            'descripcion' => 'descripción',
        ]);

        $tutor->update($validated);
        return Redirect::route('tutores.index')->with('success', 'Tutor actualizado correctamente.');
    }

    /**
     * 6. DESTROY: Elimina (Soft o Hard delete) el tutor.
     */
    public function destroy(Tutor $tutor)
    {
        // Opcional: Validar si tiene inscripciones activas antes de borrar
        if ($tutor->inscripciones()->exists()) {
            return Redirect::back()->with('error', 'No puedes eliminar un tutor con alumnos inscritos.');
        }
        $tutor->delete();

        return Redirect::route('tutores.index')->with('success', 'Tutor eliminado.');
    }
}