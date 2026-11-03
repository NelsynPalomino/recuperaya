<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coincidencia extends Model
{
    protected $table = 'coincidencias';

    protected $fillable = ['reporte_perdida_id', 'objeto_id', 'puntaje'];
}
