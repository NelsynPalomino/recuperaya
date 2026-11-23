<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Lugar;
use App\Models\Objeto;
use App\Models\User;
use App\Services\Texto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * PRUEBAS DE VOLUMEN (RNF-01): el catálogo y los reportes responden en menos de 2 segundos
 * con 2000 objetos registrados. Se ejecutan en la base SQLite en memoria de las pruebas.
 */
class VolumenTest extends TestCase
{
    use RefreshDatabase;

    private const TOTAL = 2000;
    private const LIMITE_SEGUNDOS = 2.0;

    private User $estudiante;

    protected function setUp(): void
    {
        parent::setUp();
        $categorias = collect(['Celular', 'Llaves', 'Mochila / bolso', 'Billetera'])->map(fn ($n) => Categoria::create(['nombre' => $n])->id);
        $lugares = collect(['Biblioteca', 'Cafetería', 'Pabellón C – Aulas'])->map(fn ($n) => Lugar::create(['nombre' => $n])->id);
        $encargado = User::factory()->encargado()->create();
        $this->estudiante = User::factory()->create();
        $hash = password_hash(Texto::normalizar('respuesta'), PASSWORD_BCRYPT);   // un solo hash para no demorar la carga
        $filas = [];
        for ($i = 1; $i <= self::TOTAL; $i++) {
            $codigo = sprintf('OBJ-%04d', $i);
            $filas[] = [
                'codigo' => $codigo, 'slug' => 'objeto-' . strtolower($codigo),
                'categoria_id' => $categorias[$i % 4], 'lugar_id' => $lugares[$i % 3],
                'descripcion' => "Objeto de prueba número $i encontrado en el campus", 'color' => ['Negro', 'Azul', 'Rojo'][$i % 3],
                'fecha_hallazgo' => now()->subDays($i % 60)->toDateString(), 'foto' => null,
                'pregunta1' => '¿Qué marca es?', 'respuesta1_hash' => $hash, 'pregunta2' => '¿De qué color es?', 'respuesta2_hash' => $hash,
                'estado' => $i % 10 === 0 ? Objeto::DEVUELTO : Objeto::EN_CUSTODIA, 'registrado_por' => $encargado->id,
                'created_at' => now(), 'updated_at' => now(),
            ];
        }
        foreach (array_chunk($filas, 200) as $bloque) {
            DB::table('objetos')->insert($bloque);
        }
    }

    #[Test] public function vol01_catalogo_con_2000_objetos_responde_en_menos_de_2_segundos(): void
    {
        $this->assertSame(self::TOTAL, Objeto::count());
        $inicio = microtime(true);
        $r = $this->actingAs($this->estudiante)->get(route('catalogo.index', ['q' => 'campus']));
        $segundos = microtime(true) - $inicio;
        $r->assertOk()->assertViewHas('objetos', fn ($p) => $p->total() === 1800 && $p->count() === 12);
        $this->assertLessThan(self::LIMITE_SEGUNDOS, $segundos, "El catálogo tardó {$segundos} s");
    }

    #[Test] public function vol02_reporte_estadistico_con_2000_objetos_responde_en_menos_de_2_segundos(): void
    {
        $admin = User::factory()->admin()->create();
        $inicio = microtime(true);
        $this->actingAs($admin)->get(route('estadisticas.index'))->assertOk();
        $this->assertLessThan(self::LIMITE_SEGUNDOS, microtime(true) - $inicio);
    }
}
