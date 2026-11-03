<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    use MensajesValidacion;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:150'],
            'password' => ['required', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return $this->mensajesBase();
    }

    public function attributes(): array
    {
        return ['email' => 'correo institucional', 'password' => 'contraseña'];
    }
}
