<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Objeto extends Model
{
    use HasFactory;

    public const EN_CUSTODIA = 'En custodia';
    public const RECLAMADO = 'Reclamado';
    public const DEVUELTO = 'Devuelto';
    public const NO_RECLAMADO = 'No reclamado';
    public const ESTADOS = [self::EN_CUSTODIA, self::RECLAMADO, self::DEVUELTO, self::NO_RECLAMADO];

    protected $table = 'objetos';

    /**
     * Asignación masiva (fillable): campos que vienen del formulario.
     * El "estado", el "codigo", el "slug", la foto y las respuestas cifradas
     * NO se llenan de forma masiva: los asigna el sistema (ver ObjetoController).
     */
    protected $fillable = ['categoria_id', 'lugar_id', 'descripcion', 'color', 'fecha_hallazgo', 'pregunta1', 'pregunta2'];

    // Las respuestas de verificación nunca se envían a las vistas ni en JSON.
    protected $hidden = ['respuesta1_hash', 'respuesta2_hash'];

    protected $casts = ['fecha_hallazgo' => 'date'];

    /** URL amigable: /catalogo/celular-negro-obj-0001 en lugar de /catalogo/1 (Sesión 09). */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function lugar(): BelongsTo
    {
        return $this->belongsTo(Lugar::class);
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function reclamos(): HasMany
    {
        return $this->hasMany(Reclamo::class);
    }

    public function entrega(): HasOne
    {
        return $this->hasOne(Entrega::class);
    }

    public function scopeEnCustodia(Builder $q): Builder
    {
        return $q->where('estado', self::EN_CUSTODIA);
    }

    public function urlFoto(): string
    {
        // asset() usa la dirección con la que se abrió el sistema (artisan serve o XAMPP).
        return asset($this->foto ? 'storage/' . $this->foto : 'img/sin-foto.svg');
    }

    public function claseEstado(): string
    {
        return [self::EN_CUSTODIA => 'azul', self::RECLAMADO => 'ambar', self::DEVUELTO => 'verde', self::NO_RECLAMADO => 'gris'][$this->estado] ?? 'gris';
    }

    /** RN-08: los objetos en custodia por más de 90 días pasan a "No reclamado". */
    public static function marcarNoReclamados(): int
    {
        return static::where('estado', self::EN_CUSTODIA)
            ->whereDate('fecha_hallazgo', '<', now()->subDays(config('recuperaya.dias_no_reclamado'))->toDateString())
            ->update(['estado' => self::NO_RECLAMADO]);
    }
}
