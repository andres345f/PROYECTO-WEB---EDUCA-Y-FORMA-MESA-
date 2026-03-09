<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Calendario;
use App\Models\Licencia;
use App\Models\Servicio;
use App\Models\SesionProgramada;
use App\Models\Tutor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LicenciaController extends Controller
{
    // ─────────────────────────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $auth = $request->user();
        $modo = $this->resolverModo($request);

        $base = Licencia::with([
            'asistencia.sesion.calendario.servicio:id,nombre',
            'asistencia.sesion.calendario.tutor.usuario:id,name',
            'asistencia.inscripcion.alumno.usuario:id,name',
        ])->orderByDesc('created_at');

        if ($modo === 'alumno') {
            $base->whereHas(
                'asistencia.inscripcion',
                fn($q) =>
                $q->where('id_alumno', $auth->alumno->id)
            );
            $this->applyFiltrosBasicos($base, $request);

            return Inertia::render('Licencias/Index', [
                'licencias' => $base->paginate(10)->withQueryString(),
                'filters' => $request->only(['search', 'estado_aprobacion']),
                'modo' => 'alumno',
            ]);
        }

        if ($modo === 'tutor') {
            $base->whereHas(
                'asistencia.inscripcion.calendario',
                fn($q) =>
                $q->where('id_tutor', $auth->tutor->id)
            );
            $this->applyFiltrosBasicos($base, $request);

            return Inertia::render('Licencias/Index', [
                'licencias' => $base->paginate(10)->withQueryString(),
                'filters' => $request->only(['search', 'estado_aprobacion', 'fecha_desde', 'fecha_hasta']),
                'modo' => 'tutor',
            ]);
        }


        // Propietario / admin: todos con filtros ricos
        $this->applyFiltrosBasicos($base, $request);
        $this->applyFiltrosAdmin($base, $request);

        $tutores = Tutor::with('usuario:id,name')->get()
            ->map(fn($t) => ['id' => $t->id, 'nombre' => $t->usuario?->name ?? '—']);
        $servicios = Servicio::select('id', 'nombre')->orderBy('nombre')->get();

        return Inertia::render('Licencias/Index', [
            'licencias' => $base->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'estado_aprobacion', 'fecha_desde', 'fecha_hasta', 'id_tutor', 'id_servicio']),
            'modo' => 'admin',
            'tutores' => $tutores,
            'servicios' => $servicios,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // CREATE
    // ─────────────────────────────────────────────────────────────────

    public function create()
    {
        $auth = request()->user();

        // Solo los alumnos pueden solicitar licencias
        $query = Asistencia::with('sesion.calendario.servicio', 'inscripcion.alumno.usuario')
            ->whereDoesntHave('licencia');

        if ($auth?->is_alumno) {
            $query->whereHas(
                'inscripcion',
                fn($q) =>
                $q->where('id_alumno', $auth->alumno->id)
            );
        }

        $sesiones = $query->orderByDesc('created_at')->get();

        return Inertia::render('Licencias/Create', [
            'sesiones' => $sesiones->map(fn($a) => [
                'id' => $a->id,
                'fecha_sesion' => $a->sesion?->fecha_sesion,
                'hora_inicio' => $a->sesion?->hora_inicio,
                'hora_fin' => $a->sesion?->hora_fin,
                'numero_sesion' => $a->sesion?->numero_sesion,
                'servicio_nombre' => $a->sesion?->calendario?->servicio?->nombre,
            ])->values(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // STORE
    // ─────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_asistencia' => 'required|exists:asistencia,id|unique:licencia,id_asistencia',
            'motivo' => 'required|string',
            'evidencia_url' => 'nullable|image|max:4096',
        ], [
            'id_asistencia.required' => 'La asistencia es obligatoria',
            'id_asistencia.exists' => 'La asistencia seleccionada no existe',
            'id_asistencia.unique' => 'Ya existe una licencia para esta asistencia',
            'motivo.required' => 'El motivo de la licencia es obligatorio',
            'motivo.string' => 'El motivo debe ser una cadena de texto',
            'evidencia_url.image' => 'La evidencia debe ser una imagen válida',
            'evidencia_url.max' => 'La evidencia no puede superar los 4 MB',
        ], [
            'id_asistencia' => 'asistencia',
            'motivo' => 'motivo',
            'evidencia_url' => 'evidencia',
        ]);

        $evidenciaPath = null;
        if ($request->hasFile('evidencia_url')) {
            $evidenciaPath = $request->file('evidencia_url')->store('licencias', 'public');
        }

        // Estado y observacion los controla únicamente el tutor/admin
        Licencia::create(array_merge($validated, [
            'evidencia_url' => $evidenciaPath,
            'estado_aprobacion' => 'PENDIENTE',
            'observacion_admin' => null,
            'fecha_solicitud' => now()->toDateString(),
        ]));

        return Redirect::route('licencias.index')->with('success', 'Licencia enviada correctamente.');
    }

    // ─────────────────────────────────────────────────────────────────
    // EDIT
    // ─────────────────────────────────────────────────────────────────

    public function edit(Licencia $licencia)
    {
        $auth = request()->user();
        $modo = $this->resolverModo(request());

        $licencia->load(['asistencia.sesion.calendario.disponibilidades']);

        $sesiones = Asistencia::with('sesion.calendario.servicio', 'inscripcion.alumno.usuario')
            ->where(fn($q) => $q->whereDoesntHave('licencia')->orWhere('id', $licencia->id_asistencia))
            ->orderByDesc('created_at')
            ->get();

        // Calcular fecha sugerida de reprogramación para tutores/admin
        $fechaSugerida = null;
        if (!$auth?->is_alumno) {
            $sesionOriginal = $licencia->asistencia->sesion;
            if ($sesionOriginal) {
                $slot = $this->buscarProximaSesionDisponible(
                    $sesionOriginal->calendario,
                    Carbon::parse($sesionOriginal->fecha_sesion),
                    $sesionOriginal->id,
                );
                $fechaSugerida = $slot['fecha_sesion'] ?? null;
            }
        }
        $licencia->evidencia_url = asset('storage/'.$licencia->evidencia_url);

        return Inertia::render('Licencias/Edit', [
            'licencia' => $licencia,
            'sesiones' => $sesiones->map(fn($a) => [
                'id' => $a->id,
                'fecha_sesion' => $a->sesion?->fecha_sesion,
                'hora_inicio' => $a->sesion?->hora_inicio,
                'hora_fin' => $a->sesion?->hora_fin,
                'numero_sesion' => $a->sesion?->numero_sesion,
                'servicio_nombre' => $a->sesion?->calendario?->servicio?->nombre,
            ])->values(),
            'fechaSugerida' => $fechaSugerida,
            'modo' => $modo,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // UPDATE
    // ─────────────────────────────────────────────────────────────────

    public function update(Request $request, Licencia $licencia)
    {
        $auth = $request->user();
        $modo = $this->resolverModo($request);

        if ($modo !== 'alumno' && $licencia->estado_aprobacion === 'APROBADA') {
            return Redirect::back()->withErrors([
                'licencia' => 'No puedes editar una licencia que ya fue aprobada.',
            ]);
        }

        // ── Alumno: solo puede editar motivo / evidencia (si sigue PENDIENTE) ──
        if ($modo === 'alumno') {
            if ($licencia->estado_aprobacion !== 'PENDIENTE') {
                return Redirect::back()->withErrors([
                    'licencia' => 'Solo puedes editar la licencia mientras esté en estado PENDIENTE.',
                ]);
            }
            
           // Verificar qué datos llegan en la solicitud de actualización del alumno
           $validated = $request->validate([
                'id_asistencia' => 'required|exists:asistencia,id|unique:licencia,id_asistencia,' . $licencia->id_licencia . ',id_licencia',
                'motivo' => 'required|string',
                'evidencia_url' => 'nullable|image|max:4096',
            ], [
                'id_asistencia.required' => 'La asistencia es obligatoria',
                'id_asistencia.exists' => 'La asistencia seleccionada no existe',
                'id_asistencia.unique' => 'Ya existe una licencia para esta asistencia',
                'motivo.required' => 'El motivo de la licencia es obligatorio',
                'motivo.string' => 'El motivo debe ser una cadena de texto',
                'evidencia_url.image' => 'La evidencia debe ser una imagen válida',
                'evidencia_url.max' => 'La evidencia no puede superar los 4 MB',
            ], [
                'id_asistencia' => 'asistencia',
                'motivo' => 'motivo',
                'evidencia_url' => 'evidencia',
            ]);
             
            $payload = [
                'id_asistencia' => $validated['id_asistencia'],
                'motivo' => $validated['motivo'],
            ];

            if ($request->hasFile('evidencia_url')) {
                if ($licencia->evidencia_url) {
                    Storage::disk('public')->delete($licencia->evidencia_url);
                }
                $payload['evidencia_url'] = $request->file('evidencia_url')->store('licencias', 'public');
            }

            $licencia->update($payload);
            return Redirect::route('licencias.index')->with('success', 'Licencia actualizada.');
        }

        // ── Tutor / Admin: pueden cambiar estado + observacion + reprogramar ──
        $validated = $request->validate([
            'estado_aprobacion' => 'required|in:PENDIENTE,APROBADA,RECHAZADA',
            'observacion_admin' => 'nullable|string',
            'fecha_reprogramacion' => 'nullable|date|after_or_equal:today',
        ], [
            'estado_aprobacion.required' => 'El estado de aprobación es obligatorio',
            'estado_aprobacion.in' => 'El estado de aprobación seleccionado no es válido',
            'observacion_admin.string' => 'La observación debe ser una cadena de texto',
            'fecha_reprogramacion.date' => 'La fecha de reprogramación debe ser una fecha válida',
            'fecha_reprogramacion.after_or_equal' => 'La fecha de reprogramación debe ser hoy o una fecha futura',
        ], [
            'estado_aprobacion' => 'estado de aprobación',
            'observacion_admin' => 'observación del administrador',
            'fecha_reprogramacion' => 'fecha de reprogramación',
        ]);

        if ($validated['estado_aprobacion'] === 'APROBADA') {
            $asistencia = $licencia->asistencia()->with([
                'sesion.calendario.disponibilidades',
                'inscripcion',
            ])->firstOrFail();

            $sesionOriginal = $asistencia->sesion;
            $calendario = $sesionOriginal->calendario;

            // Buscar slot: fecha exacta pedida por tutor o la próxima disponible
            $slot = null;
            if ($request->filled('fecha_reprogramacion')) {
                $fechaElegida = Carbon::parse($request->fecha_reprogramacion)->toDateString();

                $yaReservada = SesionProgramada::query()
                    ->where('id', '!=', $sesionOriginal->id)
                    ->whereDate('fecha_sesion', $fechaElegida)
                    ->where('hora_inicio', '<', $sesionOriginal->hora_fin)
                    ->where('hora_fin', '>', $sesionOriginal->hora_inicio)
                    ->whereHas('calendario', function ($q) use ($calendario) {
                        $q->where('id_tutor', $calendario->id_tutor);
                    })
                    ->exists();

                if ($yaReservada) {
                    return Redirect::back()->withErrors([
                        'fecha_reprogramacion' => 'La fecha indicada ya está reservada para ese horario.',
                    ]);
                }

                $slot = [
                    'fecha_sesion' => $fechaElegida,
                    'hora_inicio' => $sesionOriginal->hora_inicio,
                    'hora_fin' => $sesionOriginal->hora_fin,
                ];
            } else {
                $slot = $this->buscarProximaSesionDisponible(
                    $calendario,
                    Carbon::parse($sesionOriginal->fecha_sesion),
                    $sesionOriginal->id,
                );
                if (!$slot) {
                    return Redirect::back()->withErrors([
                        'fecha_reprogramacion' => 'No hay fechas disponibles en los próximos 90 días para reprogramar.',
                    ]);
                }
            }

            DB::transaction(function () use ($licencia, $validated, $asistencia, $sesionOriginal, $calendario, $slot) {
                // Marcar asistencia original como FALTO
                $asistencia->update(['estado_asistencia' => 'AUSENTE']);

                // Crear nueva sesión programada
                $nuevaSesion = SesionProgramada::create([
                    'id_calendario' => $calendario->id,
                    'fecha_sesion' => $slot['fecha_sesion'],
                    'hora_inicio' => $slot['hora_inicio'],
                    'hora_fin' => $slot['hora_fin'],
                    'link_sesion' => null,
                    'numero_sesion' => null,
                ]);

                // Nueva asistencia para la misma inscripción
                Asistencia::create([
                    'id_sesion' => $nuevaSesion->id,
                    'id_inscripcion' => $asistencia->id_inscripcion,
                    'estado_asistencia' => 'PENDIENTE',
                    'observaciones' => 'Reprogramada por licencia #' . $licencia->id_licencia,
                ]);

                // Actualizar licencia
                $licencia->update([
                    'estado_aprobacion' => $validated['estado_aprobacion'],
                    'observacion_admin' => $validated['observacion_admin'],
                ]);
            });

            return Redirect::route('licencias.index')
                ->with('success', 'Licencia aprobada. Sesión reprogramada para ' . $slot['fecha_sesion'] . '.');
        }

        // PENDIENTE o RECHAZADA: solo actualizar campos
        $licencia->update([
            'estado_aprobacion' => $validated['estado_aprobacion'],
            'observacion_admin' => $validated['observacion_admin'],
        ]);

        return Redirect::route('licencias.index')->with('success', 'Licencia actualizada.');
    }

    private function resolverModo(Request $request): string
    {
        $auth = $request->user();
        $modoSolicitado = $request->input('modo', $request->query('modo'));

        if ($auth?->is_alumno && $auth?->is_tutor) {
            return in_array($modoSolicitado, ['alumno', 'tutor'], true)
                ? $modoSolicitado
                : 'alumno';
        }

        if ($auth?->is_alumno) {
            return 'alumno';
        }

        if ($auth?->is_tutor) {
            return 'tutor';
        }

        return 'admin';
    }

    // ─────────────────────────────────────────────────────────────────
    // DESTROY
    // ─────────────────────────────────────────────────────────────────

    public function destroy(Licencia $licencia)
    {
        $licencia->delete();
        return Redirect::route('licencias.index')->with('success', 'Licencia eliminada.');
    }

    // ─────────────────────────────────────────────────────────────────
    // HELPERS PRIVADOS
    // ─────────────────────────────────────────────────────────────────

    /**
     * Busca el próximo slot libre en la disponibilidad del calendario,
     * evitando conflictos con sesiones ya ocupadas.
     *
     * @param Calendario $calendario  Con disponibilidades cargadas
     * @param Carbon     $desde       Busca a partir del día SIGUIENTE a esta fecha
     * @param int        $excluyeId   Sesión original (no cuenta como conflicto)
     * @param string|null $fechaExacta Si se pasa, solo verifica ese día exacto
     */
    private function buscarProximaSesionDisponible(
        Calendario $calendario,
        Carbon $desde,
        int $excluyeId,
        ?string $fechaExacta = null,
    ): ?array {
        $disponibilidades = $calendario->disponibilidades;
        if ($disponibilidades->isEmpty()) {
            return null;
        }

        $diaMap = [
            1 => 'LUNES',
            2 => 'MARTES',
            3 => 'MIERCOLES',
            4 => 'JUEVES',
            5 => 'VIERNES',
            6 => 'SABADO',
            7 => 'DOMINGO',
        ];

        if ($fechaExacta) {
            // Verificar solo esa fecha
            $cursor = Carbon::parse($fechaExacta)->startOfDay();
            $limite = $cursor->copy();
        } else {
            $cursor = $desde->copy()->addDay()->startOfDay();
            $limite = $cursor->copy()->addDays(90);
        }

        while ($cursor->lessThanOrEqualTo($limite)) {
            $diaActual = $diaMap[$cursor->dayOfWeekIso] ?? null;
            $bloque = $disponibilidades->firstWhere('dia_semana', $diaActual);

            if ($bloque) {
                $ocupadaEnCalendario = SesionProgramada::query()
                    ->where('id_calendario', $calendario->id)
                    ->whereDate('fecha_sesion', $cursor->toDateString())
                    ->where('id', '!=', $excluyeId)
                    ->exists();

                $choqueHorarioTutor = SesionProgramada::query()
                    ->where('id', '!=', $excluyeId)
                    ->whereDate('fecha_sesion', $cursor->toDateString())
                    ->where('hora_inicio', '<', $bloque->hora_cierre)
                    ->where('hora_fin', '>', $bloque->hora_apertura)
                    ->whereHas('calendario', function ($q) use ($calendario) {
                        $q->where('id_tutor', $calendario->id_tutor);
                    })
                    ->exists();

                if (!$ocupadaEnCalendario && !$choqueHorarioTutor) {
                    return [
                        'fecha_sesion' => $cursor->toDateString(),
                        'hora_inicio' => $bloque->hora_apertura,
                        'hora_fin' => $bloque->hora_cierre,
                    ];
                }
            }

            if ($fechaExacta)
                break;
            $cursor->addDay();
        }

        return null;
    }

    private function applyFiltrosBasicos($query, Request $request): void
    {
        if ($request->filled('search')) {
            $query->where('motivo', 'like', "%{$request->search}%");
        }
        if ($request->filled('estado_aprobacion')) {
            $query->where('estado_aprobacion', $request->estado_aprobacion);
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_solicitud', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_solicitud', '<=', $request->fecha_hasta);
        }
    }

    private function applyFiltrosAdmin($query, Request $request): void
    {
        if ($request->filled('id_tutor')) {
            $query->whereHas(
                'asistencia.inscripcion.calendario',
                fn($q) =>
                $q->where('id_tutor', $request->id_tutor)
            );
        }
        if ($request->filled('id_servicio')) {
            $query->whereHas(
                'asistencia.inscripcion.calendario',
                fn($q) =>
                $q->where('id_servicio', $request->id_servicio)
            );
        }
    }
}
