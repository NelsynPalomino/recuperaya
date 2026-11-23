<?php
/**
 * Casos prácticos de la Práctica #05 (L23 – Pruebas de caja negra y caja blanca).
 *  - Caja negra: asignación de primas a empleados.
 *  - Caja blanca: recorrido de un vector de 10 números.
 */

/**
 * CAJA NEGRA. Devuelve "P1".."P4" o el motivo del error.
 * Registro: número (3 dígitos, 001-999), nombre (10 caracteres alfanuméricos),
 * meses (3 dígitos, 000-999) y directivo ("+" o "-").
 */
function asignarPrima(string $numero, string $nombre, string $meses, string $directivo): string
{
    if (!preg_match('/^\d{3}$/', $numero) || $numero === '000') {
        return 'ERROR: número de empleado inválido';
    }
    if (!preg_match('/^[A-Za-z0-9 ]{10}$/', $nombre)) {
        return 'ERROR: nombre inválido';
    }
    if (!preg_match('/^\d{3}$/', $meses)) {
        return 'ERROR: meses de trabajo inválido';
    }
    if ($directivo !== '+' && $directivo !== '-') {
        return 'ERROR: indicador de directivo inválido';
    }
    $antiguo = (int) $meses >= 12;
    if ($directivo === '+') {
        return $antiguo ? 'P1' : 'P3';
    }
    return $antiguo ? 'P2' : 'P4';
}

/**
 * CAJA BLANCA. Versión corregida del ejercicio: el enunciado usa while (1<10), que nunca termina;
 * la condición correcta es while (i < n). Devuelve las líneas que el programa imprime.
 */
function recorrerVector(array $v): array
{
    $salida = [];
    $i = 0;                                  // nodo 1
    $s = 0;
    while ($i < count($v)) {                 // nodo 2 (decisión 1)
        $salida[] = (string) $v[$i];         // nodo 3
        if ($v[$i] % 2 == 0) {               // nodo 4 (decisión 2)
            $s += $v[$i];                    // nodo 5
            $salida[] = "Suma de pares: $s";
        } else {
            if ($v[$i] >= 50) {              // nodo 6 (decisión 3)
                $salida[] = "Número mayor de 50 {$v[$i]}";   // nodo 7
            } else {
                $salida[] = "Número menor de 50 {$v[$i]}";   // nodo 8
            }
        }
        $i++;                                // nodo 9
    }
    return $salida;                          // nodo 10
}
