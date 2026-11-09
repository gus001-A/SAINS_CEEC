<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Estudiante;
use App\Support\Curp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Ruta a la que se redirige al estudiante tras autenticarse.
     */
    private function destinoEstudiante(User $user): string
    {
        $estudiante = Estudiante::where('usuario', $user->id)->first();

        if ($estudiante && $estudiante->plan_activo) {
            return route('estudiante.clases-premium');
        }

        return route('estudiante.dashboard');
    }

    private function guardarSesionLegacy(User $user): void
    {
        session([
            'MM_Username' => $user->correo ?? $user->curp,
            'MM_UserGroup' => $user->rol,
            'user_id' => $user->id,
            'user_nombre' => $user->nombre ?? $user->correo ?? $user->curp,
            'user_rol' => $user->rol,
        ]);
    }

    /**
     * Validación en tiempo real de la CURP (formulario de registro).
     * Devuelve nombre, apellidos, fecha de nacimiento y sexo para autocompletar.
     */
    public function validarCurp(Request $request)
    {
        $curp = Curp::normalizar($request->query('curp'));
        $resultado = Curp::consultar($curp);

        if ($resultado['valida'] && User::where('curp', $curp)->exists()) {
            $resultado = [
                'valida' => false,
                'mensaje' => 'Esta CURP ya tiene una cuenta. Inicia sesión.',
                'fuente' => $resultado['fuente'],
                'datos' => null,
            ];
        }

        return response()->json($resultado);
    }

    // Registro de estudiante: CURP y contraseña. Nombre y fecha de nacimiento
    // se autocompletan con la CURP (RENAPO) y se usan en "Completar perfil".
    public function register(Request $request)
    {
        $request->merge([
            'curp' => Curp::normalizar($request->curp),
            'correo' => mb_strtolower(trim((string) $request->correo)),
            'telefono' => preg_replace('/\D+/', '', (string) $request->telefono),
            'nombre' => $this->mayusculas($request->nombre),
            'paterno' => $this->mayusculas($request->paterno),
            'materno' => $this->mayusculas($request->materno),
        ]);

        $request->validate([
            'curp' => ['required', 'string', 'size:18', 'unique:usuario,curp',
                function ($attribute, $value, $fail) {
                    if ($error = Curp::errorFormato($value)) {
                        $fail($error);
                    }
                },
            ],
            'nombre' => 'required|string|max:255',
            'paterno' => 'required|string|max:255',
            'materno' => 'nullable|string|max:255',
            'correo' => 'required|email|max:255|unique:usuario,correo',
            'telefono' => ['required', 'regex:/^\d{10}$/'],
            'password' => 'required|min:6|confirmed',
        ], [
            'curp.unique' => 'Esta CURP ya tiene una cuenta. Inicia sesión.',
            'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'correo.unique' => 'Este correo ya tiene una cuenta. Inicia sesión.',
            'telefono.regex' => 'El teléfono celular debe tener 10 dígitos.',
        ], [
            'curp' => 'CURP', 'paterno' => 'apellido paterno', 'materno' => 'apellido materno',
            'correo' => 'correo electrónico', 'telefono' => 'teléfono celular',
        ]);

        $consulta = Curp::consultar($request->curp);
        if (!$consulta['valida']) {
            throw ValidationException::withMessages(['curp' => $consulta['mensaje']]);
        }

        // Si RENAPO respondió, sus datos mandan sobre lo enviado por el navegador.
        $datos = $consulta['datos'];
        if ($consulta['fuente'] !== 'renapo') {
            $datos = array_merge($datos, $request->only('nombre', 'paterno', 'materno'));
        }

        $user = User::create([
            'curp' => $request->curp,
            'correo' => $request->correo,
            'telefono' => $request->telefono,
            'contraseña' => Hash::make($request->password),
            'rol' => 'estudiante',
        ]);

        \App\Models\Notificacion::enviar($user->id, [
            'tipo' => 'bienvenida',
            'titulo' => '¡Bienvenido a CEEC! 🎓',
            'mensaje' => 'Completa tu perfil y empieza a practicar con los simuladores gratuitos.',
            'url' => route('estudiante.completar-perfil'),
            'icono' => 'bell', 'color' => 'indigo',
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $this->guardarSesionLegacy($user);
        session(['registro_curp' => $datos]);

        return redirect($this->destinoEstudiante($user));
    }

    // Inicio de sesión: con la CURP o con el correo electrónico.
    public function login(Request $request)
    {
        // En CEEC se entra únicamente con el correo electrónico (alumnos y administradores).
        $request->merge(['correo' => mb_strtolower(trim((string) $request->correo))]);
        $request->validate([
            'correo' => 'required|email|max:255',
            'password' => 'required|string',
        ], [
            'correo.required' => 'Escribe tu correo electrónico.',
            'correo.email' => 'Escribe un correo electrónico válido.',
            'password.required' => 'Escribe tu contraseña.',
        ], ['correo' => 'correo electrónico']);

        // SQLite compara distinguiendo mayúsculas, por eso se compara en minúsculas.
        $user = User::whereRaw('LOWER(correo) = ?', [$request->correo])->first();

        if (!$user || !$user->contraseña || !Hash::check($request->password, $user->contraseña)) {
            throw ValidationException::withMessages([
                'correo' => 'El correo o la contraseña son incorrectos.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $this->guardarSesionLegacy($user);

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended($this->destinoEstudiante($user));
    }

    private function mayusculas($texto): ?string
    {
        $texto = trim(preg_replace('/\s+/', ' ', (string) $texto));

        return $texto === '' ? null : mb_strtoupper($texto);
    }

    // Cerrar sesión
    public function logout(Request $request)
    {
        session()->forget(['MM_Username', 'MM_UserGroup', 'user_id', 'user_nombre', 'user_rol']);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Sesión cerrada correctamente');
    }
}
