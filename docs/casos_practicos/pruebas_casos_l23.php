<?php
/** Ejecuta los casos de prueba de los casos prácticos L23.  Uso: php docs/casos_practicos/pruebas_casos_l23.php */
require __DIR__ . '/casos_l23.php';

$total = 0; $fallas = 0;
function caso(string $id, string $desc, $obtenido, $esperado): void
{
    global $total, $fallas;
    $total++;
    $ok = $obtenido === $esperado;
    if (!$ok) $fallas++;
    $mostrar = is_array($obtenido) ? implode(' | ', $obtenido) : $obtenido;
    printf("  [%s] %-7s %-58s -> %s\n", $ok ? 'OK' : 'FALLA', $id, $desc, $mostrar);
}

echo "== CAJA NEGRA: particiones de equivalencia ==\n";
caso('CE-01', 'Directivo con 24 meses (clases 1,6,9,12)', asignarPrima('123', 'JUAN PEREZ', '024', '+'), 'P1');
caso('CE-02', 'No directivo con 36 meses (1,6,9,13)', asignarPrima('456', 'ANA TORRES', '036', '-'), 'P2');
caso('CE-03', 'Directivo con 5 meses (1,6,8,12)', asignarPrima('789', 'LUIS ROJAS', '005', '+'), 'P3');
caso('CE-04', 'No directivo con 3 meses (1,6,8,13)', asignarPrima('321', 'EVA QUISPE', '003', '-'), 'P4');
caso('CE-05', 'Número 000 (clase 2)', asignarPrima('000', 'JUAN PEREZ', '024', '+'), 'ERROR: número de empleado inválido');
caso('CE-06', 'Número no numérico "A12" (clase 3)', asignarPrima('A12', 'JUAN PEREZ', '024', '+'), 'ERROR: número de empleado inválido');
caso('CE-07', 'Número de 4 dígitos "1234" (clase 4)', asignarPrima('1234', 'JUAN PEREZ', '024', '+'), 'ERROR: número de empleado inválido');
caso('CE-08', 'Nombre con caracteres especiales (clase 7)', asignarPrima('123', 'JUAN@PEREZ', '024', '+'), 'ERROR: nombre inválido');
caso('CE-09', 'Meses no numéricos "1A2" (clase 10)', asignarPrima('123', 'JUAN PEREZ', '1A2', '+'), 'ERROR: meses de trabajo inválido');
caso('CE-10', 'Meses negativos "-01" (clase 11)', asignarPrima('123', 'JUAN PEREZ', '-01', '+'), 'ERROR: meses de trabajo inválido');
caso('CE-11', 'Directivo con "*" (clase 14)', asignarPrima('123', 'JUAN PEREZ', '024', '*'), 'ERROR: indicador de directivo inválido');

echo "\n== CAJA NEGRA: valores límite ==\n";
caso('VL-01', 'Número 001 (límite inferior)', asignarPrima('001', 'JUAN PEREZ', '024', '+'), 'P1');
caso('VL-02', 'Número 999 (límite superior)', asignarPrima('999', 'JUAN PEREZ', '024', '+'), 'P1');
caso('VL-03', 'Número 1000 (superior + 1)', asignarPrima('1000', 'JUAN PEREZ', '024', '+'), 'ERROR: número de empleado inválido');
caso('VL-04', 'Meses 000 (límite inferior, válido)', asignarPrima('123', 'JUAN PEREZ', '000', '-'), 'P4');
caso('VL-05', 'Meses 011 (justo antes de 12)', asignarPrima('123', 'JUAN PEREZ', '011', '+'), 'P3');
caso('VL-06', 'Meses 012 (límite de antigüedad)', asignarPrima('123', 'JUAN PEREZ', '012', '+'), 'P1');
caso('VL-07', 'Meses 013 (límite + 1)', asignarPrima('123', 'JUAN PEREZ', '013', '-'), 'P2');
caso('VL-08', 'Meses 999 (límite superior)', asignarPrima('123', 'JUAN PEREZ', '999', '-'), 'P2');
caso('VL-09', 'Meses 1000 (superior + 1)', asignarPrima('123', 'JUAN PEREZ', '1000', '-'), 'ERROR: meses de trabajo inválido');
caso('VL-10', 'Nombre de 9 caracteres (límite - 1)', asignarPrima('123', 'JUAN PERE', '024', '+'), 'ERROR: nombre inválido');
caso('VL-11', 'Nombre de 10 caracteres (límite)', asignarPrima('123', 'JUAN PEREZ', '024', '+'), 'P1');
caso('VL-12', 'Nombre de 11 caracteres (límite + 1)', asignarPrima('123', 'JUAN PEREZZ', '024', '+'), 'ERROR: nombre inválido');

echo "\n== CAJA NEGRA: tabla de decisión (grafo causa-efecto) ==\n";
foreach ([['R1', '+', '012', 'P1'], ['R2', '-', '012', 'P2'], ['R3', '+', '011', 'P3'], ['R4', '-', '011', 'P4']] as [$r, $d, $m, $e]) {
    caso("TD-$r", "Directivo \"$d\" y $m meses", asignarPrima('123', 'JUAN PEREZ', $m, $d), $e);
}

echo "\n== CAJA BLANCA: caminos independientes (V(G) = 4) ==\n";
caso('CB-1', 'Camino 1: vector vacío (1-2-10)', recorrerVector([]), []);
caso('CB-2', 'Camino 2: número par (1-2-3-4-5-9-2-10)', recorrerVector([12]), ['12', 'Suma de pares: 12']);
caso('CB-3', 'Camino 3: impar >= 50 (1-2-3-4-6-7-9-2-10)', recorrerVector([59]), ['59', 'Número mayor de 50 59']);
caso('CB-4', 'Camino 4: impar < 50 (1-2-3-4-6-8-9-2-10)', recorrerVector([33]), ['33', 'Número menor de 50 33']);
caso('CB-5', 'Valor límite: impar 49 (< 50)', recorrerVector([49]), ['49', 'Número menor de 50 49']);
caso('CB-6', 'Valor límite: 50 es par (no evalúa >= 50)', recorrerVector([50]), ['50', 'Suma de pares: 50']);
$salida = recorrerVector([12, 56, 33, 59, 8, 7, 75, 78, 44, 22]);
caso('CB-7', 'Vector del enunciado: suma final de pares = 220', end($salida), 'Suma de pares: 220');
caso('CB-8', 'Vector del enunciado: 20 líneas impresas', count($salida), 20);

echo "\nResultado: " . ($total - $fallas) . " de $total casos aprobados\n";
exit($fallas ? 1 : 0);
