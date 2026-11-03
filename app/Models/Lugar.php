<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Lugar extends Model
{
    protected $table = 'lugares';

    protected $guarded = [];

    protected $casts = ['activo' => 'boolean'];

    public function scopeActivos(Builder $q): Builder
    {
        return $q->where('activo', true)->orderBy('nombre');
    }
}
