<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Avisos (RF-08): se guardan en la bandeja del sistema y se envían por correo (MAIL_MAILER del .env). */
class Notificador
{
    public static function enviar(int $userId, string $titulo, string $mensaje, ?string $enlace = null): Notificacion
    {
        $n = Notificacion::create(['user_id' => $userId, 'titulo' => $titulo, 'mensaje' => $mensaje, 'enlace' => $enlace]);
        $usuario = User::find($userId);
        for ($i = 0; $i < 3 && $usuario; $i++) {
            try {
                Mail::raw($mensaje . ($enlace ? "\n\n$enlace" : ''), function ($m) use ($usuario, $titulo) {
                    $m->to($usuario->email)->subject('[RecuperaYa] ' . $titulo);
                });
                $n->update(['enviada_correo' => true]);
                break;
            } catch (Throwable $e) {
                report($e);
            }
        }
        return $n;
    }

    public static function aEncargados(string $titulo, string $mensaje, ?string $enlace = null): void
    {
        User::where('rol', User::ENCARGADO)->where('activo', true)->each(
            fn (User $u) => self::enviar($u->id, $titulo, $mensaje, $enlace)
        );
    }
}
