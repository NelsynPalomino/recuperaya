<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegistroRequest;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/** Registro, inicio y cierre de sesión (RF-01, RNF-04). */
class AuthController extends Controller
{
    private const CUENTA_DESACTIVADA = 'Tu cuenta está desactivada. Comunícate con el administrador.';

    /** Devuelve el mensaje de error si el acceso no procede, o null si las credenciales son válidas. */
    private function motivoDeRechazo(?User $usuario, LoginRequest $request): ?string
    {
        if ($usuario?->estaBloqueado()) {
            $min = (int) ceil(now()->diffInMinutes($usuario->bloqueado_hasta, true));
            $error = "Cuenta bloqueada temporalmente por intentos fallidos. Inténtalo en $min minuto(s).";
        } elseif (! $usuario || ! Hash::check($request->password, $usuario->password)) {
            if ($usuario) {
                $this->registrarFallo($usuario);
            }
            $error = 'Correo o contraseña incorrectos.';
        } elseif (! $usuario->activo) {
            $error = self::CUENTA_DESACTIVADA;
        }
        return $error ?? null;
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        $usuario = User::where('email', mb_strtolower($request->email))->first();
        $error = $this->motivoDeRechazo($usuario, $request);
        if ($error !== null) {
            return back()->withInput($request->only('email'))->with('error', $error);
        }
        $usuario->forceFill(['intentos_fallidos' => 0, 'bloqueado_hasta' => null])->save();
        Auth::login($usuario);
        $request->session()->regenerate();
        Auditoria::registrar('inicio_sesion', $usuario->email);
        return redirect()->intended(route('inicio'));
    }

    private function registrarFallo(User $usuario): void
    {
        $intentos = $usuario->intentos_fallidos + 1;
        $bloqueo = null;
        if ($intentos >= config('recuperaya.max_intentos_login')) {
            $bloqueo = now()->addMinutes(config('recuperaya.minutos_bloqueo'));
            $intentos = 0;
        }
        // forceFill porque estos campos no están en $fillable (protegidos de la asignación masiva).
        $usuario->forceFill(['intentos_fallidos' => $intentos, 'bloqueado_hasta' => $bloqueo])->save();
    }

    public function showRegistro()
    {
        return view('auth.registro');
    }

    public function registro(RegistroRequest $request)
    {
        // Asignación masiva: solo pasan los campos de $fillable del modelo User.
        $usuario = User::create($request->validated());
        Auth::login($usuario);
        $request->session()->regenerate();
        Auditoria::registrar('registro_usuario', $usuario->email);
        return redirect()->route('inicio')->with('exito', '¡Bienvenido(a) a RecuperaYa! Tu cuenta fue creada.');
    }

    public function logout(Request $request)
    {
        Auditoria::registrar('cierre_sesion', (string) $request->user()?->email);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('exito', 'Cerraste sesión correctamente.');
    }
}
