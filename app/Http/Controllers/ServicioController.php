<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use App\Models\CategoriaNivel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class ServicioController extends Controller
{
    /**
     * 1. INDEX: Muestra la lista de servicios.
     */
    public function index(Request $request)
    {
        $query = Servicio::orderBy('created_at', 'desc');

        // Filtro por búsqueda (nombre)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nombre', 'like', "%{$search}%");
        }

        // Filtro por categoría
        if ($request->filled('id_categoria')) {
            $query->where('id_categoria', $request->input('id_categoria'));
        }

        $servicios = $query->paginate(10);
        $categorias = CategoriaNivel::all();

        return Inertia::render('Servicios/Index', [
            'servicios' => $servicios,
            'categorias' => $categorias,
            'filters' => $request->only(['search', 'id_categoria']),
        ]);
    }

    /**
     * 2. CREATE: Muestra el formulario de creación.
     */
    public function create()
    {
        // Necesitamos las categorías para llenar el <select> del formulario
        $categorias = CategoriaNivel::all();

        return Inertia::render('Servicios/Create', [
            'categorias' => $categorias
        ]);
    }

    /**
     * 3. STORE: Guarda el nuevo servicio en la BD.
     */
    public function store(Request $request)
    {
        // A. Validación
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'id_categoria' => 'required|exists:categorianivel,id', // Valida que la FK exista

            'modalidad' => 'required|in:VIRTUAL,PRESENCIAL,HIBRIDO',
            'descripcion' => 'nullable|string',
        ], [
            'nombre.required' => 'El nombre del servicio es obligatorio',
            'nombre.string' => 'El nombre del servicio debe ser una cadena de texto',
            'nombre.max' => 'El nombre del servicio no puede tener más de 150 caracteres',
            'id_categoria.required' => 'La categoría del servicio es obligatoria',
            'id_categoria.exists' => 'La categoría seleccionada no existe',
            'modalidad.required' => 'La modalidad del servicio es obligatoria',
            'modalidad.in' => 'La modalidad seleccionada no es válida',
            'descripcion.string' => 'La descripción debe ser una cadena de texto',
        ], [
            'nombre' => 'nombre',
            'id_categoria' => 'categoría',
            'modalidad' => 'modalidad',
            'descripcion' => 'descripción',
        ]);

        // B. Creación
        Servicio::create($validated);

        // C. Redirección con Mensaje Flash
        // Inertia intercepta esto y lo pasa al frontend sin recargar
        return Redirect::route('servicios.index')->with('success', 'Servicio creado correctamente.');
    }

    /**
     * 4. EDIT: Muestra el formulario de edición con datos cargados.
     */
    public function edit(Servicio $servicio)
    {
        // Traemos las categorías para el select
        $categorias = CategoriaNivel::all();

        return Inertia::render('Servicios/Edit', [
            'servicio' => $servicio,
            'categorias' => $categorias
        ]);
    }

    /**
     * 5. UPDATE: Actualiza el servicio existente.
     */
    public function update(Request $request, Servicio $servicio)
    {
        // Validación similar al store
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'id_categoria' => 'required|exists:categorianivel,id',
            'modalidad' => 'required|in:VIRTUAL,PRESENCIAL,HIBRIDO',
            'descripcion' => 'nullable|string',

        ], [
            'nombre.required' => 'El nombre del servicio es obligatorio',
            'nombre.string' => 'El nombre del servicio debe ser una cadena de texto',
            'nombre.max' => 'El nombre del servicio no puede tener más de 150 caracteres',
            'id_categoria.required' => 'La categoría del servicio es obligatoria',
            'id_categoria.exists' => 'La categoría seleccionada no existe',
            'modalidad.required' => 'La modalidad del servicio es obligatoria',
            'modalidad.in' => 'La modalidad seleccionada no es válida',
            'descripcion.string' => 'La descripción debe ser una cadena de texto',
        ], [
            'nombre' => 'nombre',
            'id_categoria' => 'categoría',
            'modalidad' => 'modalidad',
            'descripcion' => 'descripción',
        ]);

        $servicio->update($validated);

        return Redirect::route('servicios.index')->with('success', 'Servicio actualizado correctamente.');
    }

    /**
     * 6. DESTROY: Elimina (Soft o Hard delete) el servicio.
     */
    public function destroy(Servicio $servicio)
    {
        // Opcional: Validar si tiene calendarios abiertos con inscripciones antes de borrar
        if ($servicio->calendarios()->first() && $servicio->calendarios()->first()->inscripciones()->exists()) {
            return Redirect::back()->with('error', 'No puedes eliminar un servicio con alumnos inscritos.');
        }

        $servicio->delete();

        return Redirect::route('servicios.index')->with('success', 'Servicio eliminado correctamente.');
    }
}