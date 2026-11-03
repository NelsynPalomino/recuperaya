<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Lugar;
use App\Models\Objeto;
use App\Models\User;
use App\Services\Texto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Objeto> */
class ObjetoFactory extends Factory
{
    protected $model = Objeto::class;

    public function definition(): array
    {
        $codigo = 'OBJ-' . fake()->unique()->numerify('9###');
        return [
            'codigo' => $codigo,
            'slug' => Str::slug('objeto ' . $codigo),
            'categoria_id' => Categoria::query()->inRandomOrder()->value('id') ?? Categoria::create(['nombre' => 'Celular'])->id,
            'lugar_id' => Lugar::query()->inRandomOrder()->value('id') ?? Lugar::create(['nombre' => 'Biblioteca'])->id,
            'descripcion' => fake('es_ES')->sentence(8),
            'color' => fake()->randomElement(['Negro', 'Azul', 'Rojo', 'Blanco', 'Gris']),
            'fecha_hallazgo' => now()->subDays(fake()->numberBetween(0, 20))->toDateString(),
            'foto' => null,
            'pregunta1' => '¿Qué marca es?',
            'respuesta1_hash' => password_hash(Texto::normalizar('marca'), PASSWORD_BCRYPT),
            'pregunta2' => '¿Qué tiene adentro?',
            'respuesta2_hash' => password_hash(Texto::normalizar('nada'), PASSWORD_BCRYPT),
            'estado' => Objeto::EN_CUSTODIA,
            'registrado_por' => User::factory()->encargado(),
        ];
    }
}
