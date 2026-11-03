<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake('es_PE')->name(),
            'email' => fake()->unique()->numerify('7#######') . '@continental.edu.pe',
            'codigo_universitario' => null,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Clave2026'),
            'remember_token' => Str::random(10),
            'rol' => User::USUARIO,
            'activo' => true,
        ];
    }

    public function encargado(): static
    {
        return $this->state(fn () => ['rol' => User::ENCARGADO]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['rol' => User::ADMIN]);
    }
}
