<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function index(Request $request)
    {
        $query = User::orderBy('created_at', 'desc');

        // Filtro por búsqueda (nombre o email)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }

        // Filtro por rol
        if ($request->filled('role')) {
            $role = $request->input('role');
            if ($role === 'alumno') {
                $query->where('is_alumno', true);
            } elseif ($role === 'tutor') {
                $query->where('is_tutor', true);
            } elseif ($role === 'propietario') {
                $query->where('is_propietario', true);
            }
        }

        $usuarios = $query->paginate(10);

        return Inertia::render('Users/Index', [
            'usuarios' => $usuarios,
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    /**
     * 2. CREATE: Muestra el formulario de creación.
     */
    public function create()
    {
        abort_unless(auth()->check() && auth()->user()->is_propietario, 403);

        return Inertia::render('Users/Create');
    }

    /**
     * 3. STORE: Guarda el nuevo tutor en la BD.
     */
    public function store(Request $request)
    {
        abort_unless($request->user() && $request->user()->is_propietario, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'especialidad' => 'nullable|string|max:100',
            'biografia' => 'nullable|string',
            'banco_nombre' => 'nullable|string|max:100',
            'banco_cbu' => 'nullable|string|max:50',
        ], [
            'name.required' => 'El nombre es obligatorio',
            'name.string' => 'El nombre debe ser una cadena de texto',
            'name.max' => 'El nombre no puede tener más de 150 caracteres',
            'email.required' => 'El correo electrónico es obligatorio',
            'email.email' => 'El correo electrónico no tiene un formato válido',
            'email.max' => 'El correo electrónico no puede tener más de 255 caracteres',
            'email.unique' => 'El correo electrónico ya está registrado',
            'password.required' => 'La contraseña es obligatoria',
            'password.string' => 'La contraseña debe ser una cadena de texto',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'La confirmación de contraseña no coincide',
            'especialidad.string' => 'La especialidad debe ser una cadena de texto',
            'especialidad.max' => 'La especialidad no puede tener más de 100 caracteres',
            'biografia.string' => 'La biografía debe ser una cadena de texto',
            'banco_nombre.string' => 'El nombre del banco debe ser una cadena de texto',
            'banco_nombre.max' => 'El nombre del banco no puede tener más de 100 caracteres',
            'banco_cbu.string' => 'El CBU debe ser una cadena de texto',
            'banco_cbu.max' => 'El CBU no puede tener más de 50 caracteres',
        ], [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
            'especialidad' => 'especialidad',
            'biografia' => 'biografía',
            'banco_nombre' => 'nombre del banco',
            'banco_cbu' => 'CBU',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_alumno' => false,
                'is_tutor' => true,
            ]);

            Tutor::create([
                'id_usuario' => $user->id,
                'especialidad' => $validated['especialidad'] ?? null,
                'biografia' => $validated['biografia'] ?? null,
                'banco_nombre' => $validated['banco_nombre'] ?? null,
                'banco_cbu' => $validated['banco_cbu'] ?? null,
            ]);
        });

        return Redirect::route('users.index')->with('success', 'Tutor creado correctamente.');
    }

    /**
     * 4. EDIT: Muestra el formulario de edición con datos cargados.
     */
    public function edit(User $user)
    {
        // Traemos las categorías para el select

        if ($user->is_tutor) {
            $tutor = Tutor::where('id_usuario', $user->id)->first();
        }
        if ($user->is_alumno) {
            $alumno = Alumno::where('id_usuario', $user->id)->first();
        }

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'tutor' => $tutor ?? null,
            'alumno' => $alumno ?? null,
        ]);
    }

    /**
     * 5. UPDATE: Actualiza el tutor existente.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            /* 'estado' => 'required|in:ACTIVO,INACTIVO,BLOQUEADO', */
            'is_alumno' => 'boolean',
            'is_tutor' => 'boolean',
            'is_propietario' => 'boolean',
            // Campos de alumno
            'direccion' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'nivel_educativo' => 'nullable|string|max:50',
            // Campos de tutor
            'especialidad' => 'nullable|string|max:100',
            'biografia' => 'nullable|string',
            'banco_nombre' => 'nullable|string|max:100',
            'banco_cbu' => 'nullable|string|max:50',
        ], [
            'name.required' => 'El nombre es obligatorio',
            'name.string' => 'El nombre debe ser una cadena de texto',
            'name.max' => 'El nombre no puede tener más de 255 caracteres',
            'email.required' => 'El correo electrónico es obligatorio',
            'email.email' => 'El correo electrónico no tiene un formato válido',
            'email.max' => 'El correo electrónico no puede tener más de 255 caracteres',
            'email.unique' => 'El correo electrónico ya está registrado',
            'is_alumno.boolean' => 'El valor de alumno debe ser verdadero o falso',
            'is_tutor.boolean' => 'El valor de tutor debe ser verdadero o falso',
            'is_propietario.boolean' => 'El valor de propietario debe ser verdadero o falso',
            'direccion.string' => 'La dirección debe ser una cadena de texto',
            'direccion.max' => 'La dirección no puede tener más de 255 caracteres',
            'fecha_nacimiento.date' => 'La fecha de nacimiento debe ser una fecha válida',
            'nivel_educativo.string' => 'El nivel educativo debe ser una cadena de texto',
            'nivel_educativo.max' => 'El nivel educativo no puede tener más de 50 caracteres',
            'especialidad.string' => 'La especialidad debe ser una cadena de texto',
            'especialidad.max' => 'La especialidad no puede tener más de 100 caracteres',
            'biografia.string' => 'La biografía debe ser una cadena de texto',
            'banco_nombre.string' => 'El nombre del banco debe ser una cadena de texto',
            'banco_nombre.max' => 'El nombre del banco no puede tener más de 100 caracteres',
            'banco_cbu.string' => 'El CBU debe ser una cadena de texto',
            'banco_cbu.max' => 'El CBU no puede tener más de 50 caracteres',
        ], [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'is_alumno' => 'es alumno',
            'is_tutor' => 'es tutor',
            'is_propietario' => 'es propietario',
            'direccion' => 'dirección',
            'fecha_nacimiento' => 'fecha de nacimiento',
            'nivel_educativo' => 'nivel educativo',
            'especialidad' => 'especialidad',
            'biografia' => 'biografía',
            'banco_nombre' => 'nombre del banco',
            'banco_cbu' => 'CBU',
        ]);

        // Actualizar datos del usuario
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            /*  'estado' => $validated['estado'], */
            'is_alumno' => $validated['is_alumno'] ?? false,
            'is_tutor' => $validated['is_tutor'] ?? false,
            'is_propietario' => $validated['is_propietario'] ?? false,
        ]);

        // Actualizar datos de alumno si aplica
        if ($validated['is_alumno'] ?? false) {
            Alumno::updateOrCreate(
                ['id_usuario' => $user->id],
                [
                    'direccion' => $validated['direccion'] ?? null,
                    'fecha_nacimiento' => $validated['fecha_nacimiento'] ?? null,
                    'nivel_educativo' => $validated['nivel_educativo'] ?? null,
                ]
            );
        }

        // Actualizar datos de tutor si aplica
        if ($validated['is_tutor'] ?? false) {
            Tutor::updateOrCreate(
                ['id_usuario' => $user->id],
                [
                    'especialidad' => $validated['especialidad'] ?? null,
                    'biografia' => $validated['biografia'] ?? null,
                    'banco_nombre' => $validated['banco_nombre'] ?? null,
                    'banco_cbu' => $validated['banco_cbu'] ?? null,
                ]
            );
        }

        return Redirect::route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * 6. DESTROY: Elimina (Soft o Hard delete) el tutor.
     */
    public function destroy(User $user)
    {

        $user->delete();

        // Asegúrate de usar redirect() o Redirect::route()
        return Redirect::route('users.index');
    }


}