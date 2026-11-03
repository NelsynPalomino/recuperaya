<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validación del formulario de registro de usuario (RF-01, RN-01). */
class RegistroRequest extends FormRequest
{
    use MensajesValidacion;

    public function authorize(): bool
    {
        return true; // Cualquier visitante puede registrarse.
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->name),
            'email' => mb_strtolower(trim((string) $this->email)),
            'codigo_universitario' => $this->codigo_universitario ? strtoupper(trim($this->codigo_universitario)) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120', 'regex:/^[\pL\s\.\'-]+$/u'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email', 'regex:/@' . preg_quote(config('recuperaya.dominio_correo'), '/') . '$/i'],
            'codigo_universitario' => ['nullable', 'alpha_num', 'between:6,20', 'unique:users,codigo_universitario'],
            'password' => ['required', 'string', 'min:8', 'max:64', 'confirmed', 'regex:/[A-Za-z]/', 'regex:/[0-9]/'],
            'acepta' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return $this->mensajesBase() + [
            'name.regex' => 'El nombre solo puede tener letras y espacios.',
            'email.regex' => 'Debes usar tu correo institucional @' . config('recuperaya.dominio_correo') . '.',
            'password.regex' => 'La contraseña debe tener letras y números.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre completo',
            'email' => 'correo institucional',
            'codigo_universitario' => 'código universitario',
            'password' => 'contraseña',
            'acepta' => 'acepto la política de privacidad',
        ];
    }
}
