<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\InformeClase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class InformeClaseController extends Controller
{
    public function index(Request $request)
    {

      
        $id = auth()->user()->tutor->id;    
        $query = InformeClase::with([
            'asistencia.sesion.calendario.servicio:id,nombre',
            'asistencia.inscripcion.alumno.usuario:id,name',
        ])
            ->whereHas('asistencia.inscripcion.calendario', function ($query) use ($id) {
                $query->where('id_tutor', $id);
            })
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('temas_vistos', 'like', "%{$search}%");
        }

        if ($request->filled('desempenio')) {
            $query->where('desempenio', $request->input('desempenio'));
        }


          // dd($query);
        return Inertia::render('InformesClase/Index', [
            'informes' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search', 'desempenio']),
        ]);
    }

   public function create()
{
    $id = auth()->user()->tutor->id;

    $sesiones = Asistencia::with('sesion.calendario.servicio', 'inscripcion.alumno.usuario')
        ->whereHas('sesion.calendario', function ($query) use ($id) {
            $query->where('id_tutor', $id);
        })
        ->whereDoesntHave('informe')
        ->orderByDesc('created_at')
        ->get();

    return Inertia::render('InformesClase/Create', [
        'sesiones' => $sesiones->map(fn($asistencia) => [
            'id' => $asistencia->id,
            'fecha_sesion' => $asistencia->sesion?->fecha_sesion,
            'hora_inicio' => $asistencia->sesion?->hora_inicio,
            'hora_fin' => $asistencia->sesion?->hora_fin,
            'numero_sesion' => $asistencia->sesion?->numero_sesion,
            'servicio_nombre' => $asistencia->sesion?->calendario?->servicio?->nombre,
        ])->values(),
    ]);
}
    public function store(Request $request)
    {

        //dd("llego al store");
        $validated = $request->validate([
            'id_asistencia' => 'required|exists:asistencia,id|unique:informeclase,id_asistencia',
            'temas_vistos' => 'nullable|string',
            'tareas_asignadas' => 'nullable|string',
            'desempenio' => 'nullable|in:BAJO,MEDIO,ALTO,EXCELENTE',
        ], [
            'id_asistencia.required' => 'La asistencia es obligatoria',
            'id_asistencia.exists' => 'La asistencia seleccionada no existe',
            'id_asistencia.unique' => 'Ya existe un informe para esta asistencia',
            'temas_vistos.string' => 'Los temas vistos deben ser una cadena de texto',
            'tareas_asignadas.string' => 'Las tareas asignadas deben ser una cadena de texto',
            'desempenio.in' => 'El desempeño seleccionado no es válido',
        ], [
            'id_asistencia' => 'asistencia',
            'temas_vistos' => 'temas vistos',
            'tareas_asignadas' => 'tareas asignadas',
            'desempenio' => 'desempeño',
        ]);

        InformeClase::create($validated);

        return Redirect::route('informes-clase.index')->with('success', 'Informe de clase creado correctamente.');
    }

    public function edit(InformeClase $informes_clase)
    {
        $sesiones = Asistencia::with('sesion.calendario.servicio', 'inscripcion.alumno.usuario')
            ->where(function ($query) use ($informes_clase) {
                $query->whereDoesntHave('informe')
                    ->orWhere('id', $informes_clase->id_asistencia);
            })
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('InformesClase/Edit', [
            'informe' => $informes_clase,
            'sesiones' => $sesiones->map(fn($asistencia) => [
                'id' => $asistencia->id,
                'fecha_sesion' => $asistencia->sesion?->fecha_sesion,
                'hora_inicio' => $asistencia->sesion?->hora_inicio,
                'hora_fin' => $asistencia->sesion?->hora_fin,
                'numero_sesion' => $asistencia->sesion?->numero_sesion,
                'servicio_nombre' => $asistencia->sesion?->calendario?->servicio?->nombre,
            ])->values(),
        ]);
    }

    public function update(Request $request, InformeClase $informes_clase)
    {
        $validated = $request->validate([
            'id_asistencia' => 'required|exists:asistencia,id|unique:informeclase,id_asistencia,' . $informes_clase->id_informe . ',id_informe',
            'temas_vistos' => 'nullable|string',
            'tareas_asignadas' => 'nullable|string',
            'desempenio' => 'nullable|in:BAJO,MEDIO,ALTO,EXCELENTE',
        ], [
            'id_asistencia.required' => 'La asistencia es obligatoria',
            'id_asistencia.exists' => 'La asistencia seleccionada no existe',
            'id_asistencia.unique' => 'Ya existe un informe para esta asistencia',
            'temas_vistos.string' => 'Los temas vistos deben ser una cadena de texto',
            'tareas_asignadas.string' => 'Las tareas asignadas deben ser una cadena de texto',
            'desempenio.in' => 'El desempeño seleccionado no es válido',
        ], [
            'id_asistencia' => 'asistencia',
            'temas_vistos' => 'temas vistos',
            'tareas_asignadas' => 'tareas asignadas',
            'desempenio' => 'desempeño',
        ]);

        $informes_clase->update($validated);

        return Redirect::route('informes-clase.index')->with('success', 'Informe de clase actualizado correctamente.');
    }

    public function destroy(InformeClase $informes_clase)
    {
        $informes_clase->delete();

        return Redirect::route('informes-clase.index')->with('success', 'Informe de clase eliminado correctamente.');
    }
}
