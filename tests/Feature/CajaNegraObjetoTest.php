<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Lugar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * CASO PRÁCTICO DE CAJA NEGRA: formulario "Registrar objeto encontrado" (RF-03).
 * Técnicas: particiones de equivalencia (clases válidas / inválidas) y análisis de valores límite.
 * Solo se observa la entrada y la salida (acepta / rechaza el campo), sin mirar el código interno.
 */
class CajaNegraObjetoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Categoria::create(['nombre' => 'Celular']);
        Lugar::create(['nombre' => 'Biblioteca']);
    }

    private function enviar(array $cambios)
    {
        $datos = $cambios + [
            'categoria_id' => Categoria::first()->id, 'lugar_id' => Lugar::first()->id,
            'descripcion' => 'Celular negro con funda transparente', 'color' => 'Negro',
            'fecha_hallazgo' => now()->toDateString(), 'foto' => UploadedFile::fake()->image('f.jpg', 800, 600),
            'pregunta1' => '¿Qué fondo tiene?', 'respuesta1' => 'perro',
            'pregunta2' => '¿Color del case?', 'respuesta2' => 'azul',
        ];
        return $this->actingAs(User::factory()->encargado()->create())->post(route('objetos.store'), $datos);
    }

    /** [id, campo, valor, ¿válido?] */
    public static function casos(): array
    {
        $texto = fn (int $n) => str_repeat('a', $n);
        return [
            'CN-01 descripción 9 caracteres (límite inferior - 1)' => ['descripcion', $texto(9), false],
            'CN-02 descripción 10 caracteres (límite inferior)' => ['descripcion', $texto(10), true],
            'CN-03 descripción 500 caracteres (límite superior)' => ['descripcion', $texto(500), true],
            'CN-04 descripción 501 caracteres (límite superior + 1)' => ['descripcion', $texto(501), false],
            'CN-05 color con números (clase inválida)' => ['color', 'Negro 123', false],
            'CN-06 color de 2 letras (límite inferior - 1)' => ['color', 'Az', false],
            'CN-07 color compuesto válido' => ['color', 'Azul con rojo', true],
            // Las fechas se expresan como desplazamientos y se calculan dentro de la prueba (ya con la zona horaria de la
            // aplicación): el proveedor de datos se ejecuta antes de iniciar Laravel, con la zona horaria de php.ini.
            'CN-08 fecha de hallazgo hoy (límite)' => ['fecha_hallazgo', 'today', true],
            'CN-09 fecha de hallazgo mañana (futura)' => ['fecha_hallazgo', 'tomorrow', false],
            'CN-10 fecha con más de un año de antigüedad' => ['fecha_hallazgo', '-1 year -1 day', false],
        ];
    }

    #[Test] #[DataProvider('casos')]
    public function particiones_y_valores_limite(string $campo, string $valor, bool $valido): void
    {
        if ($campo === 'fecha_hallazgo') {
            $valor = now()->modify($valor)->toDateString();
        }
        $respuesta = $this->enviar([$campo => $valor]);
        $valido ? $respuesta->assertSessionHasNoErrors() : $respuesta->assertSessionHasErrors($campo);
    }

    #[Test] public function cn11_foto_de_199_px_es_rechazada(): void
    {
        $this->enviar(['foto' => UploadedFile::fake()->image('f.jpg', 199, 199)])->assertSessionHasErrors('foto');
    }

    #[Test] public function cn12_foto_de_200_px_es_aceptada(): void
    {
        $this->enviar(['foto' => UploadedFile::fake()->image('f.jpg', 200, 200)])->assertSessionHasNoErrors();
    }

    #[Test] public function cn13_foto_mayor_a_5_mb_es_rechazada(): void
    {
        $this->enviar(['foto' => UploadedFile::fake()->image('f.jpg', 800, 600)->size(5121)])->assertSessionHasErrors('foto');
    }

    #[Test] public function cn14_archivo_pdf_es_rechazado(): void
    {
        $this->enviar(['foto' => UploadedFile::fake()->create('f.pdf', 100, 'application/pdf')])->assertSessionHasErrors('foto');
    }
}
