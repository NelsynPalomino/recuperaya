<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** PRUEBAS DE INTEGRACIÓN: Form Request + controlador + base de datos (RF-01, RNF-04, RNF-05). */
class RegistroYAccesoTest extends TestCase
{
    use RefreshDatabase;

    private function datos(array $cambios = []): array
    {
        return $cambios + ['name' => 'Nelsy Palomino', 'email' => '74001122@continental.edu.pe', 'codigo_universitario' => '74001122',
            'password' => 'Clave2026x', 'password_confirmation' => 'Clave2026x', 'acepta' => '1'];
    }

    #[Test] public function cp01_registro_valido_crea_usuario_con_rol_usuario(): void
    {
        $this->post(route('registro.store'), $this->datos())->assertRedirect(route('inicio'));
        $this->assertDatabaseHas('users', ['email' => '74001122@continental.edu.pe', 'rol' => User::USUARIO]);
        $this->assertAuthenticated();
    }

    #[Test] public function cp03_correo_de_otro_dominio_es_rechazado(): void
    {
        $this->post(route('registro.store'), $this->datos(['email' => 'nelsy@gmail.com']))->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 0);
    }

    #[Test] public function cp07_contrasena_de_7_caracteres_es_rechazada(): void
    {
        $this->post(route('registro.store'), $this->datos(['password' => 'Abc1234', 'password_confirmation' => 'Abc1234']))->assertSessionHasErrors('password');
    }

    #[Test] public function cp09_contrasena_sin_numeros_es_rechazada(): void
    {
        $this->post(route('registro.store'), $this->datos(['password' => 'soloLetras', 'password_confirmation' => 'soloLetras']))->assertSessionHasErrors('password');
    }

    #[Test] public function registro_sin_aceptar_privacidad_es_rechazado(): void
    {
        $datos = $this->datos();
        unset($datos['acepta']);
        $this->post(route('registro.store'), $datos)->assertSessionHasErrors('acepta');
    }

    #[Test] public function asignacion_masiva_no_permite_registrarse_como_administrador(): void
    {
        $this->post(route('registro.store'), $this->datos(['rol' => User::ADMIN]));
        $this->assertSame(User::USUARIO, User::first()->rol);
    }

    #[Test] public function cp11_cuenta_se_bloquea_despues_de_5_intentos_fallidos(): void
    {
        $u = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['email' => $u->email, 'password' => 'incorrecta' . $i]);
        }
        $this->assertTrue($u->fresh()->estaBloqueado());
        $this->post(route('login.store'), ['email' => $u->email, 'password' => 'Clave2026'])->assertSessionHas('error');
        $this->assertGuest();
    }

    #[Test] public function usuario_comun_no_puede_entrar_a_gestion_de_objetos(): void
    {
        $this->actingAs(User::factory()->create())->get(route('objetos.create'))->assertForbidden();
    }

    #[Test] public function route_resource_usa_verbos_en_espanol(): void
    {
        $this->assertStringEndsWith('/objetos/crear', route('objetos.create'));
    }
}
