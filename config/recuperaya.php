<?php

// Reglas de negocio de RecuperaYa (se pueden cambiar sin tocar el código).
return [
    'dominio_correo' => 'continental.edu.pe',   // RN-01
    'dias_no_reclamado' => 90,                  // RN-08
    'max_intentos_reclamo' => 2,                // RN-06
    'max_intentos_login' => 5,                  // RNF-04
    'minutos_bloqueo' => 15,                    // RNF-04
    'umbral_coincidencia' => 60,                // RF-07
];
