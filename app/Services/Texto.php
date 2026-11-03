<?php

namespace App\Services;

class Texto
{
    /** Minúsculas, sin tildes y sin espacios extra (para comparar respuestas y colores). */
    public static function normalizar(?string $t): string
    {
        $t = mb_strtolower(trim((string) $t), 'UTF-8');
        $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        $t = preg_replace('/[^a-z0-9 ]/', ' ', $t);
        return trim(preg_replace('/\s+/', ' ', $t));
    }
}
