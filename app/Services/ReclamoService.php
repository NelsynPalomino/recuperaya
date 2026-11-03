<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Entrega;
use App\Models\Objeto;
use App\Models\Reclamo;
use App\Models\ReportePerdida;
use Illuminate\Support\Facades\DB;

/** Reglas del reclamo y la entrega (RF-09, RF-10, RF-11, RN-05, RN-06). */
class ReclamoService
{
    public static function verificarRespuestas(Objeto $objeto, string $r1, string $r2): bool
    {
        return password_verify(Texto::normalizar($r1), $objeto->respuesta1_hash)
            && password_verify(Texto::normalizar($r2), $objeto->respuesta2_hash);
    }

    /** @return array{ok: bool, tipo: string, mensaje: string} */
    public static function reclamar(int $userId, Objeto $objeto, string $r1, string $r2): array
    {
        if ($objeto->estado !== Objeto::EN_CUSTODIA) {
            return ['ok' => false, 'tipo' => 'error', 'mensaje' => 'Este objeto ya no está disponible para reclamo.'];
        }
        $max = config('recuperaya.max_intentos_reclamo');
        $reclamo = Reclamo::where('user_id', $userId)->where('objeto_id', $objeto->id)->latest('id')->first();
        if ($reclamo && $reclamo->estado === Reclamo::RECHAZADO) {
            return ['ok' => false, 'tipo' => 'error', 'mensaje' => "Ya usaste tus $max intentos para este objeto. Acércate a la oficina de Bienestar si crees que es un error."];
        }
        if ($reclamo && in_array($reclamo->estado, [Reclamo::APROBADO, Reclamo::ENTREGADO], true)) {
            return ['ok' => false, 'tipo' => 'aviso', 'mensaje' => 'Ya tienes un reclamo aprobado para este objeto.'];
        }
        $reclamo ??= Reclamo::create(['user_id' => $userId, 'objeto_id' => $objeto->id]);
        // "estado" e "intentos" están en $guarded, por eso se asignan de forma explícita:
        $reclamo->intentos = $reclamo->intentos + 1;

        if (self::verificarRespuestas($objeto, $r1, $r2)) {
            $reclamo->estado = Reclamo::APROBADO;
            $reclamo->save();
            $objeto->estado = Objeto::RECLAMADO;
            $objeto->save();
            Notificador::enviar($userId, 'Reclamo aprobado', "Tu reclamo del objeto {$objeto->codigo} fue aprobado. Acércate a la oficina de Bienestar Universitario con tu carné para recogerlo.", route('reclamos.mis'));
            Notificador::aEncargados('Reclamo aprobado pendiente de entrega', "El objeto {$objeto->codigo} tiene un reclamo aprobado. Verifica el carné al entregarlo.", route('entregas.index'));
            Auditoria::registrar('reclamo_aprobado', "Objeto {$objeto->codigo}, reclamo {$reclamo->id}");
            return ['ok' => true, 'tipo' => 'exito', 'mensaje' => '¡Respuestas correctas! Tu reclamo fue aprobado. Recoge tu objeto en la oficina de Bienestar presentando tu carné.'];
        }
        if ($reclamo->intentos >= $max) {
            $reclamo->estado = Reclamo::RECHAZADO;
            $reclamo->save();
            Notificador::enviar($userId, 'Reclamo rechazado', "Tu reclamo del objeto {$objeto->codigo} fue rechazado porque las respuestas de verificación no coinciden.", route('reclamos.mis'));
            Auditoria::registrar('reclamo_rechazado', "Objeto {$objeto->codigo}, reclamo {$reclamo->id}");
            return ['ok' => false, 'tipo' => 'error', 'mensaje' => 'Las respuestas no coinciden. Tu reclamo fue rechazado.'];
        }
        $reclamo->save();
        $quedan = $max - $reclamo->intentos;
        return ['ok' => false, 'tipo' => 'aviso', 'mensaje' => "Las respuestas no coinciden. Te queda $quedan intento."];
    }

    /** @return array{ok: bool, tipo: string, mensaje: string} */
    public static function entregar(Reclamo $reclamo, int $encargadoId, string $codigoCarne, ?string $obs = null): array
    {
        if ($reclamo->estado !== Reclamo::APROBADO) {
            return ['ok' => false, 'tipo' => 'error', 'mensaje' => 'Solo se pueden entregar objetos con reclamo aprobado.'];
        }
        DB::transaction(function () use ($reclamo, $encargadoId, $codigoCarne, $obs) {
            Entrega::create([
                'objeto_id' => $reclamo->objeto_id, 'reclamo_id' => $reclamo->id, 'receptor_id' => $reclamo->user_id,
                'encargado_id' => $encargadoId, 'codigo_carne' => strtoupper($codigoCarne), 'observacion' => $obs, 'fecha_entrega' => now(),
            ]);
            $reclamo->estado = Reclamo::ENTREGADO;
            $reclamo->save();
            $reclamo->objeto->estado = Objeto::DEVUELTO;
            $reclamo->objeto->save();
            ReportePerdida::where('user_id', $reclamo->user_id)->where('estado', ReportePerdida::ACTIVO)
                ->whereIn('id', fn ($q) => $q->select('reporte_perdida_id')->from('coincidencias')->where('objeto_id', $reclamo->objeto_id))
                ->update(['estado' => ReportePerdida::RESUELTO]);
        });
        $codigo = $reclamo->objeto->codigo;
        Notificador::enviar($reclamo->user_id, 'Objeto entregado', "Registramos la entrega del objeto $codigo. ¡Gracias por usar RecuperaYa!", route('reclamos.mis'));
        Auditoria::registrar('entrega', "Objeto $codigo entregado al usuario {$reclamo->user_id}");
        return ['ok' => true, 'tipo' => 'exito', 'mensaje' => "Entrega registrada. El objeto $codigo ahora figura como Devuelto."];
    }
}
