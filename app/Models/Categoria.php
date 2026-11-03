<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';

    // Asignación masiva con guarded: todos los campos se pueden llenar excepto el id y las fechas.
    protected $guarded = [];

    protected $casts = ['activo' => 'boolean'];

    public function scopeActivos(Builder $q): Builder
    {
        return $q->where('activo', true)->orderBy('nombre');
    }
}
