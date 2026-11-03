<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ADMIN = 'administrador';
    public const ENCARGADO = 'encargado';
    public const USUARIO = 'usuario';

    /**
     * Asignación masiva: solo estos campos se llenan desde un formulario.
     * "rol", "activo" e "intentos_fallidos" NO están aquí: solo el sistema o el administrador los cambian.
     */
    protected $fillable = ['name', 'email', 'codigo_universitario', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
            'bloqueado_hasta' => 'datetime',
        ];
    }

    public function esAdmin(): bool
    {
        return $this->rol === self::ADMIN;
    }

    public function esEncargado(): bool
    {
        return $this->rol === self::ENCARGADO;
    }

    public function esUsuario(): bool
    {
        return $this->rol === self::USUARIO;
    }

    public function puedeGestionar(): bool
    {
        return $this->esAdmin() || $this->esEncargado();
    }

    public function estaBloqueado(): bool
    {
        return $this->bloqueado_hasta !== null && $this->bloqueado_hasta->isFuture();
    }

    public function reportes(): HasMany
    {
        return $this->hasMany(ReportePerdida::class);
    }

    public function reclamos(): HasMany
    {
        return $this->hasMany(Reclamo::class);
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class);
    }
}
