<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        $lista = $request->user()->notificaciones()->latest()->take(50)->get();
        $request->user()->notificaciones()->where('leida', false)->update(['leida' => true]);
        return view('notificaciones.index', ['notificaciones' => $lista]);
    }
}
