<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Lugar;
use App\Models\Objeto;
use App\Models\User;
use App\Services\Texto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Datos iniciales. Ejecutar con:  php artisan migrate:fresh --seed
 * Contraseñas: Admin2026* / Encargado2026* / Usuario2026*
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // forceCreate: "rol" no es fillable, por eso se usa forceCreate solo en el seeder.
        User::forceCreate(['name' => 'Administrador del sistema', 'email' => 'admin@continental.edu.pe', 'password' => 'Admin2026*', 'rol' => User::ADMIN]);
        $encargado = User::forceCreate(['name' => 'Encargado de Bienestar', 'email' => 'encargado@continental.edu.pe', 'password' => 'Encargado2026*', 'rol' => User::ENCARGADO]);
        User::forceCreate(['name' => 'Estudiante de prueba', 'email' => '72345678@continental.edu.pe', 'codigo_universitario' => '72345678', 'password' => 'Usuario2026*', 'rol' => User::USUARIO]);

        foreach (['Celular', 'Documento / carné', 'Llaves', 'Mochila / bolso', 'Ropa', 'Útiles / calculadora', 'Laptop / tablet', 'Audífonos', 'Billetera', 'Otros'] as $c) {
            Categoria::create(['nombre' => $c]);
        }
        // Ambientes de la sede Huancayo (pabellones A al L).
        foreach ([
            'Biblioteca',
            'Cafetería',
            'Pabellón A – Administración y oficinas',
            'Pabellón B – Bienestar Universitario',
            'Laboratorio de cómputo',
            'Auditorio',
            'Patio central',
            'Estacionamiento',
            'Gimnasio / losa deportiva',
            'Servicios higiénicos',
            'Pabellón B – Centro Asistencial de Salud',
            'Pabellón C – Aulas',
            'Pabellón D – Aulas',
            'Pabellón E – Aulas',
            'Pabellón F – Hub de Información (biblioteca, pisos 2 al 4)',
            'Pabellón F – Laboratorios de Ingeniería',
            'Pabellón F – Laboratorios de simulación de Medicina Humana',
            'Pabellón G – Laboratorios de Ingeniería',
            'Pabellón G – Laboratorios de simulación de Medicina Humana',
            'Pabellón H – Aulas',
            'Pabellón I – Aulas',
            'Pabellón J – Aulas',
            'Pabellón J – Laboratorio de Procesos (J108)',
            'Pabellón K',
            'Pabellón L – Aulas',
            'Centro de Simulación de Ciencias de la Salud',
            'Laboratorio de Análisis Clínico',
            'Laboratorio de Anatomía Patológica',
            'Laboratorio de Terapia Física y Rehabilitación',
            'Laboratorio de Radiología',
            'Laboratorio de Ingeniería Civil',
            'Talleres de manufactura y diseño digital',
            'Comedor y concesiones de alimentos',
            'Áreas verdes y plazas',
            'Ingreso principal (Av. San Carlos)',
            'Servicios higiénicos – Pabellón A',
            'Servicios higiénicos – Pabellón B',
            'Servicios higiénicos – Pabellón C',
            'Servicios higiénicos – Pabellón D',
            'Servicios higiénicos – Pabellón E',
            'Servicios higiénicos – Pabellón F',
            'Servicios higiénicos – Pabellón G',
            'Servicios higiénicos – Pabellón H',
            'Servicios higiénicos – Pabellón I',
            'Servicios higiénicos – Pabellón J',
            'Servicios higiénicos – Pabellón K',
            'Servicios higiénicos – Pabellón L',
        ] as $l) {
            Lugar::create(['nombre' => $l]);
        }

        $demo = [
            ['Celular', 'Biblioteca', 'Celular con pantalla de 6 pulgadas y case, encontrado en una mesa del segundo piso.', 'Negro', 4, 'demo_celular.jpg', '¿Qué imagen tiene como fondo de pantalla?', 'un perro', '¿De qué color es el case?', 'transparente'],
            ['Mochila / bolso', 'Cafetería', 'Mochila mediana con dos bolsillos, contiene cuadernos.', 'Azul', 3, 'demo_mochila.jpg', '¿De qué marca es la mochila?', 'totto', '¿Qué hay en el bolsillo pequeño?', 'audifonos'],
            ['Llaves', 'Patio central', 'Juego de llaves con llavero.', 'Plateado', 2, 'demo_llaves.jpg', '¿Cuántas llaves tiene?', '3', '¿Qué figura tiene el llavero?', 'un corazon'],
            ['Útiles / calculadora', 'Laboratorio de cómputo', 'Calculadora encontrada junto a la computadora 12.', 'Gris', 1, 'demo_calculadora.jpg', '¿De qué marca es?', 'casio', '¿Qué tipo de calculadora es?', 'calculadora cientifica'],
        ];
        foreach ($demo as $i => [$cat, $lug, $desc, $color, $dias, $foto, $p1, $r1, $p2, $r2]) {
            $codigo = sprintf('OBJ-%04d', $i + 1);
            Objeto::forceCreate([
                'codigo' => $codigo,
                'slug' => Str::slug("$cat $color $codigo"),
                'categoria_id' => Categoria::where('nombre', $cat)->value('id'),
                'lugar_id' => Lugar::where('nombre', $lug)->value('id'),
                'descripcion' => $desc, 'color' => $color,
                'fecha_hallazgo' => now()->subDays($dias)->toDateString(),
                'foto' => "objetos/$foto",
                'pregunta1' => $p1, 'respuesta1_hash' => password_hash(Texto::normalizar($r1), PASSWORD_BCRYPT),
                'pregunta2' => $p2, 'respuesta2_hash' => password_hash(Texto::normalizar($r2), PASSWORD_BCRYPT),
                'estado' => Objeto::EN_CUSTODIA, 'registrado_por' => $encargado->id,
            ]);
        }
    }
}
