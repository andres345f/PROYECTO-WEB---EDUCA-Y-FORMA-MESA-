<?php

namespace App\Http\Controllers;

use App\Models\Calendario;
use App\Models\Disponibilidad;
use App\Models\SesionProgramada;
use App\Models\Servicio;
use App\Models\Tutor;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;

class CalendarioController extends Controller
{
    public function index(Request $request)
    {

         $authUser = auth()->user();
          $query = Calendario::with(['servicio', 'tutor.usuario', 'disponibilidades','inscripciones'])
            ->where('id_tutor', $authUser->tutor->id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('servicio', function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%");
            });
        }

        if ($request->filled('id_servicio')) {
            $query->where('id_servicio', $request->input('id_servicio'));
        }

        if ($request->filled('tipo_programacion')) {
            $query->where('tipo_programacion', $request->input('tipo_programacion'));
        }
       
        $calendarios = $query->paginate(10)->withQueryString();
        //dd($calendarios);
        $servicios = Servicio::all();
        $tutores = Tutor::with('usuario:id,name')->get();
        return Inertia::render('Calendarios/Index', [
            'calendarios' => $calendarios,
            'servicios' => $servicios,
            'tutores' => $tutores,
            'filters' => $request->only(['search', 'id_servicio', 'id_tutor', 'tipo_programacion']),
        ]);
    }

    public function create()
    {
        $servicios = Servicio::all();
        $tutores = Tutor::with('usuario:id,name')->get();

        return Inertia::render('Calendarios/Create', [
            'servicios' => $servicios,
            'tutores' => $tutores,
        ]);
    }

    public function store(Request $request)
    {

        $authUser = auth()->user();
        $tutorId = $authUser->tutor->id;
        $validated = $request->validate([
            'id_servicio' => 'required|exists:servicio,id',
            'tipo_programacion' => 'required|in:CITA_LIBRE,PAQUETE_FIJO',
            'fecha_inicio' => 'nullable|date|required_if:tipo_programacion,PAQUETE_FIJO',
            'numero_sesiones' => 'nullable|integer|min:1|required_if:tipo_programacion,PAQUETE_FIJO',
            'duracion_sesion_minutos' => 'required|integer|min:15',
            'costo_total' => 'required|numeric|min:0',
            'cupos_maximos' => 'required|integer|min:1',
            'disponibilidades' => 'required|array|min:1',
            'disponibilidades.*.dia_semana' => 'required|in:LUNES,MARTES,MIERCOLES,JUEVES,VIERNES,SABADO,DOMINGO',
            'disponibilidades.*.hora_apertura' => 'required|date_format:H:i',
            'disponibilidades.*.hora_cierre' => 'required|date_format:H:i|after:disponibilidades.*.hora_apertura',
        ], [
            'id_servicio.required' => 'El campo servicio es obligatorio.',
            'costo_total.required' => 'El campo costo total es obligatorio.',
            'duracion_sesion_minutos.required' => 'El campo duración de sesión es obligatorio.',
        
            'disponibilidades.required' => 'Debes registrar al menos una disponibilidad.',
            'disponibilidades.min' => 'Debes registrar al menos una disponibilidad.',
            'numero_sesiones.required_if' => 'El número de sesiones es obligatorio para paquetes fijos.',
            
        ]);

        if ($this->tieneCruceDisponibilidadTutor($tutorId, $validated['disponibilidades'])) {
            return Redirect::back()
                ->withErrors(['disponibilidades' => 'El tutor tiene conflicto de disponibilidad en uno o más rangos.'])
                ->withInput();
        }

        DB::transaction(function () use ($validated, $tutorId) {
            $isPaquete = $validated['tipo_programacion'] === 'PAQUETE_FIJO';

            $calendario = Calendario::create([
                'id_servicio' => $validated['id_servicio'],
                'id_tutor' => $tutorId,
                'tipo_programacion' => $validated['tipo_programacion'],
                'fecha_inicio' => $isPaquete ? $validated['fecha_inicio'] : null,
                'numero_sesiones' => $isPaquete ? $validated['numero_sesiones'] : null,
                'duracion_sesion_minutos' => $validated['duracion_sesion_minutos'],
                'costo_total' => $validated['costo_total'],
                'cupos_maximos' => $validated['cupos_maximos'],
            ]);
            $rows = collect($validated['disponibilidades'])
                ->map(fn ($item) => [
                    'id_calendario' => $calendario->id,
                    'dia_semana' => $item['dia_semana'],
                    'hora_apertura' => $item['hora_apertura'],
                    'hora_cierre' => $item['hora_cierre'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();
            Disponibilidad::insert($rows);

            if ($isPaquete) {
                $this->sincronizarSesionesPaqueteFijo($calendario, $validated['disponibilidades']);
            }
        });
        return Redirect::route('calendarios.index')->with('success', 'Calendario creado correctamente.');
    }

    public function edit(Calendario $calendario)
    {
       
        $calendario->load(['disponibilidades']);
        $servicios = Servicio::all();
        $tutores = Tutor::with('usuario:id,name')->get();

        return Inertia::render('Calendarios/Edit', [
            'calendario' => $calendario,
            'servicios' => $servicios,
            'tutores' => $tutores,
        ]);
    }

    public function update(Request $request, Calendario $calendario)
    {
        $authUser = auth()->user();
        $id_tutor = $authUser->tutor->id;
        $validated = $request->validate([
            'id_servicio' => 'required|exists:servicio,id',
            'tipo_programacion' => 'required|in:CITA_LIBRE,PAQUETE_FIJO',
            'fecha_inicio' => 'nullable|date|required_if:tipo_programacion,PAQUETE_FIJO',
            'numero_sesiones' => 'nullable|integer|min:1|required_if:tipo_programacion,PAQUETE_FIJO',
            'duracion_sesion_minutos' => 'required|integer|min:15',
            'costo_total' => 'required|numeric|min:0',
            'cupos_maximos' => 'required|integer|min:1',
            'disponibilidades' => 'required|array|min:1',
            'disponibilidades.*.dia_semana' => 'required|in:LUNES,MARTES,MIERCOLES,JUEVES,VIERNES,SABADO,DOMINGO',
            'disponibilidades.*.hora_apertura' => 'required|date_format:H:i',
            'disponibilidades.*.hora_cierre' => 'required|date_format:H:i|after:disponibilidades.*.hora_apertura',
        ], [
            'disponibilidades.required' => 'Debes registrar al menos una disponibilidad.',
            'disponibilidades.min' => 'Debes registrar al menos una disponibilidad.',
            'numero_sesiones.required_if' => 'El número de sesiones es obligatorio para paquetes fijos.',
        ], [
            'id_servicio' => 'servicio',
            'tipo_programacion' => 'tipo de programación',
            'fecha_inicio' => 'fecha de inicio',
            'numero_sesiones' => 'número de sesiones',
            'duracion_sesion_minutos' => 'duración de la sesión (minutos)',
            'costo_total' => 'costo total',
            'cupos_maximos' => 'cupos máximos',
            'disponibilidades' => 'disponibilidades',
            'disponibilidades.*.dia_semana' => 'día de la semana',
            'disponibilidades.*.hora_apertura' => 'hora de apertura',
            'disponibilidades.*.hora_cierre' => 'hora de cierre',
        ]);

        if ($this->tieneCruceDisponibilidadTutor($id_tutor, $validated['disponibilidades'], $calendario->id)) {
            return Redirect::back()
                ->withErrors(['disponibilidades' => 'El tutor tiene conflicto de disponibilidad en uno o más rangos.'])
                ->withInput();
        }

        DB::transaction(function () use ($validated, $calendario, $id_tutor) {
            $isPaquete = $validated['tipo_programacion'] === 'PAQUETE_FIJO';

            $calendario->update([
                'id_servicio' => $validated['id_servicio'],
                'id_tutor' => $id_tutor,
                'tipo_programacion' => $validated['tipo_programacion'],
                'fecha_inicio' => $isPaquete ? $validated['fecha_inicio'] : null,
                'numero_sesiones' => $isPaquete ? $validated['numero_sesiones'] : null,
                'duracion_sesion_minutos' => $validated['duracion_sesion_minutos'],
                'costo_total' => $validated['costo_total'],
                'cupos_maximos' => $validated['cupos_maximos'],
            ]);

            $calendario->disponibilidades()->delete();
            $rows = collect($validated['disponibilidades'])
                ->map(fn ($item) => [
                    'id_calendario' => $calendario->id,
                    'dia_semana' => $item['dia_semana'],
                    'hora_apertura' => $item['hora_apertura'],
                    'hora_cierre' => $item['hora_cierre'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

            Disponibilidad::insert($rows);

            if ($isPaquete) {
                $this->sincronizarSesionesPaqueteFijo($calendario, $validated['disponibilidades']);
            }
        });

        return Redirect::route('calendarios.index')->with('success', 'Calendario actualizado correctamente.');
    }

    public function destroy(Calendario $calendario)
    {
        if ($calendario->inscripciones()->exists()) {
            return Redirect::back()
                ->with('error', 'No puedes eliminar un calendario con inscripciones activas.');
        }

        $calendario->delete();

        return Redirect::route('calendarios.index')->with('success', 'Calendario eliminado correctamente.');
    }

    private function tieneCruceDisponibilidadTutor(int $idTutor, array $nuevasDisponibilidades, ?int $calendarioId = null): bool
    {
        $query = Calendario::where('id_tutor', $idTutor)->with('disponibilidades');

        if ($calendarioId) {
            $query->where('id', '!=', $calendarioId);
        }

        $otrosCalendarios = $query->get();

        foreach ($nuevasDisponibilidades as $nueva) {
            $nuevoInicio = $nueva['hora_apertura'];
            $nuevoFin = $nueva['hora_cierre'];
            $nuevoDia = $nueva['dia_semana'];

            foreach ($otrosCalendarios as $calendario) {
                foreach ($calendario->disponibilidades as $existente) {
                    if ($existente->dia_semana !== $nuevoDia) {
                        continue;
                    }

                    $existeCruce = $nuevoInicio < $existente->hora_cierre
                        && $nuevoFin > $existente->hora_apertura;

                    if ($existeCruce) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function sincronizarSesionesPaqueteFijo(Calendario $calendario, array $disponibilidades): void
    {
        $sesiones = $this->generarSesionesPaqueteFijo($calendario, $disponibilidades);

        if (empty($sesiones)) {
            return;
        }

        if (!$calendario->inscripciones()->exists()) {
            SesionProgramada::where('id_calendario', $calendario->id)->delete();
        }

        $existentes = SesionProgramada::where('id_calendario', $calendario->id)->pluck('numero_sesion')->all();

        $nuevas = collect($sesiones)
            ->filter(fn ($item) => !in_array($item['numero_sesion'], $existentes, true))
            ->map(fn ($item) => [
                'id_calendario' => $calendario->id,
                'fecha_sesion' => $item['fecha_sesion'],
                'hora_inicio' => $item['hora_inicio'],
                'hora_fin' => $item['hora_fin'],
                'link_sesion' => null,
                'numero_sesion' => $item['numero_sesion'],
                'created_at' => now(),
                'updated_at' => now(),
            ])->values()->all();

        if (!empty($nuevas)) {
            SesionProgramada::insert($nuevas);
        }
    }

    private function generarSesionesPaqueteFijo(Calendario $calendario, array $disponibilidades): array
    {
        if (!$calendario->fecha_inicio) {
            return [];
        }

        $bloques = collect($disponibilidades)
            ->sortBy(fn ($item) => $this->ordenDia($item['dia_semana']) * 10000 + (int) str_replace(':', '', $item['hora_apertura']))
            ->values();

        if ($bloques->isEmpty()) {
            return [];
        }

        $totalSesiones = max(1, (int) ($calendario->numero_sesiones ?? 1));
        $cursor = Carbon::parse($calendario->fecha_inicio)->startOfDay();
        $sesiones = [];
        $maxDiasBusqueda = 730;

        while (count($sesiones) < $totalSesiones && $maxDiasBusqueda > 0) {
            $dia = $this->nombreDiaPorNumero($cursor->dayOfWeekIso);
            $bloque = $bloques->firstWhere('dia_semana', $dia);

            if ($bloque) {
                $inicio = Carbon::parse($cursor->format('Y-m-d') . ' ' . $bloque['hora_apertura']);
                $finDisponibilidad = Carbon::parse($cursor->format('Y-m-d') . ' ' . $bloque['hora_cierre']);
                $fin = $inicio->copy()->addMinutes((int) $calendario->duracion_sesion_minutos);

                if ($fin->greaterThan($finDisponibilidad)) {
                    $fin = $finDisponibilidad;
                }

                if ($fin->greaterThan($inicio)) {
                    $sesiones[] = [
                        'numero_sesion' => count($sesiones) + 1,
                        'fecha_sesion' => $cursor->toDateString(),
                        'hora_inicio' => $inicio->format('H:i'),
                        'hora_fin' => $fin->format('H:i'),
                    ];
                }
            }

            $cursor->addDay();
            $maxDiasBusqueda--;
        }

        return $sesiones;
    }

    private function ordenDia(string $dia): int
    {
        return match ($dia) {
            'LUNES' => 1,
            'MARTES' => 2,
            'MIERCOLES' => 3,
            'JUEVES' => 4,
            'VIERNES' => 5,
            'SABADO' => 6,
            'DOMINGO' => 7,
            default => 8,
        };
    }

    private function nombreDiaPorNumero(int $numero): string
    {
        return match ($numero) {
            1 => 'LUNES',
            2 => 'MARTES',
            3 => 'MIERCOLES',
            4 => 'JUEVES',
            5 => 'VIERNES',
            6 => 'SABADO',
            7 => 'DOMINGO',
            default => 'LUNES',
        };
    }
}
