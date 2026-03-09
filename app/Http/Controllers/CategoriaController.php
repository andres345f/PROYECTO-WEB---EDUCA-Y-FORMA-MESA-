<?php

namespace App\Http\Controllers;

use App\Models\CategoriaNivel;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $query = CategoriaNivel::orderBy('created_at', 'desc');

        // Filtro por búsqueda (nombre)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nombre', 'like', "%{$search}%");
        }

        // Filtro por categoría padre
        if ($request->filled('id_categoria_padre')) {
            $query->where('id_categoria_padre', $request->input('id_categoria_padre'));
        }

        $categoriasp = $query->paginate(10);
        $categorias = CategoriaNivel::all(); // Para el filtro de categoría padre en la vista 
        return Inertia::render('Categorias/Index', [
            'categoriasp' => $categoriasp,
            'categorias' => $categorias,
            'filters' => $request->only(['search', 'id_categoria_padre']),
        ]);
    }

    /**
     * 2. CREATE: Muestra el formulario de creación.
     */
    public function create()
    {
        // Necesitamos las categorías para llenar el <select> del formulario
        $categorias = CategoriaNivel::all();

        return Inertia::render('Categorias/Create', [
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
            'nombre' => 'required|string|max:100',
            'id_categoria_padre' => 'nullable|exists:categorianivel,id',
        ] ,[
            'nombre.required' => 'El nombre de la categoría es obligatorio',
            'nombre.string' => 'El nombre de la categoría debe ser una cadena de texto',
            'nombre.max' => 'El nombre de la categoría no puede tener más de 100 caracteres',
            'id_categoria_padre.exists' => 'La categoría padre seleccionada no existe',
        ]);
        // B. Creación
        CategoriaNivel::create($validated);
        // C. Redirección con Mensaje Flash
        // Inertia intercepta esto y lo pasa al frontend sin recargar
        return Redirect::route('categorias.index')->with('success', 'Categoría creada correctamente.');
    }

    /**
     * 4. EDIT: Muestra el formulario de edición con datos cargados.
     */
    public function edit(CategoriaNivel $categoria)
    {
        // Traemos las categorías para el select
        $categorias = CategoriaNivel::all();
        return Inertia::render('Categorias/Edit', [
            'categoria' => $categoria,
            'categorias' => $categorias
        ]);
    }

    /**
     * 5. UPDATE: Actualiza el servicio existente.
     */
    public function update(Request $request, CategoriaNivel $categoria)
    {
        // Validación similar al store
            $validated = $request->validate([
                'nombre' => 'required|string|max:100',
                'id_categoria_padre' => 'nullable|exists:categorianivel,id',
            ], [
                'nombre.required' => 'El nombre de la categoría es obligatorio',
                'nombre.string' => 'El nombre de la categoría debe ser una cadena de texto',
                'nombre.max' => 'El nombre de la categoría no puede tener más de 100 caracteres',
                'id_categoria_padre.exists' => 'La categoría padre seleccionada no existe',
            ], [
                'nombre' => 'nombre de la categoría',
                'id_categoria_padre' => 'categoría padre',
            ]);
        $categoria->update($validated);
        return Redirect::route('categorias.index')->with('success', 'Categoría actualizada correctamente.');
    }

    /**
     * 6. DESTROY: Elimina (Soft o Hard delete) el servicio.
     */
    public function destroy(CategoriaNivel $categoria)
    {

        // Desactiva la categoría y sus hijos
        $categoriaFind = CategoriaNivel::find($categoria->id);
        if ($categoriaFind->estado == false) {
            $categoriaFind->update(['estado' => true]);
            $categoriaFind->hijos()->update(['estado' => true]);
            $categoriaFind->servicios()->update(['estado_activo' => true]);
        } else {
            //dd('llega por aca');
            $categoriaFind->update(['estado' => false]);
            $categoriaFind->hijos()->update(['estado' => false]);
            $categoriaFind->servicios()->update(['estado_activo' => false]);
        }
        return Redirect::route('categorias.index')->with('success', 'Categoría actualizada correctamente.');
    }
}